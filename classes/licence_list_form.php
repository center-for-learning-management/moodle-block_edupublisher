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

class licence_list_form extends \moodleform {
    function definition() {
        global $DB, $publisherid;

        $mform = $this->_form;
        $mform->addElement('hidden', 'publisherid', 0);
        $mform->setType('publisherid', PARAM_INT);
        $mform->addElement('hidden', 'action', '');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('html', '<a href="/blocks/edupublisher/pages/licence.php?publisherid=' . $publisherid . '" class="btn btn-secondary">' . get_string('licence_back_to_dashboard', 'block_edupublisher') . '</a>');

        $sql = "SELECT * FROM {block_edupublisher_lic} WHERE publisherid=? ORDER BY created DESC, licencekey ASC";
        $licences = $DB->get_records_sql($sql, array($publisherid));
        $flag = ''; $group = 0;

        foreach ($licences AS $licence) {
            $_flag = date('Y-m-d h:i', $licence->created);
            if ($flag != $_flag) {
                if (!empty($flag)) {
                    $this->add_checkbox_controller($group, NULL, array('style' => 'font-weight: bold;'), 1);
                }
                $flag = $_flag;
                $group++;
                $mform->addElement('html', '<h3>' . $flag . '</h3>');
            }
            $mform->addElement('advcheckbox', 'licenceids[' . $licence->id . ']', $licence->licencekey, '', array('group' => $group), array(0, 1));
        }
        $this->add_checkbox_controller($group, NULL, array('style' => 'font-weight: bold;'), 1);
        $this->add_action_buttons();
    }
}
