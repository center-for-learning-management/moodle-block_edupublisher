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

class publisher_form extends \moodleform {
    static $accepted_types = '';
    static $areamaxbytes = 10485760;
    static $maxbytes = 1024*1024;
    static $maxfiles = 1;
    static $subdirs = 0;

    function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('filemanager', 'publisher_logo', get_string('publisher_logo', 'block_edupublisher'), null,
            array(
                'accepted_types' => '',
                'areamaxbytes' => self::$areamaxbytes,
                'maxbytes' => self::$maxbytes,
                'maxfiles' => self::$maxfiles,
                'subdirs' => self::$subdirs,
                 //, 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL
            )
        );

        $this->add_action_buttons();
    }
}
