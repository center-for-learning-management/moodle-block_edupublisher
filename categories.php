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
 * @copyright  2021 Center for learning management (www.lernmanagement.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once('../../config.php');
require_login();

$PAGE->set_url('/blocks/edupublisher/setting.php', array());
$PAGE->set_context(\context_system::instance());
$PAGE->set_heading(get_string('pluginname', 'block_edupublisher'));
$PAGE->set_title(get_string('pluginname', 'block_edupublisher'));

echo $OUTPUT->header();

// Show list of my orgs.
if (!is_siteadmin()) {
    echo $OUTPUT->render_from_template('block_edupublisher/alert', array(
        'type' => 'danger',
        'content' => get_string('permission_denied', 'block_edupublisher'),
    ));
    echo $OUTPUT->footer();
    die();
}

if (!function_exists('block_edupublisher_build_categories_tree')) {
    function block_edupublisher_build_categories_tree(&$tree, $categoryid) {
        global $DB;
        $items = $DB->get_records_sql('SELECT id,name,depth FROM {course_categories} WHERE parent=? ORDER BY name ASC', array($categoryid));
        foreach($items AS $item) {
            $tree[$item->id] = str_pad('', ($item->depth - 1) * 2, '>') . $item->name;
            block_edupublisher_build_categories_tree($tree, $item->id);
        }
    }
}

$_CATEGORIES = array();
//block_edupublisher_build_categories_tree($_CATEGORIES, 0);

// @todo make selector here.
/*
$settings->add(new admin_setting_configselect('block_edupublisher/category', get_string('category', 'block_edupublisher'), '', 1, $_CATEGORIES));
$settings->add(new admin_setting_configselect('block_edupublisher/categorysubcourses', get_string('categorysubcourses', 'block_edupublisher'), '', 1, $_CATEGORIES));
*/

echo "to do";
echo $OUTPUT->footer();
