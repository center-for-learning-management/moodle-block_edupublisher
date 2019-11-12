<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_edupublisher
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
**/

define('NO_OUTPUT_BUFFERING', true);

require_once('../../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/util/ui/import_extensions.php');
require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');

// Import a certain package to a course, if sectionid given move it to that position

$packageid = required_param('package', PARAM_INT);
$targetcourseid = required_param('course', PARAM_INT);
$sectionid = optional_param('section', 0, PARAM_INT);

$section = $DB->get_record('course_sections', array('course' => $targetcourseid, 'id' => $sectionid));
$sectionnr = $section->section;

// Require editing-permissions in targetcourse
$targetcourse = $DB->get_record('course', array('id'=>$targetcourseid), '*', MUST_EXIST);
$targetcontext = context_course::instance($targetcourse->id);
require_login($targetcourse);
require_capability('moodle/restore:restoretargetimport', $targetcontext);

// Set up the page
$PAGE->set_title(get_string('import'));
$PAGE->set_heading(get_string('import'));
$PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/import.php', array('package' => $packageid, 'course'=>$targetcourseid, 'section' => $sectionid)));
$PAGE->set_context($targetcontext);
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

block_edupublisher::check_requirements();

// Load package and importcourse + context
$package = block_edupublisher::get_package($packageid, false);
$importcourseid = $package->course;
$importcourse = $DB->get_record('course', array('id'=>$importcourseid), '*', MUST_EXIST);
$importcontext = context_course::instance($importcourse->id);

$restoretarget = optional_param('target', backup::TARGET_CURRENT_ADDING, PARAM_INT);

