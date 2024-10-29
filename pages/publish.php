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
require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');

// Backup of large courses requires extra memory. Use the amount configured
// in admin settings.
raise_memory_limit(MEMORY_EXTRA);

require_login();

$sourcecourseid = optional_param('sourcecourseid', 0, PARAM_INT);
$targetcourseid = optional_param('targetcourseid', 0, PARAM_INT);
$packageid = optional_param('packageid', 0, PARAM_INT);

if (!empty($sourcecourseid)) {
    $publish = $DB->get_record('block_edupublisher_publish', array('sourcecourseid' => $sourcecourseid));
} elseif (!empty($targetcourseid)) {
    $publish = $DB->get_record('block_edupublisher_publish', array('targetcourseid' => $targetcourseid));
} elseif (!empty($packageid)) {
    $publish = $DB->get_record('block_edupublisher_publish', array('packageid' => $packageid));
}

if (empty($publish->id)) {
    $sourcecoureid = required_param('sourcecourseid', PARAM_INT);
    $publish = (object)array(
        'sourcecourseid' => $sourcecourseid,
        'targetcourseid' => 0,
        'packageid' => 0,
        'timecreated' => time(),
        'timemodified' => time(),
        'payload' => '',
    );
    $publish->id = $DB->insert_record('block_edupublisher_publish', $publish);
}
$publish->timemodified = time();
$DB->set_field('block_edupublisher_publish', 'timemodified', $publish->timemodified, array('id' => $publish->id));

$packageid = $publish->packageid;
$sourcecourseid = $publish->sourcecourseid;
$targetcourseid = $publish->targetcourseid;

$sourcecontext = \context_course::instance($sourcecourseid, 'IGNORE_MISSING');

// Set up the page
$PAGE->set_context($sourcecontext);
$PAGE->set_title(get_string('publish_new_package', 'block_edupublisher'));
$PAGE->set_heading(get_string('publish_new_package', 'block_edupublisher'));
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');
$urlparams = [
    'packageid' => $packageid,
    'sourcecourseid' => $sourcecourseid,
    'targetcourseid' => $targetcourseid,
];
$PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/publish.php', $urlparams));
$PAGE->navbar->add(get_string('publish_new_package', 'block_edupublisher'), $PAGE->url);

if (empty($publish->sourcecourseid) || empty($sourcecontext->id)) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
        'content' => get_string('publish_missing_sourcecourseid', 'block_edupublisher'),
        'type' => 'danger',
        'url' => $CFG->wwwroot . '/my',
    ));
    echo $OUTPUT->footer();
    die();
}

if (!empty($targetcourseid)) {
    $targetcontext = \context_course::instance($targetcourseid, IGNORE_MISSING);
    if (empty($targetcontext->id)) {
        $publish->targetcourseid = 0;
        $publish->importcompleted = 0;
        $publish->packageid = 0;
        $DB->set_field('block_edupublisher_publish', 'targetcourseid', 0, array('id' => $publish->id));
        $DB->set_field('block_edupublisher_publish', 'importcompleted', 0, array('id' => $publish->id));
        $DB->set_field('block_edupublisher_publish', 'packageid', 0, array('id' => $publish->id));
        $PAGE->set_context($sourcecontext);
        require_login($sourcecourseid);
    } else {
        $PAGE->set_context($targetcontext);
        require_login($targetcourseid);
    }
} else {
    $PAGE->set_context($sourcecontext);
    require_login($sourcecourseid);
}

\block_edupublisher\lib::check_requirements();

