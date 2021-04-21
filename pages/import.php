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
require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

// Restore of large courses requires extra memory. Use the amount configured
// in admin settings.
raise_memory_limit(MEMORY_EXTRA);

// Import a certain package to a course, if sectionid given move it to that position

$assubcourse    = optional_param('assubcourse', 1, PARAM_INT);
$cancel         = optional_param('cancel', '', PARAM_ALPHA);
$packageid      = required_param('package', PARAM_INT);
$sectionid      = optional_param('section', 0, PARAM_INT);
$stage          = optional_param('stage', restore_ui::STAGE_DESTINATION, PARAM_INT);
$targetcourseid = required_param('course', PARAM_INT);

$filepath       = optional_param('filepath', '', PARAM_ALPHANUM);

// Load package and importcourse + context
$package = block_edupublisher::get_package($packageid, false);

$targetcourse   = $DB->get_record('course', array('id'=>$targetcourseid), '*', MUST_EXIST);
$targetcontext  = \context_course::instance($targetcourse->id);
require_login($targetcourse);

$sourcecontext = \context_course::instance($package->course);

$fs = \get_file_storage();
$pathnamehash = $fs->get_pathname_hash($sourcecontext->id, 'block_edupublisher', 'coursebackup', 0, '/', 'coursebackup.mbz');
$file = $fs->get_file($sourcecontext->id, 'block_edupublisher', 'coursebackup', 0, '/', 'coursebackup.mbz');

$PAGE->set_title(get_string('import'));
$PAGE->set_heading(get_string('import'));
$PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/import.php', array('package' => $packageid, 'course'=>$targetcourseid, 'section' => $sectionid)));
$PAGE->set_context($targetcontext);
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

if ($file) {
    // Ensure we have a restore target
    if (!empty($assubcourse) && !empty(\get_config('block_edupublisher', 'allowsubcourses'))) {
        // Do some checks.
        $categorysubcourse = \get_config('block_edupublisher', 'categorysubcourses');
        if (empty($categorysubcourse)) {
            echo $OUTPUT->header();
            $alert = array(
                'content' => get_string('categorysubcourses:missing', 'block_edupublisher'),
                'type' => 'danger',
            );
            echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
            echo $OUTPUT->footer();
            exit;
        }
        $defaultroleteacher = \get_config('block_edupublisher', 'defaultroleteacher');
        if (empty($defaultroleteacher)) {
            echo $OUTPUT->header();
            $alert = array(
                'content' => get_string('defaultroleteacher:missing', 'block_edupublisher'),
                'type' => 'danger',
            );
            echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
            echo $OUTPUT->footer();
            exit;
        }

        $metaenrol = \enrol_get_plugin('meta');
        if (empty($metaenrol)) {
            echo $OUTPUT->header();
            $alert = array(
                'content' => get_string('enrolment:meta:notinstalled', 'block_edupublisher'),
                'type' => 'danger',
            );
            echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
            echo $OUTPUT->footer();
            exit;
        }

        /*
        if (!$metaenrol->can_add_instance($targetcourse->id)) {
            echo $OUTPUT->header();
            $alert = array(
                'content' => get_string('enrolment:meta:notactive', 'block_edupublisher'),
                'type' => 'danger',
            );
            echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
            echo $OUTPUT->footer();
            exit;
        }
        */

        // Create the course.
        $importcourse = $DB->get_record('course', array('id' => $package->course));
        $subcourse = $importcourse;
        $subcourse->id = 0;
        $subcourse->category = intval($categorysubcourse);
        $subcourse->visible = 1;
        $subcourse->fullname = get_string('subcourse', 'block_eduvidual', array('fullname' => $subcourse->fullname));
        $subcourse->shortname = '[' . $USER->id . '-' . date('YmdHis') . ']';
        $subcourse->idnumber = '';
        $subcourse = \create_course($subcourse);

        // Manually enrol teacher.
        \block_edupublisher\lib::course_manual_enrolments(array($subcourse->id), array($USER->id), $defaultroleteacher, 0);

        // Map course enrolments using course_meta_link.
        $metadata = array(
            'customint1' => $subcourse->id,
            'customint2' => 0,
        );
        $metaenrol->add_instance($targetcourse, $metadata);

        // Create the subcourse-module.
        $section = $DB->get_record('course_sections', array('id' => $sectionid));
        $scdata = (object)array(
            'blankwindow' => 1,
            'course' => $targetcourse->id,
            'fetchpercentage' => 0, // 0 ... original values, 1 ... percentage
            'refcourse' => $subcourse->id,
            'section' => $section->section,
        );
        $scdefaults = (object)array();
        $item = \block_edupublisher\module_compiler::compile('subcourse', $scdata, $scdefaults);
        \block_edupublisher\module_compiler::create($item);

        // Set targetcontext and sectionid.
        $targetcontext = \context_course::instance($subcourse->id);
        $sectionid = 0;
    }
    require_capability('moodle/restore:restoretargetimport', $targetcontext);

    // Ensure it is extracted.
    \block_edupublisher\lib::prepare_restore($package);
    $url = new \moodle_url('/blocks/edupublisher/pages/restore.php', array(
        'contextid' => $targetcontext->id,
        'contenthash' => $file->get_contenthash(),
        'pathnamehash' => $pathnamehash,
        'packageid' => $package->id,
        'sectionid' => $sectionid,
    ));
    redirect($url->__toString());
    exit;
} else {
    echo $OUTPUT->header();
    $alert = array(
        'content' => get_string('coursebackup:notfound', 'block_edupublisher'),
        'type' => 'danger',
    );
    echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
    echo $OUTPUT->footer();
    exit;
}

