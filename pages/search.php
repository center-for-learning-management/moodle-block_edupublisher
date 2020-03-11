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
require_once($CFG->dirroot . '/blocks/edupublisher/locallib.php');

$course = optional_param('courseid', 0, PARAM_INT);
$section = optional_param('sectionid', -1, PARAM_INT); // This refers to section id, not number!
$search = optional_param('search', '', PARAM_TEXT);
$subjectarea = optional_param('subjectarea', '', PARAM_TEXT);
$schoollevel = optional_param('schoollevel', '', PARAM_TEXT);
$layout = optional_param('layout', 'incourse', PARAM_TEXT);
$availablelayouts = array('incourse', 'embedded', 'popup');
if (!in_array($layout, $availablelayouts)) {
    $layout = 'incourse';
}

$sectionno = optional_param('sectionnr', -1, PARAM_INT);
if ($section == -1 && $sectionno > -1) {
    $sql = "SELECT id,section
                FROM {course_sections}
                WHERE course=?
                ORDER BY section ASC";
    $params = array($course, $sectionno);
    $sections = $DB->get_records_sql($sql, $params);
    foreach ($sections AS $_section) {
        if ($_section->section == $sectionno) {
            $section = $_section->id;
        }
    }
}

if (!empty($course)) {
    $context = context_course::instance($course);
    $PAGE->set_context($context);
    require_login(get_course($course));
} else {
    $context = context_system::instance();
    $PAGE->set_context($context);
    require_login();
}

// Set up the page
$PAGE->set_title(get_string('search'));
$PAGE->set_heading(get_string('search'));
$PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/search.php', array('courseid' => $course, 'search' => $search, 'sectionid' => $section, 'layout' => $layout)));
$PAGE->set_pagelayout($layout);
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');
$PAGE->navbar->add(get_string('search_in_edupublisher', 'block_edupublisher'), $PAGE->url);

block_edupublisher::check_requirements();
block_edupublisher::print_app_header();

$subjectareas = \block_edupublisher\get_subjectareas_sorted($subjectarea);
$schoollevels = \block_edupublisher\get_schoollevels_sorted($schoollevel);

$lic_orgids = array();
$lic_courseids = array();
$orgs = block_eduvidual::get_organisations('teacher', false);
foreach ($orgs AS $org) {
    $lic_orgids[] = $org->orgid;
}
$courses = enrol_get_all_users_courses($USER->id, true);
foreach ($courses AS $_course) {
    $context = context_course::instance($_course->id);
    if (has_capability('moodle/course:update', $context)) {
        $lic_courseids[] = $_course->id;
    }
}

$sql = "SELECT l.*,lp.packageid,p.name publishername FROM
            {block_edupublisher_lic} l,
            {block_edupublisher_lic_pack} lp,
            {block_edupublisher_pub} p
            WHERE l.publisherid=p.id
                AND l.id=lp.licenceid
                AND (
                    (type=3 AND redeemid=?)
                    OR (type=2 AND redeemid IN (?))
                    OR (type=1 AND redeemid IN (?))
                )
            ORDER BY p.name ASC";

$lics = $DB->get_records_sql($sql, array($USER->id, implode(',', $lic_courseids), implode(',', $lic_orgids)));
$publishers = array();

foreach ($lics AS $lic) {
    //print_r($lic);
    if (!isset($publishers[$lic->publishername])) {
        $publishers[$lic->publishername] = block_edupublisher::get_publisher($lic->publisherid);
        $publishers[$lic->publishername]->items = array();
    }
    if (!isset($publishers[$lic->publishername]->items[$lic->packageid])) {
        $publishers[$lic->publishername]->items[$lic->packageid] = block_edupublisher::get_package($lic->packageid, true);
        //$publishers[$lic->publishername]->items[$lic->packageid]->importtocourseid = $course;
    }
}
$publishers = array_values($publishers);
for ($a = 0; $a < count($publishers); $a++) {
    $publishers[$a]->items = array_values($publishers[$a]->items);
}

//print_r($publishers);
echo $OUTPUT->render_from_template(
    'block_edupublisher/search',
    (object) array(
        'courseid' => $course,
        'enablecommercial' => get_config('block_edupublisher', 'enablecommercial'),
        'importtocourseid' => $course, // Required together with showpreviewbutton for search_li.mustache
        'layout' => $layout,
        'publishers' => $publishers,
        'sectionid' => $sectionno,
        'search' => $search,
        'showpreviewbutton' => $course, // Required together with importtocourseid for search_li.mustache
        'subjectarea' => $subjectarea,
        'subjectareas' => $subjectareas,
        'schoollevel' => $schoollevel,
        'schoollevels' => $schoollevels,
        'wwwroot' => $CFG->wwwroot,
    )
);

block_edupublisher::print_app_footer();