if (!empty($packageid)) {
    $publish->publishstage_finish = 1;
    $publishstage = 5;
} elseif (!empty($publish->importcompleted)) {
    $publish->publishstage_metadata = 1;
    $publishstage = 4;
} elseif (!empty($targetcourseid) && file_exists("$CFG->backuptempdir/source$sourcecourseid/moodle_backup.xml")) {
    $publish->publishstage_import = 1;
    $publishstage = 3;
} else {
    $confirmed = optional_param('confirmed', 0, PARAM_INT);
    if ($confirmed == 0) {
        $publish->publishstage_confirm = 1;
        $publishstage = 0;
    } elseif (!file_exists("$CFG->backuptempdir/source$sourcecourseid/moodle_backup.xml")) {
        $publish->publishstage_import = 1;
        $publishstage = 1;
    } else {
        $publish->publishstage_import = 1;
        $publishstage = 2;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('block_edupublisher/publish_navbar', $publish);

switch ($publishstage) {
    case 0:
        echo get_string('publish_stage_confirm_text', 'block_edupublisher');
        $url = new \moodle_url('/blocks/edupublisher/pages/publish.php', $urlparams + ['confirmed' => 1]);
        $label = get_string('publish_stage_confirm_button', 'block_edupublisher');
        echo "<div style=\"text-align: center;\"><a href=\"$url\" class=\"btn btn-primary\">$label</a></div>\n";
        break;
    case 1:
        // 1.) Create a backup.
        require_capability('moodle/backup:backuptargetimport', $sourcecontext);
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE, $sourcecourseid, \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_YES, \backup::MODE_SAMESITE, $USER->id
        );
        $format = $bc->get_format();
        $type = $bc->get_type();
        $id = $bc->get_id();

        $targetfilename = "backup$sourcecourseid.mbz";

        $settings = array(
            'users' => 0,
            'filename' => $targetfilename,
        );
        foreach ($settings as $setting => $value) {
            try {
                $bc->get_plan()->get_setting($setting)->set_value($value);
            } catch (Exception $e) {

            }
        }

        $bc->finish_ui();
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];

        $fp = \get_file_packer('application/vnd.moodle.backup');
        $backuptempdir = \make_backup_temp_directory("source$sourcecourseid");
        $file->extract_to_pathname($fp, $backuptempdir);
        // Free system resources.
        $file->delete();
    case 2:
        // 2.) Create new course and import.
        if (empty($targetcourseid)) {
            // Create a target.
            $category = \get_config('block_edupublisher', 'category');
            $targetcourse = (object)array();
            $targetcourse->category = intval($category);
            $targetcourse->fullname = get_string('pending_publication', 'block_edupublisher', array('courseid' => $sourcecourseid));
            $targetcourse->summary = '';
            $targetcourse->visible = 0;
            $targetcourse->shortname = '[' . $USER->id . '-' . date('YmdHis') . ']';
            $targetcourse->idnumber = '';
            $targetcourse->newsitems = 0;
            $targetcourse = \create_course($targetcourse);
            $targetcourseid = $targetcourse->id;
            $publish->targetcourseid = $targetcourseid;
            $DB->set_field('block_edupublisher_publish', 'targetcourseid', $publish->targetcourseid, array('id' => $publish->id));

            // we will do this using the block!
            /*
            $labeldata = array(
                'course' => $targetcourseid,
                'description' => get_string(
                    'publish_proceed_label',
                    'block_edupublisher',
                    [ 'sourcecourseid' => $sourcecourseid, 'wwwroot' => $CFG->wwwroot ]
                ),
                'name' => '',
                'section' => 0,
            );
            $label = \block_edupublisher\module_compiler::compile('label', $labeldata);
            $module = \block_edupublisher\module_compiler::create($label);
            */
        }
        // Grant the user editor permission to do the import.
        $roleid = \get_config('local_eduvidual', 'defaultroleteacher');
        \block_edupublisher\lib::course_manual_enrolments([$targetcourseid], [$USER->id], $roleid);
    case 3:
        // Ensure block_edupublisher is active in both courses.
        $targetcontext = \context_course::instance($targetcourseid);
        \block_edupublisher\lib::add_to_context($targetcontext);
        $sourcecontext = \context_course::instance($sourcecourseid);
        \block_edupublisher\lib::add_to_context($sourcecontext);

        // Must hold restoretargetimport in the current course
        require_capability('moodle/restore:restoretargetimport', $sourcecontext);

        $renderer = $PAGE->get_renderer('core', 'backup');

        $cancel = optional_param('cancel', '', PARAM_ALPHA);
        $contextid = optional_param('contextid', $targetcontext->id, PARAM_INT);
        $stage = optional_param('stage', \restore_ui::STAGE_DESTINATION, PARAM_INT);
        $restore = optional_param('restore', '', PARAM_ALPHANUM);

        // Prepare a progress bar which can display optionally during long-running
        // operations while setting up the UI.
        $slowprogress = new \core\progress\display_if_slow(get_string('preparingui', 'backup'));
        // Overall, allow 10 units of progress.
        $slowprogress->start_progress('', 10);
        // This progress section counts for loading the restore controller.
        $slowprogress->start_progress('', 1, 1);

        $filepath = "source$sourcecourseid";
        $_POST['filepath'] = $filepath;
        $_POST['stage'] = $stage;
        $_POST['sesskey'] = \sesskey();
        $_POST['targetid'] = $targetcourseid;
        $_POST['target'] = 1; // merge courses.

        if ($stage == \restore_ui::STAGE_DESTINATION) {
            $_POST['stage'] = \restore_ui::STAGE_SETTINGS;
            $_POST['target'] = \backup::TARGET_CURRENT_DELETING;
            $restore = \restore_ui::engage_independent_stage($stage, $contextid);

            $stage = \restore_ui::STAGE_SETTINGS;
        }

        if ($stage == \restore_ui::STAGE_SETTINGS) {
            $restoreid = optional_param('restore', false, PARAM_ALPHANUM);
            $rc = \restore_ui::load_controller($restoreid);

            if (!$rc) {
                $restore = \restore_ui::engage_independent_stage($stage / 2, $contextid);

                if ($restore->process()) {
                    $rc = new \restore_controller($restore->get_filepath(), $restore->get_course_id(), \backup::INTERACTIVE_YES,
                        \backup::MODE_SAMESITE, $USER->id, $restore->get_target(), null, \backup::RELEASESESSION_YES);
                }
            }
            if ($rc) {
                // check if the format conversion must happen first
                if ($rc->get_status() == \backup::STATUS_REQUIRE_CONV) {
                    $rc->convert();
                }

                $restore = new \restore_ui($rc, array('contextid' => $targetcontext->id));
            }

            $restore->save_controller();
            $_POST['contextid'] = $targetcontext->id;
            $_POST['restore'] = $restore->get_restoreid();
            $_POST['sectionid'] = 0; // $sectionid;
            $_POST['stage'] = \restore_ui::STAGE_SCHEMA;
            $_POST['sesskey'] = \sesskey();

            $stage = \restore_ui::STAGE_SCHEMA;
        }
        if ($stage >= \restore_ui::STAGE_SCHEMA) {
            $restoreid = optional_param('restore', false, PARAM_ALPHANUM);
            $rc = \restore_ui::load_controller($restoreid);

            if (!$rc) {
                $restore = \restore_ui::engage_independent_stage($stage / 2, $contextid);
                if ($restore->process()) {
                    $rc = new \restore_controller($restore->get_filepath(), $restore->get_course_id(), \backup::INTERACTIVE_YES,
                        \backup::MODE_SAMESITE, $USER->id, $restore->get_target(), null, \backup::RELEASESESSION_YES);
                }
            }
            if ($rc) {
                // check if the format conversion must happen first
                if ($rc->get_status() == \backup::STATUS_REQUIRE_CONV) {
                    $rc->convert();
                }

                $restore = new \restore_ui($rc, array('contextid' => $targetcontext->id));
            }
            $stage = \restore_ui::STAGE_REVIEW;
        }

        if ($stage == \restore_ui::STAGE_REVIEW) {
            // Nothing to do.
        }

        // End progress section for loading restore controller.
        $slowprogress->end_progress();

        // This progress section is for the 'process' function below.
        $slowprogress->start_progress('', 1, 9);

        // Depending on the code branch above, $restore may be a restore_ui or it may
        // be a restore_ui_independent_stage. Either way, this function exists.
        $restore->set_progress_reporter($slowprogress);
        $outcome = $restore->process();

        if (!$restore->is_independent() && $restore->enforce_changed_dependencies()) {
            debugging('Your settings have been altered due to unmet dependencies', DEBUG_DEVELOPER);
        }

        $loghtml = '';
        // Finish the 'process' progress reporting section, and the overall count.
        $slowprogress->end_progress();
        $slowprogress->end_progress();

        if (!$restore->is_independent()) {
            // Use a temporary (disappearing) progress bar to show the precheck progress if any.
            $precheckprogress = new \core\progress\display_if_slow(get_string('preparingdata', 'backup'));
            $restore->get_controller()->set_progress($precheckprogress);
            if ($restore->get_stage() == \restore_ui::STAGE_PROCESS && !$restore->requires_substage()) {
                try {
                    // Div used to hide the 'progress' step once the page gets onto 'finished'.
                    echo \html_writer::start_div('', array('id' => 'executionprogress'));
                    // Show the current restore state (header with bolded item).
                    echo $renderer->progress_bar($restore->get_progress_bar());
                    // Start displaying the actual progress bar percentage.
                    $restore->get_controller()->set_progress(new \core\progress\display());
                    // Prepare logger.
                    $logger = new \core_backup_html_logger($CFG->debugdeveloper ? \backup::LOG_DEBUG : \backup::LOG_INFO);
                    $restore->get_controller()->add_logger($logger);
                    // Do actual restore.
                    $restore->execute();

                    // Now remove empty sections.
                    $sections = $DB->get_records('course_sections', array('course' => $targetcourseid));
                    $nr = 0;
                    foreach ($sections as $section) {
                        if ($section->section > 0 && empty($section->sequence)) {
                            $DB->delete_records('course_sections', array('id' => $section->id));
                        } else {
                            $DB->set_field('course_sections', 'section', $nr++, array('id' => $section->id));
                        }
                    }
                    rebuild_course_cache($targetcourseid, true);

                    fulldelete($CFG->backuptempdir . '/' . $filepath);
                    $publish->importcompleted = time();
                    $DB->set_field('block_edupublisher_publish', 'importcompleted', $publish->importcompleted, array('id' => $publish->id));
                    redirect($PAGE->url->__toString());

                    echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
                        'type' => 'success',
                        'content' => get_string('publish_stage_confirm_button', 'block_edupublisher'),
                        'url' => $PAGE->url->__toString(),
                    ));

                    // Get HTML from logger.
                    if ($CFG->debugdisplay) {
                        $loghtml = $logger->get_html();
                    }
                    echo \html_writer::end_div();
                } catch (\Exception $e) {
                    $restore->cleanup();
                    throw $e;
                }
            } else {
                $restore->save_controller();
            }
        }

        echo $renderer->progress_bar($restore->get_progress_bar());

        if ($restore->get_stage() != \restore_ui::STAGE_PROCESS) {
            echo $restore->display($renderer);
            echo "<script> document.getElementById('id_submitbutton').value = '" . get_string("confirm") . "'; </script>";
        } else if ($restore->get_stage() == \restore_ui::STAGE_PROCESS && $restore->requires_substage()) {
            echo $restore->display($renderer);
        }

        $restore->destroy();
        unset($restore);

        // Display log data if there was any.
        if ($loghtml != '') {
            echo $renderer->log_display($loghtml);
        }
        break;
    case 4:
        // 3.) Enter metadata.
        if (empty($packageid)) {
            $sourcecourse = \get_course($sourcecourseid);
            $package = \block_edupublisher\lib::get_package_from_course($sourcecourseid);
        }
        $package->set($targetcourseid, 'course');
        $package->set_v2('filling_mode', \block_edupublisher\package::FILLING_MODE_EXPERT, 'default');

        $package->exacompetencies();

        $form = new \block_edupublisher\package_edit_form($package, []);
        if ($form->is_submitted()) {
            // Serialize form data and store to payload.
            $publish->payload = serialize($form->get_submitted_data());
            $DB->set_field('block_edupublisher_publish', 'payload', $publish->payload, array('id' => $publish->id));
        } elseif (!empty($publish->payload)) {
            $data = unserialize($publish->payload);
            $form->set_data($data);
        }

        if ($data = $form->get_data()) {
            $package->store_package($data);

            $publish->packageid = $package->id;
            $DB->set_field('block_edupublisher_publish', 'packageid', $publish->packageid, array('id' => $publish->id));

            echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
                'content' => get_string('publish_stage_confirm_button', 'block_edupublisher'),
                'type' => 'success',
                'url' => $PAGE->url->__toString(),
            ));

            redirect($PAGE->url->__toString());
        } else {
            $package->set_v2('publishas', 1, 'etapas');
            $package->set_v2('publishas', 1, 'eduthekneu');
            $form->set_data($package->get_form_data());
            $form->display();
        }
        break;
    case 5:
        // Deny the user editor permission afterthe import.
        $roleid = \get_config('local_eduvidual', 'defaultroleteacher');
        \block_edupublisher\lib::course_manual_enrolments([$targetcourseid], [$USER->id], $roleid, true);

        $DB->delete_records('block_edupublisher_publish', array('id' => $publish->id));

        $cache = cache::make('block_edupublisher', 'publish');
        $cache->delete("pending_publication_{$COURSE->id}");

        echo get_string('publish_stage_finish_text', 'block_edupublisher');
        $url = new \moodle_url('/blocks/edupublisher/pages/package.php', ['id' => $publish->packageid]);
        $label = get_string('publish_stage_finish_button', 'block_edupublisher');
        echo "<div style=\"text-align: center;\"><a href=\"$url\" class=\"btn btn-primary\">$label</a></div>\n";

        break;
}

echo $OUTPUT->footer();
