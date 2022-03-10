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
$package = new \block_edupublisher\package($packageid, false);

$targetcourse   = $DB->get_record('course', array('id' => $targetcourseid), '*', MUST_EXIST);
$targetcontext  = \context_course::instance($targetcourse->id);
require_login($targetcourse);

$sourcecontext = \context_course::instance($package->get('course'));

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
        $importcourse = $DB->get_record('course', array('id' => $package->get('course')));
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
        'packageid' => $package->get('id'),
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
