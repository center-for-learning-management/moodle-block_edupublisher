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
 * @copyright  2019 Digital Education Society (http://www.dibig.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');

$id = optional_param('id', 0, PARAM_INT);
$publisher = block_edupublisher::get_publisher($id);
$context = context_system::instance();
// Must pass login
$PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/publishers.php', array('id' => $id)));
require_login();
$PAGE->set_context($context);
$PAGE->set_title((!empty($publisher->id) ? $publisher->name : get_string('publisher', 'block_edupublisher')));
$PAGE->set_heading((!empty($publisher->id) ? $publisher->name : get_string('publisher', 'block_edupublisher')));
$PAGE->set_pagelayout('mydashboard');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

$PAGE->navbar->add(get_string('publisher', 'block_edupublisher'), new moodle_url('/blocks/edupublisher/pages/publishers.php', array()));
if (!empty($publisher->name)) {
    $PAGE->navbar->add($publisher->name, $PAGE->url);
}
echo $OUTPUT->header();

if (\block_edupublisher\permissions::is_maintainer(array('commercial'))) {
    // if id > 0 show publisher
    // else show list of publishers
    if ($id > 0) {
        ?>
        <a href="<?php echo $CFG->wwwroot; ?>/blocks/edupublisher/pages/publishers.php" class="btn btn-primary"><?php echo get_string('back'); ?></a>
        <?php
        // Publisher details and users
        require_once($CFG->dirroot . '/blocks/edupublisher/classes/publisher_form.php');
        $form = new block_edupublisher\publisher_form();
        if ($data = $form->get_data()) {
            file_save_draft_area_files($data->publisher_logo, $context->id, 'block_edupublisher', 'publisher_logo',
                $publisher->id, array('subdirs' => 0, 'maxbytes' => block_edupublisher\publisher_form::$maxbytes, 'maxfiles' => 1));
        }
        $draftitemid = file_get_submitted_draft_itemid('publisher_logo');
        file_prepare_draft_area($draftitemid, $context->id, 'block_edupublisher', 'publisher_logo', $publisher->id,
            array('subdirs' => 0, 'maxbytes' => block_edupublisher\publisher_form::$maxbytes, 'maxfiles' => 1));
        $publisher->publisher_logo = $draftitemid;
        $form->set_data($publisher);

        $form->display();

        echo $OUTPUT->render_from_template(
            'block_edupublisher/publisher_user',
            array('publisherid' => $id, 'users' => array())
        );

        $externalsources = array_values($DB->get_records('block_edupublisher_externals', array('pubid' => $id)));

        echo $OUTPUT->render_from_template(
            'block_edupublisher/publisher_externalsources',
            array('publisherid' => $id, 'externalsources' => $externalsources)
        );
    } else {
        $publishers = $DB->get_records_sql('SELECT * FROM {block_edupublisher_pub} ORDER BY name ASC');
        echo $OUTPUT->render_from_template(
            'block_edupublisher/publisher',
            array('header' => '1')
        );
        foreach ($publishers as $publisher) {
            echo $OUTPUT->render_from_template(
                'block_edupublisher/publisher',
                $publisher
            );
        }
        echo $OUTPUT->render_from_template(
            'block_edupublisher/publisher',
            array('id' => 0, 'name' => '')
        );
    }
} else {
    echo $OUTPUT->render_from_template(
        'block_edupublisher/alert',
        (object)array(
            'content' => get_string('permission_denied', 'block_edupublisher'),
            'url' => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $package->id,
            'type' => 'danger',
        )
    );
}

echo $OUTPUT->footer();
