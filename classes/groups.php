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
 * @copyright  2022 Center for Learningmanagement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;

defined('MOODLE_INTERNAL') || die;

class groups {
    /**
     * Add required settings to admin settings page.
     * @param settings the node settings are attached to.
    **/
    public static function admin_settings_page($settings) {
        global $ADMIN, $DB;
        if (empty($ADMIN) || !$ADMIN->fulltree) {
            return;
        }

        $heading = get_string('groups:settings', 'block_edupublisher');
        $text    = get_string('groups:settings:description', 'block_edupublisher');
        $settings->add(new \admin_setting_heading('block_edupublisher_groups', '', "<h3>$heading</h3><p>$text</p>"));

        $settings->add(
            new \admin_setting_configcheckbox(
                'block_edupublisher/groupsenabled',
                get_string('groups:enabled', 'block_edupublisher'),
                get_string('groups:enabled_desc', 'block_edupublisher'),
                0
            )
        );

        $settings->add(
            new \admin_setting_configtextarea(
                'block_edupublisher/groupsdomains',
                get_string('groups:domains', 'block_edupublisher'),
                get_string('groups:domains_desc', 'block_edupublisher'),
                '',
                PARAM_TEXT
            )
        );

        $sql = "SELECT r.*
                FROM {role} AS r, {role_context_levels} AS rcl
                WHERE r.id=rcl.roleid
                    AND rcl.contextlevel = 50
                ORDER BY r.name ASC";
        $roles = $DB->get_records_sql($sql, []);
        $options = array();
        foreach($roles AS $role) {
            $options[$role->id] = (!empty($role->name) ? $role->name : $role->shortname);
        }

        $settings->add(
            new \admin_setting_configselect(
                'block_edupublisher/groupsrolestudent',
                get_string('groups:rolestudent', 'block_edupublisher'),
                get_string('groups:rolestudent_desc', 'block_edupublisher'),
                get_config('block_edupublisher', 'groupsrolestudent'),
                $options,
            )
        );

        $settings->add(
            new \admin_setting_configselect(
                'block_edupublisher/groupsroleteacher',
                get_string('groups:roleteacher', 'block_edupublisher'),
                get_string('groups:roleteacher_desc', 'block_edupublisher'),
                get_config('block_edupublisher', 'groupsroleteacher'),
                $options,
            )
        );
    }
}
