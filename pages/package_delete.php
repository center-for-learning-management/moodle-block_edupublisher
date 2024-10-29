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
$confirm = optional_param('confirm', 0, PARAM_INT);
$package = new \block_edupublisher\package($id, true);
$context = context_course::instance($package->courseid);
// Must pass login
$PAGE->set_url('/blocks/edupublisher/pages/package_delete.php?id=' . $id);
require_login($package->courseid);
$PAGE->set_context($context);
$PAGE->set_title($package->get('title'));
$PAGE->set_heading($package->get('title'));
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

$PAGE->navbar->add(
    get_string('details', 'block_edupublisher'),
    new moodle_url(
        '/blocks/edupublisher/pages/package.php',
        array(
            'id' => $package->id,
        )
    )
);
$PAGE->navbar->add(
    get_string('removal:title', 'block_edupublisher'),
    $PAGE->url
);
\block_edupublisher\lib::check_requirements();

block_edupublisher\permissions::require([
    'can_delete' => $package->can_delete()
]);

echo $OUTPUT->header();

if ($confirm) {
    $params = array(
        'content' => get_string('removing_package_course', 'block_edupublisher', $package->get_flattened()),
        'type' => 'info',
    );
    echo $OUTPUT->render_from_template('block_edupublisher/alert', $params);

    delete_course($package->courseid, false);

    $DB->delete_records('block_edupublisher_uses', array('package' => $id));
    $DB->delete_records('block_edupublisher_rating', array('package' => $id));
    $DB->delete_records('block_edupublisher_md_com', array('package' => $id));
    $DB->delete_records('block_edupublisher_md_def', array('package' => $id));
    $DB->delete_records('block_edupublisher_md_edu', array('package' => $id));
    $DB->delete_records('block_edupublisher_md_eta', array('package' => $id));
    $DB->delete_records('block_edupublisher_md_exa', array('package' => $id));

    // Check if this is an external source.
    $DB->delete_records('block_edupublisher_extitem', array('packageid' => $id));
    $DB->delete_records('block_edupublisher_extsect', array('packageid' => $id));
    $DB->delete_records('block_edupublisher_extpack', array('packageid' => $id));

    // Removing the package itself may create inconsistencies. We just flag as removed.
    $p = $DB->get_record('block_edupublisher_packages', array('id' => $id));
    $p->deleted = time();
    $p->modified = time();
    $p->course = 0;
    $DB->update_record('block_edupublisher_packages', $p);
    $params = array(
        'content' => get_string('removed_package', 'block_edupublisher', $package->get_flattened()),
        'type' => 'success',
        'url' => $CFG->wwwroot . '/blocks/edupublisher/pages/list.php',
    );
    echo $OUTPUT->render_from_template('block_edupublisher/alert', (object)$params);
} else {
    $params = array(
        'title' => get_string('removal:title', 'block_edupublisher'),
        'text' => get_string('removal:text', 'block_edupublisher', $package->get_flattened()),
        'urlconfirm' => $CFG->wwwroot . '/blocks/edupublisher/pages/package_delete.php?id=' . $id . '&confirm=1',
        'urlcancel' => $CFG->wwwroot . '/course/view.php?id=' . $package->courseid,
    );
    echo $OUTPUT->render_from_template('block_edupublisher/confirmation', (object)$params);
}

echo $OUTPUT->footer();
