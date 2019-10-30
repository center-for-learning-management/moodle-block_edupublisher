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
$PAGE->set_url('/blocks/edupublisher/pages/package_edit.php?id=' . $id);
require_login($package->course);
$PAGE->set_context($context);
$PAGE->set_title(get_string('package', 'block_edupublisher'));
$PAGE->set_heading(get_string('package', 'block_edupublisher'));
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

$PAGE->navbar->add(get_string('details', 'block_edupublisher'), new moodle_url('/blocks/edupublisher/pages/package.php', array('id' => $package->id)));
$PAGE->navbar->add(get_string('edit'), $PAGE->url);

block_edupublisher::check_requirements();

block_edupublisher::print_app_header();

if ($package->canedit) {
    $package = block_edupublisher::load_origins($package);

    require_once($CFG->dirroot . '/blocks/edupublisher/classes/package_create_form.php');
    //$form = new package_create_form(null, null, 'post', '_self', array('onsubmit' => 'this.querySelectorAll("input").forEach(i => i.disabled = false)'), true);
    $form = new package_create_form(null, null, 'post', '_self', array('onsubmit' => 'this.querySelectorAll("input").forEach( i => i.disabled = false)'), true);
    if ($data = $form->get_data()) {
        $package = block_edupublisher::store_package($data);
        $sendto = array('allmaintainers');
        block_edupublisher::store_comment($package, 'comment:template:package_updated', $sendto, true, false);
        echo "<p class=\"alert alert-success\">" . get_string('successfully_saved_package', 'block_edupublisher') . "</p>";
    }
    //$MODE_SHOW_FORM = 1;
    //$form = new package_create_form(null, null, 'post', '_self', array('onsubmit' => 'this.querySelectorAll("input").forEach( i => i.disabled = false)'), true);
    // get_data as dummy to validate under mode_show_form precondition.
    $form->get_data();
//print_r($package);
    $package = block_edupublisher::prepare_package_form($package);
//print_r($package);

    $form->set_data($package);
    $form->display();
} else {
    echo $OUTPUT->render_from_template(
        'block_edupublisher/alert',
        (object) array(
            'content' => get_string('permission_denied', 'block_edupublisher'),
            'url' => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $package->id,
            'type' => 'danger',
        )
    );
}

block_edupublisher::print_app_footer();
