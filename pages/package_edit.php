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
$context = \context_course::instance($package->get('course'));
// Must pass login
$PAGE->set_url('/blocks/edupublisher/pages/package_edit.php?id=' . $id);
require_login($package->get('course'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('package', 'block_edupublisher'));
$PAGE->set_heading(get_string('package', 'block_edupublisher'));
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

$PAGE->navbar->add(get_string('details', 'block_edupublisher'), new moodle_url('/blocks/edupublisher/pages/package.php', array('id' => $package->get('id'))));
$PAGE->navbar->add(get_string('edit'), $PAGE->url);

\block_edupublisher\lib::check_requirements();

echo $OUTPUT->header();

if ($package->get('canedit')) {
    $package->load_origins();

    require_once("$CFG->dirroot/blocks/edupublisher/classes/package_create_form.php");
    $form = new package_create_form(null, null, 'post', '_self', array('onsubmit' => 'this.querySelectorAll("input").forEach( i => i.disabled = false)'), true);
    if ($data = $form->get_data()) {
        $package->store_package($data);
        if (empty($package->get('suppresscomment', 'default'))) {
            $sendto = array('allmaintainers');
            $package->store_comment('comment:template:package_updated', $sendto, true, false);
        }
        echo "<p class=\"alert alert-success\">" . get_string('successfully_saved_package', 'block_edupublisher') . "</p>";
    }
    // get_data as dummy to validate under mode_show_form precondition.
    $form->get_data();
    $package->prepare_package_form();
    $form->set_data($package->get_flattened());
    echo "<div class=\"skip-ui-eduvidual ui-edupublisher-skip\">";
    $form->display();
    echo "</div>";
} else {
    echo $OUTPUT->render_from_template(
        'block_edupublisher/alert',
        (object) array(
            'content' => get_string('permission_denied', 'block_edupublisher'),
            'url' => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $package->get('id'),
            'type' => 'danger',
        )
    );
}

echo $OUTPUT->footer();
