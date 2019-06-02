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
$PAGE->set_url('/blocks/edupublisher/pages/licence.php?id=' . $publisherid);
require_login();
$PAGE->set_context($context);
$PAGE->set_title((!empty($publisher->id) ? $publisher->name . ' - ' : '') . get_string('licence', 'block_edupublisher'));
$PAGE->set_heading((!empty($publisher->id) ? $publisher->name . ' - ' : '') . get_string('licence', 'block_edupublisher'));
$PAGE->set_pagelayout('mydashboard');
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

block_edupublisher::check_requirements();
block_edupublisher::print_app_header();

$is_coworker = $DB->get_record('block_edupublisher_pub_user', array('publisherid' => $publisherid, 'userid' => $USER->id));

if (empty($publisherid)) {
    $options = array();
    if (block_edupublisher::is_admin()) {
        $allpublishers = $DB->get_records_sql('SELECT * FROM {block_edupublisher_pub} ORDER BY name ASC', array());
    } else {
        $allpublishers = $DB->get_records_sql('SELECT ep.* FROM {block_edupublisher_pub} ep, {block_edupublisher_pub_user} epu WHERE ep.id=epu.publisherid AND epu.userid=? ORDER BY name ASC', array($USER->id));
    }
    foreach($allpublishers AS $publisher) {
        if (block_edupublisher::is_admin()) {
            $chk = $DB->get_record('block_edupublisher_pub_user', array('publisherid' => $publisher->id, 'userid' => $USER->id));
            if (!$chk) $publisher->name = '! ' . $publisher->name;
        }
        $options[] = array('id' => $publisher->id, 'name' => $publisher->name);
    }
    if (count($options) == 1) {
        redirect($CFG->wwwroot . '/blocks/edupublisher/pages/licence.php?id=' . $options[0]['id']);
    }
    echo $OUTPUT->render_from_template(
        'block_edupublisher/licence_publisherselect',
        array('options' => $options)
    );
} elseif (block_edupublisher::is_maintainer(array('commercial')) || $is_coworker->userid == $USER->id) {
    $action = optional_param('action', '', PARAM_TEXT);
    $data = (object) array(
        'action' => $action,
        'amounts' => optional_param('amounts', 0, PARAM_INT),
        'confirmed' => optional_param('confirmed', 0, PARAM_INT),
        'failed' => array(),
        'licencekeys' => optional_param('licencekeys', '', PARAM_TEXT),
        'maturity' => time() + 60*60*24*365*7, // 7 years in future. @todo: make it customizable.
        'publisherid' => $publisherid,
        'target' => optional_param('target', 0, PARAM_INT),
        'type' => optional_param('type', 0, PARAM_INT),
    );
    switch ($action) {
        case 'generate':
            $data->selectedpackages = optional_param_array('packages', null, PARAM_INT);
            $data->amountpackages = optional_param_array('packages_amount', 0, PARAM_INT);

            $sql = "SELECT p.id, p.title FROM {block_edupublisher_packages} p, {block_edupublisher_metadata} m
                      WHERE p.id=m.package
                        AND m.field=?
                        AND m.content=?
                      ORDER BY p.title ASC";
            $allpackages = $DB->get_records_sql($sql, array('commercial_publisher', $publisherid));
            $data->packages = array();
            foreach ($allpackages AS $package) {
                $package = block_edupublisher::get_package($package->id, true);
                $package->_isselected = (!empty($data->selectedpackages[$package->id]));
                $package->{'type_' . $data->type} = 1;
                $package->amount = $data->amountpackages[$package->id];
                $data->packages[] = $package;
            }
            $data->{'type_' . $data->type} = 1;
            $data->{'target_' . $data->target} = 1;

            $data->step = 0;
            if ($data->step == 0 && in_array($data->type, array(1, 2))) {
                $data->step = 1;
            }
            if ($data->step == 1
                && in_array($data->type, array(1, 2))
                && count($data->selectedpackages) > 0
                && !empty($data->target)
                && in_array($data->target, array(1, 2, 3))) {
                $data->step = 2;
            }
            if ($data->step == 2 && !empty($data->licencekeys)) {
                $data->step = 3;
                $keys = explode(' ', $data->licencekeys);
                foreach ($keys AS $key) {
                    $chk = $DB->get_record('block_edupublisher_lic', array('licencekey' => $key), 'id', IGNORE_MISSING);
                    if (!empty($chk->id)) {
                        $data->failed[] = $key;
                    }
                }
            }
            if ($data->step == 3 && count($data->failed) == 0 && $data->confirmed == 1) {
                // We really insert the licences.
                $data->_licencekeys = explode(' ', $data->licencekeys);
                foreach ($data->_licencekeys AS $key) {
                    $data->created = time();
                    $data->redeemid = 0;
                    $data->userid = $USER->id;
                    $data->licencekey = $key;
                    $licenceid = $DB->insert_record('block_edupublisher_lic', $data, true);

                    if (!empty($licenceid)) {
                        $packageids = array_keys($data->selectedpackages);
                        foreach ($packageids AS $packageid) {
                            $obj = array(
                                'active' => 1,
                                'amounts' => $data->amounts,
                                'licenceid' => $licenceid,
                                'packageid' => $packageid,
                            );
                            if ($data->type == 1) {
                                $obj['amounts'] = $data->amountpackages[$packageid];
                            }
                            $DB->insert_record('block_edupublisher_lic_pack', $obj);
                        }
                    } else {
                        $data->failed[] = $key;
                    }
                }
                if (count($data->failed) == 0) {
                    $data->step = 4;
                }
            }

            echo $OUTPUT->render_from_template(
                'block_edupublisher/licence_generate_' . $data->step,
                $data
            );
        break;
        case 'list':
            require_once($CFG->dirroot . '/blocks/edupublisher/classes/licence_list_form.php');
            $form = new block_edupublisher\licence_list_form('licence_download.php');
            $form->set_data($data);
            $form->display();
        break;
        default:
            echo $OUTPUT->render_from_template(
                'block_edupublisher/licence_dashboard',
                array('action_' . $action => 1, 'publisherid' => $publisherid)
            );
    }
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
