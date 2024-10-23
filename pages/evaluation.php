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

$package = new \block_edupublisher\package($packageid, false);

$PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/evaluation.php', array('id' => $id, 'packageid' => $packageid, 'perma' => $perma)));

$context = \context_course::instance($package->courseid);
$PAGE->set_context($context);
$title = !empty($package->id) ? $package->get('title') : get_string('etapas_evaluation', 'block_edupublisher');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('incourse');

$PAGE->navbar->add(
    get_string('resource_catalogue', 'block_edupublisher'),
    new moodle_url(
        '/blocks/edupublisher/pages/search.php',
        array()
    )
);
$PAGE->navbar->add(
    $package->get('title'),
    new moodle_url(
        '/course/view.php',
        array(
            'id' => $package->courseid,
        )
    )
);
$PAGE->navbar->add(
    get_string('etapas_evaluation', 'block_edupublisher'),
    new \moodle_url(
        '/blocks/edupublisher/pages/evaluation.php',
        array(
            'packageid' => $package->id,
        )
    )
);

if (!has_capability('block/edupublisher:canseeevaluation', \context_system::instance())) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
        'type' => 'danger',
        'content' => get_string('permission_denied', 'block_edupublisher'),
        'url' => $CFG->wwwroot . '/my',
    ));
} else {
    if (!empty($id)) {
        $evaluation = $DB->get_record('block_edupublisher_evaluatio', array('packageid' => $packageid, 'id' => $id));
        if (!empty($evaluation->id)) {
            $fromuser = \core_user::get_user($evaluation->userid);
            $evaluation->userfullname = \fullname($fromuser);
            $PAGE->navbar->add(
                get_string(
                    'evaluation_by',
                    'block_edupublisher',
                    array(
                        'fullname' => $evaluation->userfullname,
                    )
                ), new \moodle_url(
                    '/blocks/edupublisher/pages/evaluation.php',
                    array(
                        'packageid' => $package->id,
                        'id' => $id,
                    )
                )
            );
            echo $OUTPUT->header();
            $usercontext = \context_user::instance($evaluation->userid);
            $evaluation->userpicture = new \moodle_url(
                '/pluginfile.php/' . $usercontext->id . '/user/icon'
            );
            $evaluation->userurl = new \moodle_url(
                '/user/profile.php',
                array(
                    'id' => $evaluation->userid,
                )
            );
            $evaluation->linkurl = new \moodle_url(
                '/blocks/edupublisher/pages/evaluation.php',
                array(
                    'packageid' => $packageid,
                    'id' => $evaluation->id,
                )
            );
            $evaluation->evaluated_on_readable = date("Y-m-d", $evaluation->evaldate);
            $evaluation->technology = get_string($evaluation->technology_application, 'block_edupublisher');
            echo $OUTPUT->render_from_template('block_edupublisher/evaluation_single', $evaluation);
        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
                'type' => 'danger',
                'content' => get_string('invalid_evaluation', 'block_edupublisher'),
                'url' => $CFG->wwwroot . '/my',
            ));
        }
    } else {
        echo $OUTPUT->header();

        // List existing evaluations here.
        $canevaluate = has_capability('block/edupublisher:canevaluate', \context_system::instance());
        $evaluations = array_values($DB->get_records('block_edupublisher_evaluatio', array('packageid' => $packageid), 'timecreated DESC'));
        foreach ($evaluations as &$evaluation) {
            $fromuser = \core_user::get_user($evaluation->userid);
            $evaluation->userfullname = \fullname($fromuser);
            $usercontext = \context_user::instance($evaluation->userid);
            $evaluation->userpicture = new \moodle_url('/pluginfile.php/' . $usercontext->id . '/user/icon');
            $evaluation->userurl = new \moodle_url(
                '/user/profile.php',
                array(
                    'id' => $evaluation->userid,
                )
            );
            $evaluation->linkurl = new \moodle_url(
                '/blocks/edupublisher/pages/evaluation.php',
                array(
                    'packageid' => $packageid,
                    'id' => $evaluation->id,
                )
            );
            $evaluation->evaluated_on_readable = date("Y-m-d", $evaluation->evaldate); // $evaluation->evaluated_on
        }
        echo $OUTPUT->render_from_template('block_edupublisher/evaluation_list', array(
            'canevaluate' => $canevaluate,
            'evaluations' => $evaluations,
            'hasevaluations' => count($evaluations) > 0,
            'packageid' => $packageid,
        ));
    }
}

echo $OUTPUT->footer();