try {
    block_edupublisher::print_app_header();
    // Temporarily grant user trainer-permission in package-course
    block_edupublisher::role_set(array($importcourse->id), array($USER->id), 'defaultroleteacher');
    // Make sure we have the required capabilities
    require_capability('moodle/restore:restoretargetimport', $importcontext);

    // Prepare the backup renderer
    $renderer = $PAGE->get_renderer('core','backup');

    // Attempt to load the existing backup controller (backupid will be false if there isn't one)
    $backupid = optional_param('backup', false, PARAM_ALPHANUM);
    if (!($bc = backup_ui::load_controller($backupid))) {
        $bc = new backup_controller(backup::TYPE_1COURSE, $importcourse->id, backup::FORMAT_MOODLE,
                                backup::INTERACTIVE_YES, backup::MODE_IMPORT, $USER->id);
        $settings = $bc->get_plan()->get_settings();
        $settings_enable = array('blocks');
        $settings_disable = array('calendarevents', 'filters', 'users');

        // For the initial stage we want to hide all locked settings and if there are
        // no visible settings move to the next stage
        $visiblesettings = false;
        foreach ($settings as $setting) {
            // Disable undesired settings
            if (in_array($setting->get_name(), $settings_disable) && $setting->get_status() == backup_setting::NOT_LOCKED) {
                $setting->set_value(0);
                $setting->set_status(backup_setting::LOCKED_BY_CONFIG);
            }
            if (in_array($setting->get_name(), $settings_enable) && $setting->get_status() == backup_setting::NOT_LOCKED) {
                $setting->set_value(1);
                $setting->set_status(backup_setting::LOCKED_BY_CONFIG);
            }
            if ($setting->get_status() !== backup_setting::NOT_LOCKED) {
                $setting->set_visibility(backup_setting::HIDDEN);
            } else {
                $visiblesettings = true;
            }
        }
        // @rschrenk: We will always skip this step!
        //import_ui::skip_current_stage(!$visiblesettings);
        import_ui::skip_current_stage(true);
    }
    // Prepare the import UI
    $backup = new import_ui($bc, array('importid'=>$importcourse->id, 'target'=>$restoretarget));
    // Process the current stage
    $backup->process();

    // If this is the confirmation stage remove the filename setting
    if ($backup->get_stage() == backup_ui::STAGE_CONFIRMATION) {
        $backup->get_setting('filename')->set_visibility(backup_setting::HIDDEN);
        // We try to skip this stage!
        import_ui::skip_current_stage(true);
        // If we are not able to skip this step and the form is display, we click the "confirm"-button using JS.
        $PAGE->requires->js_call_amd('block_edupublisher/main', 'clickImportConfirmation', array());
    }

    // If it's the final stage process the import
    if ($backup->get_stage() == backup_ui::STAGE_FINAL) {
        // Display an extra progress bar so that we can show the current stage.
        //echo html_writer::start_div('', array('id' => 'executionprogress'));
        //echo $renderer->progress_bar($backup->get_progress_bar());
        // Start the progress display - we split into 2 chunks for backup and restore.
        $progress = new \core\progress\display();
        $progress->start_progress('', 2);
        $backup->get_controller()->set_progress($progress);
        // Prepare logger for backup.
        $logger = new core_backup_html_logger(backup::LOG_INFO); // $CFG->debugdeveloper ? backup::LOG_DEBUG : backup::LOG_INFO);
        $backup->get_controller()->add_logger($logger);
        // First execute the backup
        $backup->execute();
        $backup->destroy();
        unset($backup);
        // Note that we've done that progress.
        $progress->progress(1);
        // Check whether the backup directory still exists. If missing, something
        // went really wrong in backup, throw error. Note that backup::MODE_IMPORT
        // backups don't store resulting files ever
        $tempdestination = make_backup_temp_directory($backupid, false);
        if (!file_exists($tempdestination) || !is_dir($tempdestination)) {
            print_error('unknownbackupexporterror'); // shouldn't happen ever
        }


        // Backup was ok - we create a label and to the restore.
        // Create a label at the end of the section.
        require_once($CFG->dirroot . '/blocks/edupublisher/classes/module_compiler.php');
        $data = array(
            'course' => $targetcourse->id,
            'intro' => '<h3>' . $package->title . '</h3>',
            'introformat' => 1,
            'section' => $sectionnr,
        );
        $item = block_edupublisher_module_compiler::compile('label', (object)$data, (object)array());
        $module = block_edupublisher_module_compiler::create($item);

        // Now store the data of all sections' sequences in targetcourse.
        $sections_old = $DB->get_records('course_sections', array('course' => $targetcourse->id));

        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $transaction = $DB->start_delegated_transaction();

        // Restore backup into course.
        $rc = new restore_controller($bc->get_backupid(), $targetcourseid,
                backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id,
                backup::TARGET_EXISTING_ADDING);
        if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
            $rc->convert();
        }
        $rc->execute_precheck();

        // Commit.
        $transaction->allow_commit();

        // NEW BEHAVIOUR - INJECT INTO SECTION ITSELF

        // Now do the import.
        // Execute the restore.
        $rc->execute_plan();
        // Delete the temp directory now
        fulldelete($tempdestination);
        // End restore section of progress tracking (restore/precheck).
        //$progress->end_progress();
        // All progress complete. Hide progress area.
        $progress->end_progress();
        //echo html_writer::end_div();
        //echo html_writer::script('document.getElementById("executionprogress").style.display = "none";');
        // Display a notification and a continue button

        $remove_sections = array();
        $cmids_new = array();
        $sections_new = $DB->get_records('course_sections', array('course' => $targetcourse->id));
        $sql = "SELECT section,name
                    FROM {course_sections}
                    WHERE course=?";
        $sections_basement_by_no = $DB->get_records_sql($sql, array($importcourse->id));

        foreach ($sections_new AS $id => $section) {
            $oldsequence = !empty($sections_old[$id]) ? explode(',', $sections_old[$id]->sequence) : array();
            $newsequence = explode(',', $sections_new[$id]->sequence);
            if (!empty($sections_basement_by_no[$section->section])) {
                $sec = $sections_basement_by_no[$section->section];
                if (!empty($sec->name)) {
                    // Add label for section.
                    $cmids_new[] = $sec->name;
                }
            }

            if (!empty($sections_old[$id])) {
                foreach ($newsequence AS $cmid) {
                    if (!in_array($cmid, $oldsequence)) {
                        $cmids_new[] = $cmid;
                    }
                }
                $sections_old[$id]->sequence = $sections_new[$id]->sequence;
                // Reset old data.
                $DB->update_record('course_sections', $sections_old[$id]);
            } else {
                $remove_sections[] = $id;
                foreach ($newsequence AS $cmid) {
                    $cmids_new[] = $cmid;
                }
            }
        }
        foreach ($cmids_new AS $cmid) {
            if (intval($cmid) > 0) {
                //echo "<li>Moving cmid #" . $cmid . " to section no " . $sectionnr . "</li>\n";
                course_add_cm_to_section($targetcourseid, $cmid, $sectionnr);
            } elseif(strlen($cmid) > 0) {
                $data = array(
                    'course' => $targetcourse->id,
                    'intro' => '<h4>' . $cmid . '</h4>',
                    'introformat' => 1,
                    'section' => $sectionnr,
                );
                $item = block_edupublisher_module_compiler::compile('label', (object)$data, (object)array());
                $module = block_edupublisher_module_compiler::create($item);
                //echo "<li>Creating label " . $cmid . " with cmid " . $module->coursemodule . "</li>\n";
                course_add_cm_to_section($targetcourseid, $module->coursemodule, $sectionnr);
            }

        }
        foreach ($remove_sections AS $rs) {
            //echo "<li>Delete section #" . $id . "</li>\n";
            course_delete_section($targetcourseid, $id, true);
        }

        rebuild_course_cache($targetcourseid, true);

        $DB->insert_record('block_edupublisher_uses', (object) array(
            'userid' => $USER->id,
            'package' => $package->id,
            'targetcourse' => $targetcourse->id,
            'created' => time()
        ));
        if (block_edupublisher::uses_eduvidual()) {
            // This is only used for a certain instance-specific app for www.eduvidual.at. This plugin also works without this line!
            $PAGE->requires->js_call_amd('block_eduvidual/jquery-ba-postmessage', 'post', array('edupublisher_import_complete'));
        }
        echo $OUTPUT->notification(get_string('importsuccess', 'backup'), 'notifysuccess');
        echo $OUTPUT->continue_button(new moodle_url('/course/view.php?id=' . $targetcourse->id));
        // Get and display log data if there was any.
        $loghtml = $logger->get_html();
        if ($loghtml != '') {
            echo $renderer->log_display($loghtml);
        }
        $redirect = $CFG->wwwroot . '/course/view.php?id=' . $targetcourse->id;
    } else {
        // Otherwise save the controller and progress
        $backup->save_controller();
    }
    // Display the current stage
    if (isset($backup)) {
        if($backup->enforce_changed_dependencies()) {
            debugging('Your settings have been altered due to unmet dependencies', DEBUG_DEVELOPER);
        }
        //echo $renderer->progress_bar($backup->get_progress_bar());
        if (!class_exists('phpQuery', true)) {
            require_once($CFG->dirroot . '/blocks/edupublisher/vendor/somesh/php-query/phpQuery/phpQuery.php');
        }
        $doc = phpQuery::newDocument($backup->display($renderer));
        //pq('form')->append(pq('<input>')->attr('type', 'hidden')->attr('value', $layout)->attr('name', 'layout'));
        $cbox = pq('#id_previous')->parent()->parent()->parent();
        if ($backup->get_stage() <= 2) {
            pq('#id_previous')->parent()->parent()->remove();
        }
        if ($backup->get_stage() == backup_ui::STAGE_CONFIRMATION) {
            pq('form')->addClass('invisible');
            pq('form')->parent()->prepend(pq('<p>' . get_string('loading') . '</p>'));
        }
        pq('#id_cancel')->remove();
        pq($cbox)->append(pq('<input>')->attr('type', 'button')->attr('value', get_string('cancel'))->attr('class', 'ui-btn btn')->attr('onclick', "history.back(); return false;"));
        pq($cbox)->append(pq($cbox)->find('#id_submitbutton')->parent()->parent());

        echo $doc->htmlOuter();
        $backup->destroy();
        unset($backup);
    }
} catch(Exception $e) {
    echo 'Exception: ' . $e->getMessage();
    echo $e->getTraceAsString();
} finally {
    // Withdraw user trainer-permission in package-course
    block_edupublisher::role_set(array($importcourse->id), array($USER->id), -1);
    block_edupublisher::print_app_footer();
    if (!empty($redirect)) { redirect($redirect); }
}
