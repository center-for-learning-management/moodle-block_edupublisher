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
 * @copyright  2019 Zentrum für Lernmanagement (https://www.lernmanagement.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');

$courseid = required_param('id', PARAM_INT);
$unenrol = optional_param('unenrol', 0, PARAM_INT);
$package = new \block_edupublisher\package_by_courseid($courseid);
if (empty($package->course)) {
    // Show a warning that this is not a package.
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
        'type' => 'danger',
        'content' => get_string('no_such_package', 'block_edupublisher'),
        'url' => $CFG->wwwroot . '/course/view.php?id=' . $courseid,
    ));
    echo $OUTPUT->footer();
    die();
}

$context = context_course::instance($package->course);

$PAGE->set_url('/blocks/edupublisher/pages/self_enrol.php?id=' . $courseid);
require_capability('block/edupublisher:canselfenrol', $context);
require_login($courseid);
$PAGE->set_heading($package->title);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

$PAGE->navbar->add(get_string('self_enrol', 'block_edupublisher'), $PAGE->url);

\block_edupublisher\lib::check_requirements();
echo $OUTPUT->header();

// determine if we are a maintainer of this package!
$ismaintainer = \block_edupublisher\lib::is_maintainer(explode(',',$package->channels));
if (!empty($ismaintainer)) {
    $defaultroleteacher = get_config('block_edupublisher', 'defaultroleteacher');
}

$defaultrolestudent = get_config('block_edupublisher', 'defaultrolestudent');
if (empty($defaultrolestudent)) {
    echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
        'type' => 'danger',
        'content' => get_string('defaultrolestudent:missing', 'block_edupublisher'),
    ));
} elseif (optional_param('confirm', 0, PARAM_BOOL) == 1) {
    $canenrolasteacher = optional_param('canenrolasteacher', 0, PARAM_INT);
    $asstudent = optional_param('asstudent', 0, PARAM_BOOL);
    $asteacher = optional_param('asteacher', 0, PARAM_BOOL);
    require_once($CFG->dirroot . '/blocks/edupublisher/locallib.php');
    $reply = array();
    if (!empty($canenrolasteacher)) {
        // We distinguish between student and teacher role.
        if (!empty($asstudent)) block_edupublisher\lib::course_manual_enrolments(array($package->course), array($USER->id), $defaultrolestudent, !empty($unenrol));
        if (!empty($asteacher)) block_edupublisher\lib::course_manual_enrolments(array($package->course), array($USER->id), $defaultroleteacher, !empty($unenrol));
    } else {
        // There is only the student role.
        block_edupublisher\lib::course_manual_enrolments(array($package->course), array($USER->id), $defaultrolestudent, !empty($unenrol));
    }

    if (!empty($unenrol)) {
        // Log that we unenrolled from a package.
        require_once($CFG->dirroot . '/blocks/edupublisher/locallib.php');
        \block_edupublisher\lib::log_user_visit($package->id, 'unenrolled');
        echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
            'type' => 'success',
            'content' => get_string('successfully_unenrolled', 'block_edupublisher'),
            'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->course,
        ));
        redirect(new moodle_url('/course/view.php', array('id' => $package->course)));
    } else {
        // Log that we enrolled a package.
        require_once($CFG->dirroot . '/blocks/edupublisher/locallib.php');
        \block_edupublisher\lib::log_user_visit($package->id, 'enrolled');
        // Do the enrolment and redirect.
        echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
            'type' => 'success',
            'content' => get_string('successfully_enrolled', 'block_edupublisher'),
            'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->course,
        ));
        redirect(new moodle_url('/course/view.php', array('id' => $package->course)));
    }
} else {
    $package->unenrol = $unenrol;
    $package->canenrolasteacher = !empty($ismaintainer);
    echo $OUTPUT->render_from_template(
        'block_edupublisher/self_enrol_confirm',
        $package
    );
}

echo $OUTPUT->footer();
