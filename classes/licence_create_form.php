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
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/formslib.php");

class licence_create_form extends \moodleform {
    function definition() {
        global $DB, $publisherid, $step;

        $mform = $this->_form;
        $mform->addElement('hidden', 'publisherid', 0);
        $mform->setType('publisherid', PARAM_INT);

        switch ($step) {
            case 2:
                global $data;
                // We want to enter licencekeys manually. All other fields are now hidden.
                $mform->addElement('textarea', 'licencekeys', get_string('licence_keys', 'block_edupublisher'), array('style' => 'width: 100%', 'rows' => '10'));
                $mform->setType('licencekeys', PARAM_TEXT);

                $mform->addElement('hidden', 'type', $data->type);
                $mform->setType('type', PARAM_TEXT);

                $mform->addElement('hidden', 'type', $data->type);
                $mform->setType('type', PARAM_TEXT);

            break;
            default:
                $packages = $DB->get_records_sql("SELECT p.id, p.title FROM {block_edupublisher_packages} p, {block_edupublisher_metadata} m WHERE p.id=m.package AND m.field=? AND m.content=? ORDER BY p.title ASC", array('commercial_publisher', $publisherid));
                $options = array();
                foreach ($packages AS $package) {
                    $options[$package->id] = $package->title;
                }
                $select = $mform->addElement('select', 'packages', get_string('licence_packages', 'block_edupublisher'), $options, array('multiple' => 'multiple', 'size' => (count($options) > 10) ? 10 : count($options), 'style' => 'width: 100%'));
                $mform->setType('packages', PARAM_TEXT);
                $select->setMultiple(true);

                $types = array(
                    'org' => get_string('licence_type_org', 'block_edupublisher'),
                    'course' => get_string('licence_type_course', 'block_edupublisher'),
                    'user' => get_string('licence_type_user', 'block_edupublisher'),
                );
                $mform->addElement('select', 'type', get_string('licence_type', 'block_edupublisher'), $types, array('style' => 'width: 100%'));
                $mform->setType('type', PARAM_TEXT);

                $mform->addElement('html', '<p>' . get_string('licence_amount_leaveempty', 'block_edupublisher') . ' (leave amount empty to enter licencekeys manually)</p>');
                $mform->addElement('text', 'amount', get_string('licence_amount', 'block_edupublishser'), array('type' => 'number', 'min' => 1, 'style' => 'width: 100%'));
                $mform->setType('amount', PARAM_INT);
                $mform->addHelpButton('amount', '<a href="#">test</a>');
        }



        $this->add_action_buttons();
    }
}
