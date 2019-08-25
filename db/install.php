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
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_block_edupublisher_install(){
    global $CFG, $DB;
    require_once($CFG->dirroot . '/lib/accesslib.php');
    $roleid = create_role('eduPublisher eTapas Maintainer', 'edupublisher_maintainer_etapas', 'Role to allow users to manage etapas in edupublisher');
    if ($roleid > 0) {
        set_role_contextlevels($roleid, array(CONTEXT_SYSTEM));
        assign_capability('block/edupublisher:canuse', CAP_ALLOW, $roleid, context_system::instance(), false);
        assign_capability('block/edupublisher:manageetapas', CAP_ALLOW, $roleid, context_system::instance(), false);

    }
}
