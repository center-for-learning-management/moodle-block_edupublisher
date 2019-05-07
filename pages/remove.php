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
 * Removes a package.
 *
 * @package    block_edupublisher
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');

$id = required_param('id', PARAM_INT);
$confirmed = optional_param('confirmed', 0, PARAM_INT);
$package = block_edupublisher::get_package($id, true);
$context = context_system::instance();
// Must pass login
$PAGE->set_url('/blocks/edupublisher/pages/remove.php?id=' . $id);
require_login();
$PAGE->set_context($context);
$PAGE->set_title($package->title);
$PAGE->set_heading($package->title);
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

block_edupublisher::check_requirements();
block_edupublisher::print_app_header();

if ($package->candelete) {
    if ($confirmed) {
        $params = array(
            'content' => get_string('removing_package_course', 'block_edupublisher', (object) $package),
            'type' => 'info',
        );
        echo $OUTPUT->render_from_template('block_edupublisher/alert', (object) $params);
        delete_course($package->course, false);
        $DB->delete_records('block_edupublisher_uses', array('package' => $id));
        $DB->delete_records('block_edupublisher_rating', array('package' => $id));
        $DB->delete_records('block_edupublisher_metadata', array('package' => $id));
        $DB->delete_records('block_edupublisher_packages', array('id' => $id));
        $params = array(
            'content' => get_string('removed_package', 'block_edupublisher', (object) $package),
            'type' => 'success',
            'url' => $CFG->wwwroot . '/my'
        );
        echo $OUTPUT->render_from_template('block_edupublisher/alert', (object) $params);
    } else {
        $params = array(
            'title' => get_string('removal:title', 'block_edupublisher'),
            'text' => get_string('removal:text', 'block_edupublisher', $package),
            'urlconfirm' => $CFG->wwwroot . '/blocks/edupublisher/pages/remove.php?id=' . $id . '&confirmed=1',
            'urlcancel' => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $id,
        );
        echo $OUTPUT->render_from_template('block_edupublisher/confirmation', (object) $params);
    }
} else {
    $params = array(
        'content' => get_string('permission_denied', 'block_edupublisher'),
        'type' => 'warning',
        'url' => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $id
    );
    echo $OUTPUT->render_from_template('block_edupublisher/alert', (object) $params);
}

block_edupublisher::print_app_footer();
