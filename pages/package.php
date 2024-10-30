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

$id = required_param('id', PARAM_INT);
$package = new \block_edupublisher\package($id, true);

// Must pass login
$PAGE->set_url('/blocks/edupublisher/pages/package.php?id=' . $id);
require_login();

$PAGE->set_context(\context_system::instance());

$title = get_string('package', 'block_edupublisher') . ': ' . $package->title;
$PAGE->set_title($title);
$PAGE->set_heading($title);

$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

// $PAGE->navbar->add($package->get('title', 'default'), new moodle_url('/course/view.php', array('id' => $package->courseid)));
// $PAGE->navbar->add(get_string('details', 'block_edupublisher'), $PAGE->url);

\block_edupublisher\lib::check_requirements(false);

$act = optional_param('act', '', PARAM_TEXT);
switch ($act) {
    case 'authoreditingpermission':
        \block_edupublisher\permissions::require([
            'canmoderate' => $package->get('canmoderate'),
        ]);

        $grant = required_param('grant', PARAM_BOOL);
        if ($grant) {
            \block_edupublisher\permissions::role_assign($package->courseid, $package->userid, 'defaultroleteacher');
            // course_role_package_viewing doesn't need to be removed
            $sendto = array('author');
            $package->store_comment('comment:template:package_editing_granted', $sendto, true, false);
        } else {
            \block_edupublisher\permissions::role_assign($package->courseid, $package->userid, 'course_role_package_viewing');
            \block_edupublisher\permissions::role_unassign($package->courseid, $package->userid, 'defaultroleteacher');
            $sendto = array('author');
            $package->store_comment('comment:template:package_editing_sealed', $sendto, true, false);
        }

        redirect(new \moodle_url('/course/view.php?id=' . $package->courseid), get_string('successfully_saved_package', 'block_edupublisher'));
        break;

    case 'submit_package':
        \block_edupublisher\permissions::require([
            'canedit' => $package->can_edit(),
        ]);

        $package->set_v2('publishas', 1, 'default');
        $package->store_package_db();

        \block_edupublisher\permissions::role_assign($package->courseid, $package->userid, 'course_role_package_viewing');
        \block_edupublisher\permissions::role_unassign($package->courseid, $package->userid, 'defaultroleteacher');

        redirect(new \moodle_url('/course/view.php?id=' . $package->courseid), 'Ressource wurde an das Redaktionsteam weitergeleitet');
        break;

    case '';
        // is handled after the switch statement
        // redirect(new \moodle_url('/course/view.php?id=' . $package->courseid));
        break;

    default:
        throw new \moodle_exception('unknown action');
}

echo $OUTPUT->header();

$package->load_origins();

$data = clone $package->get_flattened();
$data->returnurl = urlencode((new moodle_url(qualified_me()))->out_as_local_url(true));
$data->details = \block_edupublisher\output::render_package_details($package, 'package_detail_page');

echo $OUTPUT->render_from_template(
    'block_edupublisher/package',
    $data
);

echo $OUTPUT->footer();
