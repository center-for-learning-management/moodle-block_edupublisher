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

require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/blocks/edupublisher/pages/reset.php', array());
$PAGE->set_title('Atomize DB');
$PAGE->set_heading('Atomize DB');

if (!\block_edupublisher\permissions::is_admin()) {
    throw new \moodle_exception('permission denied');
}
$PAGE->requires->css('/blocks/edupublisher/style/main.css');

echo $OUTPUT->header();
\block_edupublisher\locallib::atomize_database();
echo "<p class=\"alert alert-success\">Everything was atomized. Please check and remove the table {block_edupublisher_metadata}.</p>";
echo $OUTPUT->footer();
