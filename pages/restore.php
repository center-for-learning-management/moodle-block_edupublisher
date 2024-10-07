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
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');

// Restore of large courses requires extra memory. Use the amount configured
// in admin settings.
raise_memory_limit(MEMORY_EXTRA);

$cancel = optional_param('cancel', '', PARAM_ALPHA);
// The context we want to import TO.
$contextid = required_param('contextid', PARAM_INT);
$stage = optional_param('stage', restore_ui::STAGE_DESTINATION, PARAM_INT);
$restore = optional_param('restore', '', PARAM_ALPHANUM);

if (!empty($restore)) {
    $cache = \cache::make('block_edupublisher', 'restore');
    $co = $cache->get($restore);
    if (!empty($co)) {
        if (!empty($co['packageid'])) {
            $_POST['packageid'] = $co['packageid'];
        }
        if (!empty($co['sectionid'])) {
            $_POST['sectionid'] = $co['sectionid'];
        }
    }
}

$sectionid = optional_param('sectionid', 0, PARAM_INT);
$packageid = required_param('packageid', PARAM_INT);
$package = new \block_edupublisher\package($packageid, false);

// Determine if we are performing realtime for asynchronous backups.
$backupmode = backup::MODE_GENERAL;

list($context, $course, $cm) = get_context_info_array($contextid);

navigation_node::override_active_url(new moodle_url('/blocks/edupublisher/pages/restorefile.php', array('contextid' => $contextid)));
$PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/restore.php', array('contextid' => $contextid)));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');

require_login($course, null, $cm);
require_capability('moodle/restore:restorecourse', $context);

if (is_null($course)) {
    $coursefullname = $SITE->fullname;
    $courseshortname = $SITE->shortname;
    $courseurl = new moodle_url('/');
} else {
    $coursefullname = $course->fullname;
    $courseshortname = $course->shortname;
    $courseurl = course_get_url($course->id);
}

// Show page header.
$PAGE->set_title($courseshortname . ': ' . get_string('restore'));
$PAGE->set_heading($coursefullname);

$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

$renderer = $PAGE->get_renderer('core', 'backup');

// Prepare a progress bar which can display optionally during long-running
// operations while setting up the UI.
$slowprogress = new \core\progress\display_if_slow(get_string('preparingui', 'backup'));

// Overall, allow 10 units of progress.
$slowprogress->start_progress('', 10);

// This progress section counts for loading the restore controller.
$slowprogress->start_progress('', 1, 1);

/*
const STAGE_CONFIRM = 1;
const STAGE_DESTINATION = 2;
const STAGE_SETTINGS = 4;
const STAGE_SCHEMA = 8;
const STAGE_REVIEW = 16;
const STAGE_PROCESS = 32;
const STAGE_COMPLETE = 64;
*/

$filepath = \block_edupublisher\lib::prepare_restore($package);
$_POST['filepath'] = $filepath;
$_POST['stage'] = $stage;
$_POST['sesskey'] = \sesskey();
$_POST['targetid'] = $course->id;
$_POST['target'] = 1; // merge courses.

if ($stage == restore_ui::STAGE_DESTINATION) {
    $restore = restore_ui::engage_independent_stage($stage, $contextid);
    $url = new \moodle_url('/blocks/edupublisher/pages/restore.php', array(
        'contextid' => $contextid,
        'filepath' => $filepath,
        'packageid' => $packageid,
        'sectionid' => $sectionid,
        'sesskey' => \sesskey(),
        'stage' => restore_ui::STAGE_SETTINGS,
        'target' => 1,
        'targetid' => $course->id,
    ));
    redirect($url->__toString());
}

