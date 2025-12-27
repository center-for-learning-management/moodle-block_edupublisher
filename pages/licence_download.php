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

$publisherid = optional_param('publisherid', 0, PARAM_INT);
$publisher = $DB->get_record('block_edupublisher_pub', array('id' => $publisherid), '*', IGNORE_MISSING);
$context = context_system::instance();

// Must pass login
$PAGE->set_url('/blocks/edupublisher/pages/licence.php?publisherid=' . $publisherid . '&action=list');
\block_edupublisher\permissions::require_login();
$PAGE->set_context($context);
$PAGE->set_title((!empty($publisher->id) ? $publisher->name . ' - ' : '') . get_string('licence', 'block_edupublisher'));
$PAGE->set_heading((!empty($publisher->id) ? $publisher->name . ' - ' : '') . get_string('licence', 'block_edupublisher'));
$PAGE->set_pagelayout('mydashboard');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

\block_edupublisher\lib::check_requirements();

$is_coworker = $DB->get_record('block_edupublisher_pub_user', array('publisherid' => $publisherid, 'userid' => $USER->id));

if (!empty($publisherid) && \block_edupublisher\permissions::is_maintainer(array('commercial')) || $is_coworker->userid == $USER->id) {
    require_once($CFG->dirroot . '/blocks/edupublisher/classes/licence_list_form.php');
    $form = new block_edupublisher\licence_list_form('download.php');
    if ($data = $form->get_data()) {
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=licences.csv");
        header("Pragma: no-cache");
        header("Expires: 0");
        $licenceids = array_keys($data->licenceids);

        $out = fopen('php://output', 'w');
        $delimiter = ";";
        $enclosure = "\"";
        $escapechar = "\\";

        $headline = false;
        foreach ($licenceids as $licenceid) {
            if ($data->licenceids[$licenceid] != 1)
                continue;
            $lic = $DB->get_record('block_edupublisher_lic', array('id' => $licenceid));
            if ($lic->id == $licenceid && $lic->publisherid == $publisherid) {
                $userid = $lic->userid;
                $user = $DB->get_record('user', array('id' => $userid), '*', IGNORE_MISSING);
                $lic->user = fullname($user);
                $lic->created = date('Y-m-d H:i:s', $lic->created);
                $lic->maturity = date('Y-m-d H:i:s', $lic->maturity);
                $packages = $DB->get_records('block_edupublisher_lic_pack', array('licenceid' => $lic->id));
                $lic->packages = array();
                foreach ($packages as $package) {
                    $package = new \block_edupublisher\package($package->packageid, false);
                    $lic->packages[] = $package->title;
                }
                $lic->packages = implode(', ', $lic->packages);
                unset($lic->id);
                unset($lic->publisherid);
                unset($lic->userid);
                if (!$headline) {
                    fputcsv($out, array_keys((array)$lic), $delimiter, $enclosure, $escapechar);
                    $headline = true;
                }
                switch ($lic->target) {
                    case 1:
                        $lic->target = get_string('licence_target_org', 'block_edupublisher');
                        break;
                    case 2:
                        $lic->target = get_string('licence_target_course', 'block_edupublisher');
                        break;
                    case 3:
                        $lic->target = get_string('licence_target_user', 'block_edupublisher');
                        break;
                }
                switch ($lic->type) {
                    case 1:
                        $lic->type = get_string('licence_collection', 'block_edupublisher');
                        break;
                    case 2:
                        $lic->type = get_string('licence_pool', 'block_edupublisher');
                        break;
                }
                fputcsv($out, (array)$lic, $delimiter, $enclosure, $escapechar);
            }
        }
        fclose($out);
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template(
        'block_edupublisher/alert',
        (object)array(
            'content' => get_string('permission_denied', 'block_edupublisher'),
            'url' => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $package->id,
            'type' => 'danger',
        )
    );
    echo $OUTPUT->footer();
}
