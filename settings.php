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

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    if ($ADMIN->fulltree) {
        $link = implode("\n", array(
            '<div class="form-item row">',
            '  <div class="form-label col-sm-3 text-sm-right">' . get_string('category', 'block_edupublisher') . '</div>',
            '  <div class="form-setting col-sm-9">',
            '    <a class="btn btn-secondary btn-block" href="' . $CFG->wwwroot . '/blocks/edupublisher/categories.php">' . get_string('show') . '</a>',
            '  </div>',
            '  <div class="form-defaultinfo"></div>',
            '  <div class="form-description mt-3"></div>',
            '</div>',
        ));
        $settings->add(new admin_setting_heading('block_edupublisher_category', '', $link));


        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        $definition = \block_edupublisher\lib::get_channel_definition();
        $channels = array_keys($definition);
        foreach ($channels as $channel) {
            $settings->add(new admin_setting_configtext('block_edupublisher/channelkey_' . $channel, get_string($channel . '_fetchchannel', 'block_edupublisher'), '', md5(time() . rand(0, 1000)), PARAM_TEXT, 32));
        }

        $roles = $DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = 50 ORDER BY r.name ASC', array());
        $options = array();
        foreach ($roles as $role) {
            $options[$role->id] = (!empty($role->name) ? $role->name : $role->shortname);
        }

        $settings->add(new admin_setting_configselect('block_edupublisher/defaultrolestudent', get_string('defaultrolestudent', 'block_edupublisher'),
            get_string('defaultrolestudent:description', 'block_edupublisher'), get_config('block_edupublisher', 'defaultrolestudent'), $options));

        $settings->add(new admin_setting_configselect('block_edupublisher/defaultroleteacher', get_string('defaultroleteacher', 'block_edupublisher'),
            get_string('defaultroleteacher:description', 'block_edupublisher'), get_config('block_edupublisher', 'defaultroleteacher'), $options));

        // Open to guests?
        $settings->add(new admin_setting_configcheckbox('block_edupublisher/allowguests', get_string('allowguests', 'block_edupublisher'), get_string('allowguests_desc', 'block_edupublisher'), 0));

        // ENABLE COMMERCIAL CONTENT
        $settings->add(new admin_setting_configcheckbox('block_edupublisher/enablecommercial', get_string('enablecommercial', 'block_edupublisher'), get_string('enablecommercial_desc', 'block_edupublisher'), 1));

        // Allow subcourses
        $settings->add(new admin_setting_configcheckbox('block_edupublisher/allowsubcourses', get_string('allowsubcourses', 'block_edupublisher'), get_string('allowsubcourses_desc', 'block_edupublisher'), 0));


        $defaulttemplate = $OUTPUT->render_from_template('block_edupublisher/mail', array('content' => '{{{content}}}', 'subject' => '{{{subject}}}'));
        $settings->add(new admin_setting_configtextarea('block_edupublisher/mail_template', get_string('mail_template', 'block_edupublisher'),
            get_string('mail_template:description', 'block_edupublisher'), $defaulttemplate));

        $options = array();
        $formatplugins = \core_plugin_manager::instance()->get_plugins_of_type('format');
        foreach ($formatplugins as $formatplugin) {
            $options[$formatplugin->name] = get_string('pluginname', 'format_' . $formatplugin->name);
        }

        //$sortorder = array_flip(array_keys($formatplugins));
        $settings->add(
            new admin_setting_configselect(
                'block_edupublisher/externalsources_courseformat',
                get_string('externalsources:courseformat', 'block_edupublisher'),
                get_string('externalsources:courseformat:description', 'block_edupublisher'),
                'topics',
                $options,
                PARAM_ALPHANUM
            )
        );

        \block_edupublisher\groups::admin_settings_page($settings);
    }
}
