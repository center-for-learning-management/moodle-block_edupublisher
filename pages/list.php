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

$channels = array('default', 'etapas', 'eduthek');
$channel = optional_param('channel', '', PARAM_TEXT);

$context = context_system::instance();
// Must pass login
$PAGE->set_url(new moodle_url('/blocks/edupublisher/pages/list.php', array('channel' => $channel)));
require_login();
$PAGE->set_context($context);
$title = get_string('channels', 'block_edupublisher');
if (!empty($channel) && in_array($channel, $channels)) {
    $title = get_string($channel . '_header', 'block_edupublisher');
} elseif(!empty($channel)) {
    $title = get_string('error');
}
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

$PAGE->navbar->add(get_string('resource_catalogue', 'block_edupublisher'), new moodle_url('/blocks/edupublisher/pages/search.php', array()));
$PAGE->navbar->add(get_string('channels', 'block_edupublisher'), new moodle_url('/blocks/edupublisher/pages/list.php', array()));
if (!empty($channel)) {
    $PAGE->navbar->add($title, $PAGE->url);
}


\block_edupublisher\lib::check_requirements();
echo $OUTPUT->header();

if (empty($channel) && !\block_edupublisher\lib::is_maintainer() || !empty($channel) && !\block_edupublisher\lib::is_maintainer(array($channel))) {
    echo $OUTPUT->render_from_template(
        'block_edupublisher/alert',
        array(
            'content' => get_string('permission_denied', 'block_edupublisher'),
            'type' => 'warning',
            'url' => $CFG->wwwroot . '/my',
        )
    );
    echo $OUTPUT->footer();
    die();
}

$category = get_config('block_edupublisher', 'category');
$context = context_coursecat::instance($category);
$maintainer_default = has_capability('block/edupublisher:managedefault', $context);
$maintainer_etapas = has_capability('block/edupublisher:manageetapas', $context);
$maintainer_eduthek = has_capability('block/edupublisher:manageeduthek', $context);

if (empty($channel)) {
    echo $OUTPUT->render_from_template(
        'block_edupublisher/maintain_channelselection',
        array(
            'maintainer_default' => $maintainer_default,
            'maintainer_etapas' => $maintainer_etapas,
            'maintainer_eduthek' => $maintainer_eduthek,
            'wwwroot' => $CFG->wwwroot
        )
    );
} else {
    if (!$maintainer_default && !$maintainer_etapas && !$maintainer_eduthek) {
        throw new \moodle_exception('permission_denied', 'block_edupublisher');
    }
    /*
    if (!\block_edupublisher\lib::is_maintainer(array($channel))) {
        throw new \moodle_exception('permission_denied', 'block_edupublisher');
    }
    */
    echo $OUTPUT->render_from_template('block_edupublisher/maintain_table_head', array(
        'channel' => $channel,
        'maintainer_default' => $maintainer_default,
        'maintainer_etapas' => $maintainer_etapas,
        'maintainer_eduthek' => $maintainer_eduthek,
    ));

    $sql = "SELECT id
                FROM {block_edupublisher_packages}
                WHERE deleted = 0
                ORDER BY id DESC";
    $packages = $DB->get_records_sql($sql, []);
    foreach($packages AS $p) {
        $package = new \block_edupublisher\package($p->id, true);
        $package->set($maintainer_default, 'maintainer', 'default');
        $package->set($maintainer_eduthek, 'maintainer', 'eduthek');
        $package->set($maintainer_etapas, 'maintainer', 'etapas');

        $hasexacompsourceids = !empty($package->get('exacompsourceids', 'default')) && count($package->get('exacompsourceids', 'default')) > 0;
        $package->set($hasexacompsourceids, 'hasexacompsourceids', 'etapas');

        $exclamation = (
            $maintainer_default && !empty($package->get('publishas', 'default')) && empty($package->get('published', 'default'))
            ||
            $maintainer_etapas && !empty($package->get('publishas', 'etapas')) && empty($package->get('published', 'etapas'))
            ||
            $maintainer_eduthek && !empty($package->get('publishas', 'eduthek')) && empty($package->get('published', 'eduthek'))
        );
        $package->set($exclamation, 'exclamation');
        echo $OUTPUT->render_from_template(
            'block_edupublisher/maintain_table_row',
            $package->get_flattened()
        );
    }
    echo $OUTPUT->render_from_template('block_edupublisher/maintain_table_foot', array());

}

echo $OUTPUT->footer();