if ($stage == restore_ui::STAGE_SETTINGS) {
    $restoreid = optional_param('restore', false, PARAM_ALPHANUM);
    $rc = restore_ui::load_controller($restoreid);

    if (!$rc) {
        $restore = restore_ui::engage_independent_stage($stage / 2, $contextid);
        if ($restore->process()) {
            $rc = new restore_controller($restore->get_filepath(), $restore->get_course_id(), backup::INTERACTIVE_YES,
                $backupmode, $USER->id, $restore->get_target(), null, backup::RELEASESESSION_YES);
        }
    }
    if ($rc) {
        // check if the format conversion must happen first
        if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
            $rc->convert();
        }

        $restore = new restore_ui($rc, array('contextid' => $context->id));
    }

    $restore->save_controller();

    // Now that we have our restoreid, we store additional data to the cache.
    $cache = \cache::make('block_edupublisher', 'restore');
    $co = array(
        'packageid' => $packageid,
        'sectionid' => $sectionid,
    );
    $chk = $cache->set($restore->get_restoreid(), $co);

    $url = new \moodle_url('/blocks/edupublisher/pages/restore.php', array(
        'contextid' => $context->id,
        'packageid' => $package->get('id'),
        'restore' => $restore->get_restoreid(),
        'sectionid' => $sectionid,
        'stage' => restore_ui::STAGE_SCHEMA,
        'sesskey' => \sesskey(),
    ));

    redirect($url->__toString());
}
if ($stage >= restore_ui::STAGE_SCHEMA) {
    $restoreid = optional_param('restore', false, PARAM_ALPHANUM);
    $rc = restore_ui::load_controller($restoreid);

    if (!$rc) {
        $restore = restore_ui::engage_independent_stage($stage / 2, $contextid);
        if ($restore->process()) {
            $rc = new restore_controller($restore->get_filepath(), $restore->get_course_id(), backup::INTERACTIVE_YES,
                $backupmode, $USER->id, $restore->get_target(), null, backup::RELEASESESSION_YES);
        }
    }
    if ($rc) {
        // check if the format conversion must happen first
        if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
            $rc->convert();
        }

        $restore = new restore_ui($rc, array('contextid' => $context->id));
    }
}


if ($stage == restore_ui::STAGE_REVIEW) {
    $url = new \moodle_url('/blocks/edupublisher/pages/restore.php', array(
        'contextid' => $contextid,
        'stage' => $stage,
        'restore' => $restore->get_restoreid(),
        'sesskey' => \sesskey(),
    ));


}

