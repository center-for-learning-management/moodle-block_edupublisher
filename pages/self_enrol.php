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

if(!isloggedin() || isguestuser($USER)) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
        'type' => 'danger',
        'content' => get_string('guest_not_allowed', 'block_edupublisher'),
        'url' => $CFG->wwwroot . '/login/index.php',
    ));
    echo $OUTPUT->footer();
    die();
}


$courseid = required_param('id', PARAM_INT);
$package = block_edupublisher::get_package_by_courseid($courseid);
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
require_capability('block_edupublisher/canselfenrol', $context);
require_login($courseid);
$PAGE->set_heading($package->title);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

$PAGE->navbar->add(get_string('self_enrol', 'block_edupublisher'), $PAGE->url);
block_edupublisher::check_requirements();
block_edupublisher::print_app_header();

$defaultrolestudent = get_config('block_edupublisher', 'defaultrolestudent');
if (empty($defaultrolestudent)) {
    echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
        'type' => 'danger',
        'content' => get_string('defaultrolestudent:missing', 'block_edupublisher'),
    ));
} elseif (optional_param('confirm', 0, PARAM_BOOL) == 1) {
    // Do the enrolment and redirect.
    $course = $DB->get_record('course', array('id' => $package->course), '*', MUST_EXIST);
    $enrol = enrol_get_plugin('manual');
    if ($enrol === null) {
        return false;
    }
    $instances = enrol_get_instances($package->course, true);
    $manualinstance = null;
    foreach ($instances as $instance) {
        if ($instance->enrol == 'manual') {
            $manualinstance = $instance;
            break;
        }
    }

    if (empty($manualinstance->id)) {
        $instanceid = $enrol->add_default_instance($course);
        if ($instanceid === null) {
            $instanceid = $enrol->add_instance($course);
        }
        $instance = $DB->get_record('enrol', array('id' => $instanceid));
    }

    $enrol->enrol_user($instance, $USER->id, $defaultrolestudent);
    echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
        'type' => 'success',
        'content' => get_string('successfully_enrolled', 'block_edupublisher'),
        'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->course,
    ));
    redirect(new moodle_url('/course/view.php', array('id' => $package->course)));
} else {
    echo $OUTPUT->render_from_template(
        'block_edupublisher/self_enrol_confirm',
        $package
    );
}

block_edupublisher::print_app_footer();
