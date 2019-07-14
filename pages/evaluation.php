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
 * @author     Julia Laßnig
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define('__ROOT__', dirname(dirname(dirname(dirname(__FILE__)))));
require_once(__ROOT__.'/config.php'); 

require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
require_once($CFG->dirroot . '/blocks/edupublisher/classes/etapas_evaluation_form.php');

$id = optional_param('id', 0, PARAM_INT);
$packageid = optional_param('packageid', 0, PARAM_INT);
$perma = optional_param('perma', '', PARAM_TEXT);

$url = $CFG->wwwroot . '/blocks/edupublisher/pages/evaluation.php';
if (!empty($id)) $url .= '?&id=' . $id;
if (!empty($packageid)) $url .= '?&packageid=' . $packageid;
if (!empty($perma)) $url .= '?&perma=' . $perma;
$PAGE->set_url($url);

//$context = context_course::instance($id);
$PAGE->set_context(context_system::instance());
$PAGE->set_title('eTapas Evaluation');
//$PAGE->set_course($id);
$heading = get_string("etapas_evaluation", "block_edupublisher");
$PAGE->set_heading($heading);
$PAGE->set_pagelayout('incourse');

block_edupublisher::print_app_header();


//Instantiate etapas_evaluation_form 
$mform = new block_edupublisher\etapas_evaluation_form();

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    /*$url = $CFG->wwwroot . '/course/view.php?';
    if (!empty($id)) $url .= '&id=' . $id;
    if (!empty($packageid)) $url .= '&packageid=' . $packageid;
    if (!empty($perma)) $url .= '&perma=' . $perma;*/
    redirect("view.php?id={$id}");
} else if ($dataobject = $mform->get_data()) {
  //In this case you process validated data. $mform->get_data() returns data posted in form.
  $table = 'block_edupublisher_evaluatio';
  $DB->insert_record($table, $dataobject, $returnid=true, $bulk=false);
  echo('Your data has been successfully submitted');

} else {
  // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
  // or on the first display of the form.
  $mform->display();
  
}


block_edupublisher::print_app_footer();