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

require_login();

$packageid = optional_param('package', 0, PARAM_INT);
$sourcecourseid = optional_param('sourcecourse', 0, PARAM_INT);

$package = block_edupublisher::get_package($packageid, false);

// The id of the course we are importing FROM (will only be set if past first stage
$importcourseid = $sourcecourseid;
$targetcourseid = 0;
// If package exist take importcourseid from package-sourcecourse and targetcourseid from package-course.
if ($package && $package->sourcecourse > 0) {
    $importcourseid = $package->sourcecourse;
    $targetcourseid = $package->course;
}
$restoretarget = optional_param('target', backup::TARGET_CURRENT_ADDING, PARAM_INT);

// Load the course and context
$importcourse = get_course($importcourseid);
$importcontext = context_course::instance($importcourse->id);
// Must pass login
require_login($importcourse);
// Must hold restoretargetimport in the current course
require_capability('moodle/restore:restoretargetimport', $importcontext);

// Set up the page
$PAGE->set_title($importcourse->shortname . ': ' . get_string('import'));
$PAGE->set_heading($importcourse->fullname);
if ($package->id > 0) {
    $PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/publish.php?package=' . $package->id, array()));
} else {
    $PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/publish.php?sourcecourse=' . $sourcecourseid, array()));
}

$PAGE->set_context($importcontext);
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

$PAGE->navbar->add(get_string('publish_new_package', 'block_edupublisher'), $PAGE->url);
block_edupublisher::check_requirements();

