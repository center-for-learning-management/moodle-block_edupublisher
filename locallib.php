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
 * @copyright  2020 Center for Learning Management (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;

defined('MOODLE_INTERNAL') || die;

class lib {

    /**
     * Enrols users to specific courses
     * @param courseids array containing courseid or a single courseid
     * @param userids array containing userids or a single userid
     * @param roleid roleid to assign, or -1 if wants to unenrol
     * @return true or false
    **/
    public static function course_manual_enrolments($courseids, $userids, $roleid, $remove) {
        global $CFG, $DB, $reply;
        if (!isset($reply)) $reply = array();
        //print_r($courseids); print_r($userids); echo $roleid;
        if (!is_array($courseids)) $courseids = array($courseids);
        if (!is_array($userids)) $userids = array($userids);
        // Retrieve the manual enrolment plugin.
        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            return false;
        }
        $failures = 0;
        foreach ($courseids AS $courseid) {
            // Check if course exists.
            $course = $DB->get_record('course', array('id' => $courseid), '*', IGNORE_MISSING);
            $context = \context_course::instance($course->id);
            //$course = get_course($courseid);
            if (empty($course->id)) continue;
            // Check manual enrolment plugin instance is enabled/exist.
            $instance = null;
            $enrolinstances = enrol_get_instances($courseid, false);
            $reply['enrolinstances'] = $enrolinstances;
            foreach ($enrolinstances as $courseenrolinstance) {
              if ($courseenrolinstance->enrol == "manual") {
                  $instance = $courseenrolinstance;
                  break;
              }
            }
            if (empty($instance)) {
                // We have to add a "manual-enrolment"-instance
                $fields = array(
                    'status' => 0,
                    'roleid' => get_config('block_eduvidual', 'defaultrolestudent'),
                    'enrolperiod' => 0,
                    'expirynotify' => 0,
                    'expirytreshold' => 0,
                    'notifyall' => 0
                );
                require_once($CFG->dirroot . '/enrol/manual/lib.php');
                $emp = new enrol_manual_plugin();
                $reply['createinstance'] = true;
                $instance = $emp->add_instance($course, $fields);
            }
            $reply['enrolinstance'] = $instance;
            if (empty($instance)) {
                $failures++;
            } else {
                if ($instance->status == 1) {
                    // It is inactive - we have to activate it!
                    $data = (object)array('status' => 0);
                    require_once($CFG->dirroot . '/enrol/manual/lib.php');
                    $emp = new enrol_manual_plugin();
                    $reply['updateinstance'] = true;
                    $emp->update_instance($instance, $data);
                    $instance->status = $data->status;
                }
                foreach ($userids AS $userid) {
                    if (!empty($remove)) {
                        role_unassign($roleid, $userid, $context->id);
                        // If this was the last role, we unenrol completely
                        $roles = get_user_roles($context, $userid);
                        $foundatleastone = false;
                        foreach($roles AS $role) {
                            if ($role->contextid == $context->id) {
                                $foundatleastone = true;
                                break;
                            }
                        }

                        if (!$foundatleastone) {
                            $enrol->unenrol_user($instance, $userid);
                        }
                    } else {
                        $enrol->enrol_user($instance, $userid, $roleid, 0, 0, ENROL_USER_ACTIVE);
                    }
                }
            }
        }
        return ($failures == 0);
    }
}

/**
 * List all subjectareas in an localized, alphabetically sorted array.
 * @param selectedarea mark a subjectarea as selected.
 **/
function get_subjectareas_sorted($selectedarea = "") {
    global $CFG;
    require($CFG->dirroot . '/blocks/edupublisher/classes/channel_definition.php');
    $locs = array();
    $loc_key = array();

    foreach ($definition['default']['subjectarea']['options'] AS $key => $localized) {
        $locs[] = $localized;
        $loc_key[$localized] = $key;
    }
    sort($locs);
    $sorted = array();
    foreach ($locs AS $loc) {
        $sorted[] = array(
            'key' => $loc_key[$loc],
            'name' => $loc,
            'isselected' => ($loc_key[$loc] == $selectedarea)
        );
    }
    return $sorted;
}

/**
 * List all schoollevels in an localized, alphabetically sorted array.
 * @param selectedarea mark a schoollevel as selected.
 **/
function get_schoollevels_sorted($selectedarea = "") {
    global $CFG;

    require($CFG->dirroot . '/blocks/edupublisher/classes/channel_definition.php');
    $locs = array();
    $loc_key = array();

    foreach ($definition['default']['schoollevel']['options'] AS $key => $localized) {
        $locs[] = $localized;
        $loc_key[$localized] = $key;
    }
    sort($locs);
    $sorted = array();
    foreach ($locs AS $loc) {
        $sorted[] = array(
            'key' => $loc_key[$loc],
            'name' => $loc,
            'isselected' => ($loc_key[$loc] == $selectedarea)
        );
    }
    return $sorted;
}