if (empty($cancel)) {
    // Do not print the header if user cancelled the process, as we are going to redirect the user.
    echo $OUTPUT->header();
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
    if ($restore->get_stage() == restore_ui::STAGE_PROCESS && !$restore->requires_substage() && $backupmode != backup::MODE_ASYNC) {
        try {
            // Div used to hide the 'progress' step once the page gets onto 'finished'.
            echo html_writer::start_div('', array('id' => 'executionprogress'));
            // Show the current restore state (header with bolded item).
            echo $renderer->progress_bar($restore->get_progress_bar());
            // Start displaying the actual progress bar percentage.
            $restore->get_controller()->set_progress(new \core\progress\display());
            // Prepare logger.
            $logger = new core_backup_html_logger($CFG->debugdeveloper ? backup::LOG_DEBUG : backup::LOG_INFO);
            $restore->get_controller()->add_logger($logger);
            // Do actual restore.

            // Determine if sectionid is the first in course.
            if (!empty($sectionid)) {
                // We got a sectionid, so we want the contents in a specific area of the course!
                // Get the section number based on sectionid.
                $section = $DB->get_record('course_sections', array('course' => $course->id, 'id' => $sectionid));
                $sectionnr = intval($section->section);
                // NEW BEHAVIOUR: move sections instead of move modules.
                // new sections should be moved AFTER $section
                $sections_old = $DB->get_records('course_sections', array('course' => $course->id));
                // Store the current number of the section we want to add everything AFTER.

                $moveaftersectionnumber = $sections_old[$sectionid]->section;

                $oldsectionids_beforeimport = array();

                foreach ($sections_old as $section_old) {
                    if ($section_old->section <= $moveaftersectionnumber) {
                        $oldsectionids_beforeimport[] = $section_old->id;
                    }
                }

                // 1.) We have to create empty sections at the beginning of the course,
                //     where the new contents can be imported to.
                $sections_import = array_values($DB->get_records('course_sections', array('course' => $package->get('course'))));
                $createdsectionids = array();

                $sql = "UPDATE {course_sections}
                            SET section=section+?
                            WHERE course=?
                            ORDER BY section DESC";

                $DB->execute($sql, array(count($sections_import), $course->id));
                for ($a = 0; $a < count($sections_import); $a++) {
                    $seco = (object)array(
                        'course' => $course->id,
                        'section' => $a,
                        'name' => $sections_import[$a]->name,
                        // Keep summary empty, moodle will restore it from the backup
                        // but if summary is filled here, the incorrect summary (eg. linked Images don't work) will be restored.
                        'summary' => '', // $sections_import[$a]->summary,
                        'summaryformat' => $sections_import[$a]->summaryformat,
                        'sequence' => '',
                        'visible' => $sections_import[$a]->visible,
                        'availability' => $sections_import[$a]->availability,
                        'timemodified' => time(),
                    );
                    $createdsectionids[] = $DB->insert_record('course_sections', $seco);
                }
                rebuild_course_cache($course->id, true);

                // 2.) Do the restore.
                $restore->execute();

                // 3.) Now we delete created sections if they have an empty sequence (nothing was imported).
                /*
                $sql = "DELETE FROM {course_sections}
                            WHERE course=?
                                AND id IN (?)
                                AND (name IS NULL OR name = '')
                                AND (sequence IS NULL OR sequence = '')
                                AND (summary IS NULL or summary = '')";
                */

                // 4.) Re-order all sections.
                $sections_new = $DB->get_records('course_sections', array('course' => $course->id), 'section ASC');
                //     Increment section numbering, so that we have no problem with re-ordering.
                $sql = "UPDATE {course_sections}
                            SET section=section+?
                            WHERE course=?
                            ORDER BY section DESC";
                $DB->execute($sql, array(count($sections_new), $course->id));
                //     Start re-ordering with our old sections that are BEFORE the new content.
                $newposition = 0;
                foreach ($oldsectionids_beforeimport as $s) {
                    $DB->set_field('course_sections', 'section', $newposition++, array('id' => $s));
                }

                foreach ($sections_new as $snew) {
                    if (!in_array($snew->id, $oldsectionids_beforeimport)) {
                        $DB->set_field('course_sections', 'section', $newposition++, array('id' => $snew->id));
                    }
                }

                rebuild_course_cache($course->id, true);
            } else {
                // We just import - do nothing else.
                $restore->execute();
            }
            fulldelete($CFG->backuptempdir . '/' . $filepath);

            // Log that we cloned a package.
            require_once($CFG->dirroot . '/blocks/edupublisher/locallib.php');
            \block_edupublisher\lib::log_user_visit($package->get('id'), 'cloned');

            // log the usage
            $DB->insert_record('block_edupublisher_uses', (object)array(
                'userid' => $USER->id,
                'package' => $package->get('id'),
                'targetcourse' => $course->id,
                'created' => time(),
            ));

            // Get HTML from logger.
            if ($CFG->debugdisplay) {
                $loghtml = $logger->get_html();
            }
            // Hide this section because we are now going to make the page show 'finished'.
            echo html_writer::end_div();
            echo html_writer::script('document.getElementById("executionprogress").style.display = "none";');
        } catch (Exception $e) {
            $restore->cleanup();
            throw $e;
        }
    } else {
        $restore->save_controller();
    }
}

echo $renderer->progress_bar($restore->get_progress_bar());

if ($restore->get_stage() != restore_ui::STAGE_PROCESS) {
    echo $restore->display($renderer);
} else if ($restore->get_stage() == restore_ui::STAGE_PROCESS && $restore->requires_substage()) {
    echo $restore->display($renderer);
}

$restore->destroy();
unset($restore);

// Display log data if there was any.
if ($loghtml != '') {
    echo $renderer->log_display($loghtml);
}

echo $OUTPUT->footer();
