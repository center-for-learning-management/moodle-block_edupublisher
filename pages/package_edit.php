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
$returnurl = new moodle_url(optional_param('returnurl', '/blocks/edupublisher/pages/list.php', PARAM_URL));
// check for local url
$returnurl->out_as_local_url();

if (!$id) {
    $package = null;
} else {
    $package = new \block_edupublisher\package($id, true);
}

// Must pass login
$PAGE->set_url('/blocks/edupublisher/pages/package_edit.php?id=' . $id);
require_login($package ? $package->courseid : null);

$PAGE->set_context(\context_system::instance());

$title = get_string('package', 'block_edupublisher') . ($package ? ': ' . $package->title : '');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

// $PAGE->navbar->add(get_string('details', 'block_edupublisher'), new moodle_url('/blocks/edupublisher/pages/package.php', array('id' => $id)));
// $PAGE->navbar->add(get_string('edit'), $PAGE->url);

\block_edupublisher\lib::check_requirements();

if ($package && !$package->can_edit()) {
    throw new \moodle_exception('no permission');
}

if ($package) {
    $content_items_old = $DB->get_records('block_edupublisher_pkg_items', ['packageid' => $package->id], 'sorting');
} else {
    $content_items_old = [];
}

$package?->load_origins();
$form = new \block_edupublisher\package_edit_form($package, $content_items_old);

if ($form->is_cancelled()) {
    redirect($returnurl);
} elseif ($data = $form->get_data()) {
    if ($package) {
        $package->store_package($data);
        $package_created = false;
    } else {
        $package = \block_edupublisher\package::create($data);
        $package_created = true;

        $session_competencies = $_REQUEST['session_competencies'] ?? '';
        if ($session_competencies) {
            $session_competencies = explode(',', $session_competencies);
            foreach ($session_competencies as $competencyid) {
                \core_competency\api::add_competency_to_course($package->courseid, $competencyid);
            }
        }
    }

    \block_edupublisher\lib::sync_package_to_course($package);

    if (empty($package->get('suppresscomment', 'default'))) {
        $sendto = array('allmaintainers');
        $package->store_comment('comment:template:package_updated', $sendto, true, false);
    }

    if ($package_created) {
        // danke seite usw. für die Einreichung

        echo $OUTPUT->header();

        echo get_string('publish_stage_finish_text', 'block_edupublisher');
        $url = new \moodle_url('/blocks/edupublisher/pages/package.php', ['id' => $package->id]);
        $label = get_string('publish_stage_finish_button', 'block_edupublisher');
        echo "<div style=\"text-align: center;\"><a href=\"$url\" class=\"btn btn-primary\">$label</a></div>\n";

        echo $OUTPUT->footer();
        exit;
    }


    redirect($returnurl,
        get_string('successfully_saved_package', 'block_edupublisher'),
        null,
        \core\output\notification::NOTIFY_SUCCESS);
}

if ($package) {
    $data = $package->get_form_data();
} else {
    $data = (object)[];

    // $data->default_publishas = 1;
    // $data->etapas_publishas = 1;
    // $data->eduthekneu_publishas = 1;

    $data->content_items = [];

    // show first content item
    $data->content_items[] = [
        'id' => 0,
        'delete' => 0,
    ];
}

$form->set_data($data);

echo $OUTPUT->header();

?>
    <div>
        <a href="<?=$returnurl?>" class="btn btn-secondary mb-2"><?= get_string('back') ?></a>
    </div>
<?php

$form->display();

echo $OUTPUT->footer();
