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

\block_edupublisher\permissions::require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/blocks/edupublisher/pages/reset.php', array());
$PAGE->set_title('Reset');
$PAGE->set_heading('Reset');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');

echo $OUTPUT->header();

if (\block_edupublisher\permissions::is_admin() && optional_param('confirmed', 0, PARAM_INT) == 1) {
    require_once($CFG->dirroot . '/course/lib.php');
    $packages = $DB->get_records('block_edupublisher_packages');
    foreach ($packages as $package) {
        $params = array(
            'content' => get_string('removing_package_course', 'block_edupublisher', $package),
            'type' => 'info',
        );
        echo $OUTPUT->render_from_template('block_edupublisher/alert', (object)$params);
        delete_course($package->courseid, false);
    }
    $DB->execute('TRUNCATE {block_edupublisher_packages}');
    $DB->execute('TRUNCATE {block_edupublisher_md_com}');
    $DB->execute('TRUNCATE {block_edupublisher_md_def}');
    $DB->execute('TRUNCATE {block_edupublisher_md_edu}');
    $DB->execute('TRUNCATE {block_edupublisher_md_eta}');
    $DB->execute('TRUNCATE {block_edupublisher_md_exa}');
    $DB->execute('TRUNCATE {block_edupublisher_rating}');
    $DB->execute('TRUNCATE {block_edupublisher_uses}');
    $params = array(
        'content' => get_string('removed_everything', 'block_edupublisher'),
        'type' => 'success',
        'url' => $CFG->wwwroot . '/my',
    );
    echo $OUTPUT->render_from_template('block_edupublisher/alert', (object)$params);
} elseif (\block_edupublisher\permissions::is_admin()) {
    $params = array(
        'content' => get_string('remove_everything', 'block_edupublisher'),
        'type' => 'danger',
        'url' => $CFG->wwwroot . '/blocks/edupublisher/pages/reset.php?confirmed=1',
    );
    echo $OUTPUT->render_from_template('block_edupublisher/alert', (object)$params);
} else {
    $params = array(
        'content' => get_string('permission_denied', 'block_edupublisher'),
        'type' => 'warning',
    );
    echo $OUTPUT->render_from_template('block_edupublisher/alert', (object)$params);
}

echo $OUTPUT->footer();