die();
// Set up the page
$PAGE->set_title(get_string('import'));
$PAGE->set_heading(get_string('import'));
$PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/import.php', array('package' => $packageid, 'course'=>$targetcourseid, 'section' => $sectionid)));
$PAGE->set_context($targetcontext);
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

block_edupublisher::check_requirements();

if (empty($filepath)) {
    $filepath = \block_edupublisher\lib::prepare_restore($package);
    if (empty($filepath)) {
        $alert = array(
            'content' => get_string('coursebackup:notfound', 'block_edupublisher'),
            'type' => 'danger',
        );
        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
        echo $OUTPUT->footer();
        exit;
    }

    $url = new \moodle_url('/blocks/edupublisher/pages/import.php', array(
        'assubcourse' => $assubcourse,
        'cancel'      => $cancel,
        'course'      => $targetcourseid,
        'filepath'    => $filepath,
        'package'     => $package->id,
        'section'     => $sectionid,
        'sesskey'     => \sesskey(),
        'stage'       => 4, // stage settings
        'target'      => 1, // Merge course with backup
        'targetid'    => $targetcourse->id,
    ));
    redirect($url->__toString());
}

if (!empty($filepath)) {
    // Create the restore controller.
    $rc = new \restore_controller($filepath, $targetcourseid,
            backup::INTERACTIVE_YES, backup::MODE_IMPORT, $USER->id,
            backup::TARGET_EXISTING_ADDING);

    foreach ($settings as $settingname => $value) {
        $setting = $rc->get_plan()->get_setting($settingname);
        if ($setting->get_status() == \base_setting::NOT_LOCKED) {
            $rc->get_plan()->get_setting($settingname)->set_value($value);
        }
    }


    if (empty($assubcourse)) {
        // Require editing-permissions in targetcourse
        require_capability('moodle/restore:restoretargetimport', $targetcontext);
    } else {
        // Create new course
        $categorysubcourse = \get_config('block_edupublisher', 'categorysubcourse');
        if (empty($categorysubcourse)) {
            $alert = array(
                'content' => get_string('categorysubcourse:missing', 'block_edupublisher'),
                'type' => 'danger',
            );
            echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
            echo $OUTPUT->footer();
            exit;
        }
        $defaultroleteacher = \get_config('block_edupublisher', 'defaultroleteacher');

        $importcourse = $DB->get_record('course', array('id' => $package->course));
        $targetcourse = $importcourse;
        $targetcourse->id = 0;
        $targetcourse->category = intval($categorysubcourse);
        $targetcourse->visible = 1;
        $targetcourse->shortname = '[' . $USER->id . '-' . date('YmdHis') . ']';
        $targetcourse->idnumber = '';
        $targetcourse = \create_course($targetcourse);

        \block_edupublisher\lib::course_manual_enrolments(array($targetcourse->id), array($USER->id), $defaultroleteacher);
        // Add as subcourse module to original course.
        $item = \block_edupublisher\module_compiler::compile('subcourse', $data, $defaults);
    }

    // Get the section number based on sectionid.
    $section = $DB->get_record('course_sections', array('course' => $targetcourseid, 'id' => $sectionid));
    $sectionnr = intval($section->section);
    // NEW BEHAVIOUR: move sections instead of move modules.
    // new sections should be moved AFTER $section
    $sections_old = $DB->get_records('course_sections', array('course' => $targetcourse->id));
    // Store the current number of the section we want to add everything AFTER.

    $moveaftersectionnumber = $sections_old[$sectionid]->section;

    $oldsectionids_beforeimport = array();

    foreach ($sections_old AS $section_old) {
        if ($section_old->section <= $moveaftersectionnumber) {
            $oldsectionids_beforeimport[] = $section_old->id;
        }
    }

    // 1.) We have to create empty sections at the beginning of the course,
    //     where the new contents can be imported to.
    $sections_import = array_values($DB->get_records('course_sections', array('course' => $importcourse->id)));
    $createdsectionids = array();

    $DB->execute("UPDATE {course_sections} SET section=section+? WHERE course=? ORDER BY section DESC", array(count($sections_import), $targetcourse->id));
    for($a = 0; $a < count($sections_import); $a++) {
        $seco = (object) array(
            'course' => $targetcourse->id,
            'section' => $a,
            'name' => $sections_import[$a]->name,
            'summary' => $sections_import[$a]->summary,
            'summaryformat' => $sections_import[$a]->summaryformat,
            'sequence' => '',
            'visible' => $sections_import[$a]->visible,
            'availability' => $sections_import[$a]->availability,
            'timemodified' => time(),
        );
        $createdsectionids[] = $DB->insert_record('course_sections', $seco);
    }
    rebuild_course_cache($targetcourse->id, true);

    // 2.) Do the import.
    $settings = [
        'overwrite_conf' => false,
        'users' => false,
        'keep_roles_and_enrolments' => false,
        'keep_groups_and_groupings' => false,
    ];

    try {
        $rc = new \restore_controller($backuptempdir, $targetcourseid,
                backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id,
                backup::TARGET_EXISTING_ADDING);

        foreach ($settings as $settingname => $value) {
            $setting = $rc->get_plan()->get_setting($settingname);
            if ($setting->get_status() == \base_setting::NOT_LOCKED) {
                $rc->get_plan()->get_setting($settingname)->set_value($value);
            }
        }

        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();
        // Delete the temp directory now
        fulldelete($CFG->backuptempdir . '/' . $backuptempdir);
    } catch(Exception $e) {
        echo 'Exception: ' . $e->getMessage();
        echo $e->getTraceAsString();
    }

    // 3.) Now we delete created sections if they have an empty sequence (nothing was imported).
    $sql = "DELETE FROM {course_sections}
                WHERE course=?
                    AND id IN (?)
                    AND (name IS NULL OR name = '')
                    AND (sequence IS NULL OR sequence = '')
                    AND (summary IS NULL or summary = '')";

    // 3.) Re-order all sections.
    $sections_new = $DB->get_records('course_sections', array('course' => $targetcourse->id), 'section ASC');
    //     Increment section numbering, so that we have no problem with re-ordering.
    $sql = "UPDATE {course_sections}
                SET section=section+?
                WHERE course=?
                ORDER BY section DESC";
    $DB->execute($sql, array(count($sections_new), $targetcourse->id));
    //     Start re-ordering with our old sections that are BEFORE the new content.
    $newposition = 0;
    foreach ($oldsectionids_beforeimport AS $s) {
        $DB->set_field('course_sections', 'section', $newposition++, array('id' => $s));
    }

    foreach ($sections_new AS $snew) {
        if (!in_array($snew->id, $oldsectionids_beforeimport)) {
            $DB->set_field('course_sections', 'section', $newposition++, array('id' => $snew->id));
        }
    }

    rebuild_course_cache($targetcourse->id, true);

    // Log that we cloned a package.
    require_once($CFG->dirroot . '/blocks/edupublisher/locallib.php');
    \block_edupublisher\lib::log_user_visit($packageid, 'cloned');

    echo $OUTPUT->notification(get_string('coursebackup:importcomplete', 'block_edupublisher'), 'notifysuccess');
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php?id=' . $targetcourse->id));

    $redirect = $CFG->wwwroot . '/course/view.php?id=' . $targetcourse->id;
}

block_edupublisher::print_app_footer();
if (!empty($redirect)) { redirect($redirect); }
