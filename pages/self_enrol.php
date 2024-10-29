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
$prec = $DB->get_record('block_edupublisher_packages', ['course' => $courseid]);

if (empty($prec->id)) {
    // Show a warning that this is not a package.
    $url = new \moodle_url('/course/view.php', ['id' => $courseid]);
    throw new \moodle_exception('no_such_package', 'block_edupublisher', $url->__toString());
}

$package = new \block_edupublisher\package($prec->id);
$context = \context_course::instance($courseid);

$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/blocks/edupublisher/pages/self_enrol.php?id=' . $courseid);
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

$PAGE->set_heading($package->get('title'));
$PAGE->navbar->add(get_string('self_enrol', 'block_edupublisher'), $PAGE->url);

require_login($courseid);
if (!isloggedin() || isguestuser()) {
    $SESSION->wantsurl = $PAGE->url->__toString();
    $login = new \moodle_url('/login/index.php');
    redirect($login);
}
require_capability('block/edupublisher:canselfenrol', $context);


\block_edupublisher\lib::check_requirements();

// determine if we are a maintainer of this package!
$ismaintainer = \block_edupublisher\permissions::is_maintainer(explode(',', $package->get('channels')));
if (!empty($ismaintainer)) {
    $defaultroleteacher = get_config('block_edupublisher', 'defaultroleteacher');
}

$defaultrolestudent = get_config('block_edupublisher', 'defaultrolestudent');
$rolestudent = get_config('block_edupublisher', 'groupsrolestudent');
// Attention: roleteacher is a teacher without editing permissions. This role is assigned by the group-feature.
$roleteacher = get_config('block_edupublisher', 'groupsroleteacher');
if (empty($defaultrolestudent)) {
    echo $OUTPUT->header();
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
        if (!empty($asstudent))
            \block_edupublisher\lib::course_manual_enrolments([$package->courseid], array($USER->id), $defaultrolestudent, !empty($unenrol));
        if (!empty($asstudent))
            \block_edupublisher\lib::course_manual_enrolments([$package->courseid], array($USER->id), $rolestudent, !empty($unenrol));
        if (!empty($asstudent))
            \block_edupublisher\lib::course_manual_enrolments([$package->courseid], array($USER->id), $roleteacher, !empty($unenrol));
        // Attention: asteacher means this person is a maintainer!!!
        if (!empty($asteacher))
            \block_edupublisher\lib::course_manual_enrolments([$package->courseid], array($USER->id), $defaultroleteacher, !empty($unenrol));
    } else {
        // There is only the student role.
        \block_edupublisher\lib::course_manual_enrolments([$package->courseid], array($USER->id), $defaultrolestudent, !empty($unenrol));
        \block_edupublisher\lib::course_manual_enrolments([$package->courseid], array($USER->id), $rolestudent, !empty($unenrol));
        if (!empty($unenrol)) {
            // Only change roleteacher if this is an unenrolment.
            \block_edupublisher\lib::course_manual_enrolments([$package->courseid], array($USER->id), $roleteacher, !empty($unenrol));
        }
    }

    if (!empty($unenrol)) {
        // Log that we unenrolled from a package.
        require_once($CFG->dirroot . '/blocks/edupublisher/locallib.php');
        \block_edupublisher\lib::log_user_visit($package->id, 'unenrolled');
        //redirect(new moodle_url('/course/view.php', array('id' => $package->courseid)));
        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
            'type' => 'success',
            'content' => get_string('successfully_unenrolled', 'block_edupublisher'),
            'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->courseid,
        ));
    } else {
        // Log that we enrolled a package.
        require_once($CFG->dirroot . '/blocks/edupublisher/locallib.php');
        \block_edupublisher\lib::log_user_visit($package->id, 'enrolled');
        // Do the enrolment and redirect.
        //redirect(new moodle_url('/course/view.php', array('id' => $package->courseid)));
        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
            'type' => 'success',
            'content' => get_string('successfully_enrolled', 'block_edupublisher'),
            'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->courseid,
        ));
    }
} else {
    $package->set($unenrol, 'unenrol');
    $package->set(!empty($ismaintainer) ? 1 : 0, 'canenrolasteacher');
    $groups = array_values(\groups_get_all_groups($package->courseid, $USER->id));
    echo $OUTPUT->header();
    if (count($groups) == 0) {
        echo $OUTPUT->render_from_template(
            'block_edupublisher/self_enrol_confirm',
            $package->get_flattened()
        );
    } else {
        $url = new \moodle_url('/blocks/edupublisher/pages/groups.php', ['id' => $package->id]);
        echo $OUTPUT->render_from_template(
            'block_edupublisher/self_enrol_isingroup',
            [
                'groups' => $groups,
                'multiple' => (count($groups) > 1) ? 1 : 0,
                'url' => $url->__toString(),
            ]
        );
    }


}

echo $OUTPUT->footer();
