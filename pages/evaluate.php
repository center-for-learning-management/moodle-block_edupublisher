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
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');

$id = optional_param('id', 0, PARAM_INT);
$packageid = optional_param('packageid', 0, PARAM_INT);
$perma = optional_param('perma', '', PARAM_TEXT);

$url = $CFG->wwwroot . '/blocks/edupublisher/pages/evaluate.php?';
if (!empty($id)) $url .= '&id=' . $id;
if (!empty($packageid)) $url .= '&packageid=' . $packageid;
if (!empty($perma)) $url .= '&perma=' . $perma;
$PAGE->set_url($url);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_title('Evaluation');
$PAGE->set_heading('Evaluation');
$PAGE->set_pagelayout('incourse');

block_edupublisher::print_app_header();

echo $OUTPUT->render_from_template(
    'block_edupublisher/alert',
    array(
        'content' => 'not yet implemented',
        'url' => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $packageid,
        'type' => 'warning',
    )
);


block_edupublisher::print_app_footer();
