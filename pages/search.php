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

$course = optional_param('courseid', 0, PARAM_INT);
$section = optional_param('sectionid', -1, PARAM_INT); // This refers to section id, not number!
$search = optional_param('search', '', PARAM_TEXT);
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
} else {
    $context = context_system::instance();
}

require_login();
// Set up the page
$PAGE->set_title(get_string('search'));
$PAGE->set_heading(get_string('search'));
$PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/search.php', array('courseid' => $course, 'search' => $search, 'sectionid' => $section, 'layout' => $layout)));
$PAGE->set_pagelayout($layout);
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

block_edupublisher::check_requirements();
block_edupublisher::print_app_header();

echo $OUTPUT->render_from_template(
    'block_edupublisher/search',
    (object) array(
        'courseid' => $course,
        'sectionid' => $sectionno,
        'search' => $search,
        'layout' => $layout,
        'wwwroot' => $CFG->wwwroot
    )
);

block_edupublisher::print_app_footer();
