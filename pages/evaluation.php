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
 * @copyright  2019 Zentrum für Lernmanagement
 * @author     Julia Laßnig & Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
require_once($CFG->dirroot . '/blocks/edupublisher/classes/etapas_evaluation_form.php');

$id = optional_param('id', 0, PARAM_INT);
$packageid = optional_param('packageid', 0, PARAM_INT);
$perma = optional_param('perma', '', PARAM_TEXT);

$package = \block_edupublisher::get_package($packageid, false);

$PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/evaluation.php', array('id' => $id, 'packageid' => $packageid, 'perma' => $perma)));

$context = \context_course::instance($package->course);
$PAGE->set_context($context);
$PAGE->set_title(get_string('etapas_evaluation', 'block_edupublisher'));
$PAGE->set_heading(get_string('etapas_evaluation', 'block_edupublisher'));
$PAGE->set_pagelayout('incourse');

block_edupublisher::print_app_header();

if (!has_capability('block/edupublisher:canseeevaluation', \context_system::instance())) {
    echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
        'type' => 'danger',
        'content' => get_string('no_permission', 'block_edupublisher'),
        'url' => $CFG->wwwroot . '/my',
    ));
} else {
    if (!empty($id)) {
        $backurl = new \moodle_url('/blocks/edupublisher/pages/evaluation.php', array('packageid' => $packageid));
        echo "<a href=\"$backurl\" class=\"btn btn-secondary\">" . get_string("back") . "</a>\n";
        echo "<h3>$package->title</h3>\n";

        $evaluation = $DB->get_record('block_edupublisher_evaluatio', array('packageid' => $packageid, 'id' => $id));
        if (!empty($evaluation->id)) {
            $fromuser = \core_user::get_user($evaluation->userid);
            $evaluation->userfullname = \fullname($fromuser);
            $usercontext = \context_user::instance($evaluation->userid);
            $evaluation->userpicture = new \moodle_url('/pluginfile.php/' . $usercontext->id . '/user/icon');
            $evaluation->userurl = new \moodle_url('/user/profile.php', array('id' => $evaluation->userid));
            $evaluation->linkurl = new \moodle_url('/blocks/edupublisher/pages/evaluation.php', array('packageid' => $packageid, 'id' => $evaluation->id));
            $evaluation->evaluated_on_readable = date("Y-m-d", $evaluation->evaluated_on);
            $evaluation->technology = get_string($evaluation->technology_application, 'block_edupublisher');
            echo $OUTPUT->render_from_template('block_edupublisher/evaluation_single', $evaluation);
        }
    } else {
        $backurl = new \moodle_url('/course/view.php', array('id' => $package->course));
        echo "<a href=\"$backurl\" class=\"btn btn-secondary\">" . get_string("back") . "</a>\n";
        echo "<h3>$package->title</h3>\n";

        if (has_capability('block/edupublisher:canevaluate', \context_system::instance())) {
            // Attach wwwroot of this site for the template.
            $package->wwwroot = $CFG->wwwroot;
            echo $OUTPUT->render_from_template('block_edupublisher/evaluation_createbtn', $package);
        }
        // List existing evaluations here.
        $evaluations = array_values($DB->get_records('block_edupublisher_evaluatio', array('packageid' => $packageid), 'timecreated DESC'));
        foreach ($evaluations as &$evaluation) {
            $fromuser = \core_user::get_user($evaluation->userid);
            $evaluation->userfullname = \fullname($fromuser);
            $usercontext = \context_user::instance($evaluation->userid);
            $evaluation->userpicture = new \moodle_url('/pluginfile.php/' . $usercontext->id . '/user/icon');
            $evaluation->userurl = new \moodle_url('/user/profile.php', array('id' => $evaluation->userid));
            $evaluation->linkurl = new \moodle_url('/blocks/edupublisher/pages/evaluation.php', array('packageid' => $packageid, 'id' => $evaluation->id));
            $evaluation->evaluated_on_readable = date("Y-m-d", $evaluation->timecreated); // $evaluation->evaluated_on
        }
        if (count($evaluations) > 0) {
            echo $OUTPUT->render_from_template('block_edupublisher/evaluation_list', array('evaluations' => $evaluations));
        }
    }
}

block_edupublisher::print_app_footer();
