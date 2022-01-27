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
 * @copyright  2022 Center for Learning Management (http://www.lernmanagement.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');

$id = required_param('id', PARAM_INT);
$package = block_edupublisher::get_package($id, true);
$context = context_course::instance($package->course);
// Must pass login
$PAGE->set_url('/blocks/edupublisher/pages/groups.php?id=' . $id);
require_login($package->course);

if (!empty(optional_param('switchaccount', 0, PARAM_INT))) {
    require_logout();
    redirect("$CFG->wwwroot/login/index.php");
}

$PAGE->set_context($context);
$PAGE->set_title(get_string('groups:create', 'block_edupublisher'));
$PAGE->set_heading(get_string('groups:create', 'block_edupublisher'));
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

//$PAGE->navbar->add(get_string('groups:create', 'block_edupublisher'), new moodle_url('/blocks/edupublisher/pages/package.php', array('id' => $package->id)));
$PAGE->navbar->add(get_string('groups:create', 'block_edupublisher'), $PAGE->url);

echo $OUTPUT->header();

if (!\block_edupublisher\lib::can_create_groups()) {
    $params = (object) [
        'id' => $id,
        'maildomains' => explode("\n", get_config('block_edupublisher', 'groupsdomains')),
    ];
    $params->hasmaildomains = (count($params->maildomains) > 0) ? 1 : 0;
    echo $OUTPUT->render_from_template('block_edupublisher/groups_nopermission', $params);
} else {
    $rolestudent = get_config('block_edupublisher', 'groupsrolestudent');
    $roleteacher = get_config('block_edupublisher', 'groupsroleteacher');

    if (!empty(optional_param('edupublisher_group_name', '', PARAM_TEXT))) {
        // Set group mode of course
        $DB->set_field('course', 'groupmode', 1, [ 'id' => $package->course ]);

        $data = (object) [
            'courseid' => $package->course,
            'name' => optional_param('edupublisher_group_name', '', PARAM_TEXT),
        ];
        $newgroup = \groups_create_group($data, false, null);

        if (!empty($newgroup)) {
            echo $OUTPUT->render_from_template('block_edupublisher/alert', [
                'content' => get_string('groups:create:success', 'block_edupublisher', [ 'name' => $data->name ]),
                'type' => 'success',
            ]);
            if (!\block_edupublisher\lib::has_role($context, $roleteacher, $USER)) {
                \block_edupublisher\lib::course_manual_enrolments([ $package->course ], $USER->id, $roleteacher);
            }
            \groups_add_member($newgroup, $USER->id);
            require_once("$CFG->dirroot/blocks/enrolcode/locallib.php");
            $expiration = strtotime("31.12.2099");
            \block_enrolcode_lib::create_code($package->course, $rolestudent, $newgroup, true, $expiration, 0, 0);
            \block_enrolcode_lib::create_code($package->course, $roleteacher, $newgroup, true, $expiration, 0, 0);
        } else {
            echo $OUTPUT->render_from_template('block_edupublisher/alert', [
                'content' => get_string('groups:create:error', 'block_edupublisher', [ 'name' => $data->name ]),
                'type' => 'danger',
            ]);
        }
    }

    echo $OUTPUT->render_from_template('block_edupublisher/groups_create', []);
    $groups = \groups_get_all_groups($package->course, $USER->id);
    if (!empty(optional_param('deletegroup', 0, PARAM_INT))) {
        foreach ($groups as $group) {
            if ($group->id == optional_param('deletegroup', 0, PARAM_INT)) {
                \groups_delete_group($group);
                $groups = \groups_get_all_groups($package->course, $USER->id);
                break;
            }
        }
    }

    foreach ($groups as &$group) {
        $stud = $DB->get_record('block_enrolcode', [ 'courseid' => $package->course, 'groupid' => $group->id, 'roleid' => $rolestudent ]);
        $teac = $DB->get_record('block_enrolcode', [ 'courseid' => $package->course, 'groupid' => $group->id, 'roleid' => $roleteacher ]);
        if (!empty($stud->code)) {
            $group->codestudent = $stud->code;
            $group->urlstudent = "$CFG->wwwroot/blocks/enrolcode/enrol.php?code=$stud->code";
            $group->qrstudent = "$CFG->wwwroot/blocks/enrolcode/pix/qr.php?format=base64&txt=" . base64_encode($group->urlstudent);
        }
        if (!empty($teac->code)) {
            $group->codeteacher = $teac->code;
            $group->urlteacher = "$CFG->wwwroot/blocks/enrolcode/enrol.php?code=$teac->code";
            $group->qrteacher = "$CFG->wwwroot/blocks/enrolcode/pix/qr.php?format=base64&txt=" . base64_encode($group->urlteacher);
        }
        $group->urldelete = "$CFG->wwwroot/blocks/edupublisher/pages/groups.php?id=$id&deletegroup=$group->id";
    }

    echo $OUTPUT->render_from_template('block_edupublisher/groups_list', [
        'groups' => array_values($groups)
    ]);
}

echo $OUTPUT->footer();
