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

if ($ADMIN->fulltree) {
    if (!function_exists('block_edupublisher_build_categories_tree')) {
        function block_edupublisher_build_categories_tree(&$tree, $categoryid) {
            global $DB;
            $items = $DB->get_records_sql('SELECT id,name,depth FROM {course_categories} WHERE parent=? ORDER BY name ASC', array($categoryid));
            foreach($items AS $item) {
                $tree[$item->id] = str_pad('', ($item->depth - 1) * 2, '>') . $item->name;
                block_edupublisher_build_categories_tree($tree, $item->id);
            }
        }
    }

    //$settings = new admin_settingpage('blockedupublisher', get_string('pluginname', 'block_edupublisher'), 'moodle/site:config');
    $_CATEGORIES = array();
    block_edupublisher_build_categories_tree($_CATEGORIES, 0);
    $settings->add(new admin_setting_configselect('block_edupublisher/category', get_string('category', 'block_edupublisher'), '', 1, $_CATEGORIES));

    require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
    $definition = block_edupublisher::get_channel_definition();
    $channels = array_keys($definition);
    foreach($channels AS $channel) {
        $settings->add(new admin_setting_configtext('block_edupublisher/channelkey_' . $channel, get_string($channel . '_fetchchannel', 'block_edupublisher'), '', md5(time() . rand(0, 1000)), PARAM_TEXT, 32));
    }


    $roles = $DB->get_records_sql('SELECT r.* FROM {role} AS r, {role_context_levels} AS rcl WHERE r.id=rcl.roleid  AND rcl.contextlevel = 50 ORDER BY r.name ASC', array());
    $options = array();
    foreach($roles AS $role) {
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


    $defaulttemplate = $OUTPUT->render_from_template('block_edupublisher/mail', array('content' => '{{{content}}}', 'subject' => '{{{subject}}}'));
    $settings->add(new admin_setting_configtextarea('block_edupublisher/mail_template', get_string('mail_template', 'block_edupublisher'),
                      get_string('mail_template:description', 'block_edupublisher'), $defaulttemplate));


    //$ADMIN->add('blocksettings', $settings);
}
