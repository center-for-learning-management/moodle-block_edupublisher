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
$package = block_edupublisher::get_package($id, true);
$context = context_course::instance($package->course);
// Must pass login
$PAGE->set_url('/blocks/edupublisher/pages/package.php?id=' . $id);
require_login($package->course);
$PAGE->set_context($context);
$PAGE->set_title(get_string('package', 'block_edupublisher'));
$PAGE->set_heading(get_string('package', 'block_edupublisher'));
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

block_edupublisher::check_requirements();
block_edupublisher::print_app_header();

$act = optional_param('act', '', PARAM_TEXT);
switch ($act) {
    case 'authoreditingpermission':
        if ($package->canmoderate) {
            $ctx = context_course::instance($package->course);
            if (optional_param('to', '', PARAM_TEXT) == 'grant') {
                role_assign(3, $package->userid, $ctx->id);
                $sendto = array('author');
                block_edupublisher::store_comment($package, 'comment:template:package_editing_granted', $sendto, true, false);
            }
            if (optional_param('to', '', PARAM_TEXT) == 'remove') {
                role_unassign(3, $package->userid, $ctx->id);
                $sendto = array('author');
                block_edupublisher::store_comment($package, 'comment:template:package_editing_sealed', $sendto, true, false);
            }
            $package = block_edupublisher::get_package($package->id, true);
            echo $OUTPUT->render_from_template(
                'block_edupublisher/alert',
                array(
                    'content' => get_string('successfully_saved_package', 'block_edupublisher'),
                    'type' => 'success',
                )
            );
        } else {
            echo $OUTPUT->render_from_template(
                'block_edupublisher/alert',
                array(
                    'content' => get_string('permission_denied', 'block_edupublisher'),
                    'type' => 'warning',
                    'url' => $PAGE->url,
                )
            );
        }
    break;
}

$package = block_edupublisher::load_origins($package);
$package->comments = $DB->get_records('block_edupublisher_comments', array('package' => $package->id));

//print_r($package);
echo $OUTPUT->render_from_template(
    'block_edupublisher/package',
    $package
);

block_edupublisher::print_app_footer();
