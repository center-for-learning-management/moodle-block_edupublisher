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
        //$bc->get_plan()->get_setting('blocks')->set_value(0);
        //$bc->get_plan()->get_setting('users')->set_value(0);
        //$bc->get_plan()->get_setting('blocks')->set_status(backup_setting::LOCKED_BY_CONFIG);
        $bc->get_plan()->get_setting('users')->set_status(backup_setting::LOCKED_BY_CONFIG);
        $settings = $bc->get_plan()->get_settings();
        $settings_disable = array( 'blocks', 'calendarevents', 'filters', 'users');

        // For the initial stage we want to hide all locked settings and if there are
        // no visible settings move to the next stage
        $visiblesettings = false;
        foreach ($settings as $setting) {
            // Disable undesired settings
            if (in_array($setting->get_name(), $settings_disable) && $setting->get_status() == backup_setting::NOT_LOCKED) {
                $setting->set_value(0);
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
        $PAGE->requires->js_call_amd('block_edupublisher/main', 'clickImportConfirmation', array());
    }
    // If it's the final stage process the import
    if ($backup->get_stage() == backup_ui::STAGE_FINAL) {
        // Display an extra progress bar so that we can show the current stage.
        echo html_writer::start_div('', array('id' => 'executionprogress'));
        //echo $renderer->progress_bar($backup->get_progress_bar());
        // Start the progress display - we split into 2 chunks for backup and restore.
        $progress = new \core\progress\display();
        $progress->start_progress('', 2);
        $backup->get_controller()->set_progress($progress);
        // Prepare logger for backup.
        $logger = new core_backup_html_logger($CFG->debugdeveloper ? backup::LOG_DEBUG : backup::LOG_INFO);
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
        // Prepare the restore controller. We don't need a UI here as we will just use what
        // ever the restore has (the user has just chosen).
        $rc = new restore_controller($backupid, $targetcourse->id, backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, $restoretarget);
        // Start a progress section for the restore, which will consist of 2 steps
        // (the precheck and then the actual restore).
        $progress->start_progress('Restore process', 2);
        $rc->set_progress($progress);
        // Set logger for restore.
        $rc->add_logger($logger);
        // Convert the backup if required.... it should NEVER happed
        if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
            $rc->convert();
        }
        // Mark the UI finished.
        //$rc->finish_ui();
        // Execute prechecks
        $warnings = false;
        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults)) {
                if (!empty($precheckresults['errors'])) { // If errors are found, terminate the import.
                    fulldelete($tempdestination);
                    echo $renderer->precheck_notices($precheckresults);
                    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id'=>$targetcourseid)));
                    block_edupublisher::print_app_footer();
                    die();
                }
                if (!empty($precheckresults['warnings'])) { // If warnings are found, go ahead but display warnings later.
                    $warnings = $precheckresults['warnings'];
                }
            }
        }
        if ($restoretarget == backup::TARGET_CURRENT_DELETING || $restoretarget == backup::TARGET_EXISTING_DELETING) {
            // This would not be intended by us!
            //restore_dbops::delete_course_content($targetcourse->id);
        }

        // NEW BEHAVIOUR - INJECT INTO SECTION ITSELF

        // Create a label at the end of the section.
        require_once($CFG->dirroot . '/blocks/edupublisher/classes/module_compiler.php');
        $data = array(
            'course' => $targetcourse->id,
            'intro' => '<h3>' . $package->title . '</h3>',
            'introformat' => 1,
            'section' => $sectionnr
        );
        $item = block_edupublisher_module_compiler::compile('label', (object)$data, (object)array());
        //print_r($item);
        $module = block_edupublisher_module_compiler::create($item);

        // Now store the data of all sections' sequences in targetcourse.
        $sections = $DB->get_records('course_sections', array('course' => $targetcourse->id));

        // Now do the import.
        // Execute the restore.
        $rc->execute_plan();
        // Delete the temp directory now
        fulldelete($tempdestination);
        // End restore section of progress tracking (restore/precheck).
        $progress->end_progress();
        // All progress complete. Hide progress area.
        $progress->end_progress();
        echo html_writer::end_div();
        echo html_writer::script('document.getElementById("executionprogress").style.display = "none";');
        // Display a notification and a continue button
        if ($warnings) {
            echo $OUTPUT->box_start();
            echo $OUTPUT->notification(get_string('warning'), 'notifyproblem');
            echo html_writer::start_tag('ul', array('class'=>'list'));
            foreach ($warnings as $warning) {
                echo html_writer::tag('li', $warning);
            }
            echo html_writer::end_tag('ul');
            echo $OUTPUT->box_end();
        }

        // Now log section data again.
        // All modules that are new have to be moved to $sectionid.
        // Newly created sections have to be removed.
        $sections_new = $DB->get_records('course_sections', array('course' => $targetcourse->id));
        foreach ($sections_new AS $id => $section) {
            if (!empty($sections[$id])) {
                $newsequence = $sections_new[$id]->sequence;
                // Set section data to original, but with new sequence.
                $sections_new[$id] = $sections[$id];
                $sections_new[$id]->sequence = $newsequence;
                $DB->update_record('course_sections', $sections_new[$id]);

                // This section existed before - compare sequence.
                $oldsequence = explode(',', $sections[$id]->sequence);
                $newsequence = explode(',', $sections_new[$id]->sequence);

                //echo "Comparing old sequence";
                //print_r($oldsequence);
                //print_r($newsequence);
                $cmids_to_move = array();
                foreach ($newsequence AS $cmid) {
                    if (!in_array($cmid, $oldsequence)) {
                        $cmids_to_move[] = $cmid;
                    }
                }
                $remove_section = false;
                //echo "Old section, moving the following cmids";
                //print_r($cmids_to_move);
            } else {
                // This section is new - move all content and remove afterwards.
                $cmids_to_move = explode(',', $sections[$id]->sequence);
                $remove_section = true;
                //echo "New section, moving the following cmids";
                //print_r($cmids_to_move);
            }
            foreach ($cmids_to_move AS $cmid) {
                course_add_cm_to_section($targetcourse, $cmid, $sectionnr);
            }
            if ($remove_section) {
                course_delete_section($targetcourse, $sections_new[$id], true);
            }
        }

        rebuild_course_cache($targetcourse->id, true);

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
