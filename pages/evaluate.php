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
 * @copyright  2019 Zentrum für Lernmanagement (www.lernmanagement.at)
 * @author     Julia Laßnig & Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
require_once($CFG->dirroot . '/blocks/edupublisher/classes/etapas_evaluation_form.php');

$packageid = required_param('packageid', PARAM_INT);
$perma = optional_param('perma', '', PARAM_TEXT);

$package = new \block_edupublisher\package($packageid, true);

$PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/evaluate.php', array('packageid' => $packageid, 'perma' => $perma)));

$context = \context_course::instance($package->get('course'));
$PAGE->set_context($context);
$title = !empty($package->get('id')) ? $package->get('title') : get_string('etapas_evaluation', 'block_edupublisher');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('incourse');

$PAGE->navbar->add(
    get_string('resource_catalogue', 'block_edupublisher'),
    new moodle_url('/blocks/edupublisher/pages/search.php', array())
);
$PAGE->navbar->add(
    $package->get('title'),
    new moodle_url(
        '/course/view.php',
        array(
            'id' => $package->get('course'),
        )
    )
);
$PAGE->navbar->add(
    get_string('etapas_evaluation', 'block_edupublisher'),
    new \moodle_url(
        '/blocks/edupublisher/pages/evaluation.php',
        array(
            'packageid' => $package->get('id'),
        )
    )
);

echo $OUTPUT->header();

if (!has_capability('block/edupublisher:canevaluate', \context_system::instance())) {
    echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
        'type' => 'danger',
        'content' => get_string('no_permission', 'block_edupublisher'),
        'url' => $CFG->wwwroot . '/my',
    ));
} else {
    //Instantiate etapas_evaluation_form
    $backurl = new \moodle_url('/blocks/edupublisher/pages/evaluation.php', array('packageid' => $packageid));
    $package->set($package->get('id'), 'packageid');
    $mform = new block_edupublisher\etapas_evaluation_form();
    $mform->set_data($package->get_flattened());

    //Form processing and displaying is done here
    if ($mform->is_cancelled()) {
        redirect($PAGE->url);
    } else if ($dataobject = $mform->get_data()) {
        $dataobject->userid = $USER->id;
        $dataobject->improvement_specification = nl2br($dataobject->improvement_specification);
        $dataobject->comments = nl2br($dataobject->comments);
        $dataobject->timecreated = time();
        if (empty($dataobject->evaluated_on)) {
            $dataobject->evaluated_on = time();
        }
        $id = $DB->insert_record('block_edupublisher_evaluatio', $dataobject);
        $backurl = new \moodle_url('/blocks/edupublisher/pages/evaluation.php', array('packageid' => $packageid));
        if (!empty($id)) {
            $sendto = array('allmaintainers', 'author', 'self');
            $linkurl = "/blocks/edupublisher/pages/evaluation.php?packageid=$packageid&id=$id";
            $package->set('eval', 'status', 'etapas');
            $package->store_package_db();
            $package->store_comment('comment:evaluation:added', $sendto, true, true, "etapas", $linkurl);
            echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
                'type' => 'success',
                'content' => get_string('successfully_saved_evaluation', 'block_edupublisher'),
                'url' => $backurl->__toString(),
            ));
        } else {
            echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
                'type' => 'danger',
                'content' => get_string('error'),
                'url' => $backurl->__toString(),
            ));
        }

    } else {
        $mform->display();
    }
}

echo $OUTPUT->footer();