if (optional_param('cancel', 0, PARAM_INT) == 1) {
    block_edupublisher::print_app_header();
    if (block_edupublisher::is_admin() || $package->userid == $USER->id) {
        if ($package->active == 0) {
            if ($package->course > 0) {
                delete_course($package->course, false);
            }
            $DB->delete_records('block_edupublisher_metadata', array('package' => $package->id));
            $DB->delete_records('block_edupublisher_packages', array('id' => $package->id));
            echo $OUTPUT->render_from_template(
                'block_edupublisher/alert',
                (object)array('type' => 'success', 'content' => 'Package was removed!', 'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->sourcecourse)
            );
        } else {
            echo $OUTPUT->render_from_template(
                'block_edupublisher/alert',
                (object)array('type' => 'warning', 'content' => 'Sorry, package is already activated!', 'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->sourcecourse)
            );
        }
    } else {
        echo $OUTPUT->render_from_template(
            'block_edupublisher/alert',
            (object)array('type' => 'warning', 'content' => get_string('permission_denied', 'block_edupublisher'), 'url' => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $package->sourcecourse)
        );
    }
    block_edupublisher::print_app_footer();
    die();
}


// Attempt to load the existing backup controller (backupid will be false if there isn't one)
$backupid = optional_param('backup', false, PARAM_ALPHANUM);
//print_r($package);
$PREVENTFORM = !empty($backupid);
/**
 * Create a package & course
**/
if (empty($backupid)) {
    // We did not start backup - go through form.
    //if ((!$package || $package->id == 0) && $importcourseid > 0) {
    if (empty($package->id)) {
        $package = block_edupublisher::get_package_from_course($importcourseid);
    }

    require_once($CFG->dirroot . '/blocks/edupublisher/classes/package_create_form.php');
    $form = new package_create_form();
    if ($data = $form->get_data()) {
        $data->title = $data->default_title;

        // Create the target course if necessary
        $category = get_config('block_edupublisher', 'category');
        $targetcourse = $importcourse;
        $targetcourse->category = intval($category);
        $targetcourse->fullname = $data->title;
        $targetcourse->summary = $data->default_summary['text']; // At this stage the editor is represented as array with fields text and format
        $targetcourse->visible = 1;
        $targetcourse->shortname = '[' . $USER->id . '-' . date('YmdHis') . ']';
        $targetcourse->idnumber = '';
        if (!empty($data->clonecourse) && $data->clonecourse == 1) {
            // Create a target course
            $targetcourse->id = 0;
            $targetcourse = create_course($targetcourse);
        } else {
            require_once($CFG->dirroot . '/course/lib.php');
            update_course($targetcourse);
            move_courses(array($targetcourse->id), intval($category));
        }

        $targetcontext = context_course::instance($targetcourse->id);
        $targetcourseid = $targetcourse->id;

        $data->course = $targetcourse->id;
        $data->default_weblink = $CFG->wwwroot . '/course/view.php?id=' . $targetcourse->id;

        if ($targetcourse->id == 0) {
            block_edupublisher::print_app_header();
            echo "<p class=\"alert alert-failure\">Package-Course could not be created, will not store anything.</p>";
            block_edupublisher::print_app_footer();
            die();
        } else {
            $package = block_edupublisher::store_package($data);
            //redirect($CFG->wwwroot . '/blocks/edupublisher/pages/publish.php?package=' . $package->id);
            //echo "<p class=\"alert alert-success\">" . get_string('successfully_saved_package', 'block_edupublisher') . "</p>";
            $PREVENTFORM = true;
        }
    }
    if (!$PREVENTFORM) {
        block_edupublisher::print_app_header();

        $package = block_edupublisher::prepare_package_form($package);
        $form->set_data($package);
        $form->display();
        block_edupublisher::print_app_footer();
        die();
    }
}

/**
 * Import contents
**/
if ($package->id > 0 && $PREVENTFORM) {
    try {
        $PAGE->set_url($CFG->wwwroot . '/blocks/edupublisher/pages/publish.php?package=' . $package->id);
        block_edupublisher::print_app_header();

        // If they are the NOT the same we need the import-function
        $DOIMPORT = ($package->sourcecourse != $package->course);
        if (!$DOIMPORT) {
            $DOPOSTTASKS = true;
        } else {
            if (empty($targetcourse) || empty($targetcourse->id)) {
                $targetcourse = get_course($targetcourseid);
            }
            $targetcontext = context_course::instance($targetcourseid);
            // Load the course +context to import from
            $importcourse = get_course($importcourseid);
            $importcontext = context_course::instance($importcourseid);
            // Make sure the user can backup from that course, otherwise we are not entitle to publish something!
            require_capability('moodle/backup:backuptargetimport', $importcontext);

            block_edupublisher::role_set(array($targetcourse->id), array($USER->id), 'defaultroleteacher');
            require_capability('moodle/restore:restoretargetimport', $targetcontext);
            // Prepare the backup renderer
            $renderer = $PAGE->get_renderer('core','backup');

            // Attempt to load the existing backup controller (backupid will be false if there isn't one)
            $backupid = optional_param('backup', false, PARAM_ALPHANUM);
            if (!($bc = backup_ui::load_controller($backupid))) {
                $bc = new backup_controller(backup::TYPE_1COURSE, $importcourse->id, backup::FORMAT_MOODLE,
                                        backup::INTERACTIVE_YES, backup::MODE_IMPORT, $USER->id);
                $bc->get_plan()->get_setting('users')->set_status(backup_setting::LOCKED_BY_CONFIG);
                $settings = $bc->get_plan()->get_settings();
                $settings_enable = array( 'blocks');
                $settings_disable = array( 'calendarevents', 'filters', 'users');

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
                //echo '<p class="alert alert-warning">Consent that you have permission to publish</p>';
                $backup->get_setting('filename')->set_visibility(backup_setting::HIDDEN);
                import_ui::skip_current_stage(true);
            }
            // If it's the final stage process the import
            if ($backup->get_stage() == backup_ui::STAGE_FINAL) {
                // Display an extra progress bar so that we can show the current stage.
                echo html_writer::start_div('', array('id' => 'executionprogress'));
                echo $renderer->progress_bar($backup->get_progress_bar());
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
                            echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id'=>$importcourseid)));
                            block_edupublisher::print_app_footer();
                            die();
                        }
                        if (!empty($precheckresults['warnings'])) { // If warnings are found, go ahead but display warnings later.
                            $warnings = $precheckresults['warnings'];
                        }
                    }
                }
                if ($restoretarget == backup::TARGET_CURRENT_DELETING || $restoretarget == backup::TARGET_EXISTING_DELETING) {
                    restore_dbops::delete_course_content($targetcourse->id);
                }
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
                $DOPOSTTASKS = true;
            } else {
                // Otherwise save the controller and progress
                $backup->save_controller();
            }
        }
        if ($DOPOSTTASKS) {
            // Create edupublisher-block in sourcecontext & targetcourse.
            $sourcecontext = context_course::instance($importcourseid);
            block_edupublisher::add_to_context($targetcontext);
            block_edupublisher::add_to_context($sourcecontext);

            // Flatten sections and remove empty ones.
            $sections = $DB->get_records('course_sections', array('course' => $targetcourse->id));
            $i = 0;
            foreach($sections AS $section) {
                if (empty($section->sequence)) {
                    $DB->delete_records('course_sections', array('id' => $section->id));
                } else {
                    $section->section = $i++;
                    $DB->update_record('course_sections', $section);
                }
            }
            rebuild_course_cache($package->course, true);

            // Activate package
            $package = block_edupublisher::get_package($package->id, true);

            // Unenrol all users from targetcourse
            $users = get_enrolled_users($targetcontext);
            $instances = $DB->get_records('enrol', array('courseid' => $package->course));
            foreach ($instances as $instance) {
                $plugin = enrol_get_plugin($instance->enrol);
                foreach($users AS $_user) {
                    $plugin->unenrol_user($instance, $_user->id);
                }
            }
            block_edupublisher::role_set(array($package->course), array($USER->id), 'defaultrolestudent');

            block_edupublisher::store_package($package);
            // Create the comment.
            $sendto = array('allmaintainers');
            block_edupublisher::store_comment($package, 'comment:template:package_created', $sendto, true, false);

            echo $OUTPUT->notification(get_string('successfully_published_package', 'block_edupublisher'), 'notifysuccess');
            echo $OUTPUT->continue_button(new moodle_url('/blocks/edupublisher/pages/package.php?id=' . $package->id));
            // Get and display log data if there was any.
            if (isset($loghtml)) {
                $loghtml = $logger->get_html();
                if ($loghtml != '') {
                    echo $renderer->log_display($loghtml);
                }
            }
            block_edupublisher::print_app_footer();
            die();
        }
        if ($backup) {
            // Display the current stage
            if ($backup->enforce_changed_dependencies()) {
                debugging('Your settings have been altered due to unmet dependencies', DEBUG_DEVELOPER);
            }

            if (!class_exists('phpQuery', true)) {
                require_once($CFG->dirroot . '/blocks/edupublisher/vendor/somesh/php-query/phpQuery/phpQuery.php');
            }
            $doc = phpQuery::newDocument($backup->display($renderer));
            $cbox = pq('#id_previous')->parent()->parent()->parent();
            if ($backup->get_stage() <= 2) {
                pq('#id_previous')->parent()->parent()->remove();
            }
            pq('#id_cancel')->remove();
            pq($cbox)->append(pq('<input>')->attr('type', 'button')->attr('value', get_string('cancel'))->attr('class', 'ui-btn btn')->attr('onclick', "require(['block_edupublisher/main'], function(MAIN) { MAIN.cancelPackageForm('" . $CFG->wwwroot . "/blocks/edupublisher/pages/publish.php?package=" . $package->id . "&cancel=1') }); return false;"));
            pq($cbox)->append(pq($cbox)->find('#id_submitbutton')->parent()->parent());

            echo $doc->htmlOuter();

            $backup->destroy();
            unset($backup);
        }
        block_edupublisher::print_app_footer();
    } finally {
        // Unenrol user from targetcourse
        block_edupublisher::role_set(array($package->course), array($USER->id), -1);
    }
}
