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
 * @package    local_eduportal
 * @copyright  2022 Austrian Federal Ministry of Education
 * @author     GTN solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;

defined('MOODLE_INTERNAL') || die;

class permissions {
    static function require(array $checks): void {
        foreach ($checks as $key => $ok) {
            if (!$ok) {
                throw new \moodle_exception('no permission: ' . $key);
            }
        }
    }

    static function or_require(array $checks): void {
        $ok = false;
        foreach ($checks as $check) {
            if ($check) {
                $ok = true;
                break;
            }
        }

        $key = '((' . implode(' || ', array_keys($checks)) . ') = false)';

        if (!$ok) {
            throw new \moodle_exception('no permission: ' . $key);
        }
    }

    /**
     * require_login(), but additionally no guests are allowed
     * @return void
     */
    public static function require_login() {
        global $SESSION;

        require_login();

        if (isguestuser()) {
            $SESSION->wantsurl = qualified_me();
            redirect(get_login_url());
        }
    }

    /**
     * is siteadmin?!?
     **/
    public static function is_admin(): bool {
        $sysctx = \context_system::instance();
        return has_capability('moodle/site:config', $sysctx);
    }

    /**
     * Load all roles of a user in a context and check if it contains a given roleid.
     * @param context the context to check.
     * @param roleid the roleid to search for.
     * @param userorid the user as integer or object. If non given, use $USER->id.
     */
    public static function has_role($context, $roleid, $userorid = null): bool {
        global $USER;
        if (is_object($userorid))
            $userid = $userorid->id;
        elseif (is_numeric($userorid))
            $userid = $userorid;
        else $userid = $USER->id;
        $roles = \get_user_roles($context, $userid);
        foreach ($roles as $role) {
            if ($role->roleid == $roleid)
                return true;
        }
        return false;
    }

    /**
     * @param (optional) array of channels we want to check
     * @return true if user is a maintainer
     **/
    public static function is_maintainer($channels = array()): bool {
        if (static::is_admin()) {
            return true;
        }

        $category = get_config('block_edupublisher', 'category');
        $context = \context_coursecat::instance($category);

        return has_capability('block/edupublisher:managedefault', $context);

        // old:
        /*
        $maintainer_default = has_capability('block/edupublisher:managedefault', $context);
        $maintainer_etapas = has_capability('block/edupublisher:manageetapas', $context);
        $maintainer_eduthek = has_capability('block/edupublisher:manageeduthek', $context);

        if (count($channels) == 0) {
            return $maintainer_default || $maintainer_etapas || $maintainer_eduthek;
        }
        if (in_array('default', $channels) && $maintainer_default)
            return true;
        if (in_array('etapas', $channels) && $maintainer_etapas)
            return true;
        if (in_array('eduthek', $channels) && $maintainer_eduthek)
            return true;
        return false;
        */
    }

    /**
     * Indicates if the current user is acting as a publisher for commercial content.
     * @param publisherid (optional) if user is co-worker of a specific publisher.
     * @return true if is publisher or site-admin.
     */
    public static function is_publisher($publisherid = 0): bool {
        global $DB, $USER;

        if (static::is_admin()) {
            return true;
        }

        if (empty($publisherid)) {
            $chk = $DB->get_record('block_edupublisher_pub_user', array('userid' => $USER->id));
        } else {
            $chk = $DB->get_record('block_edupublisher_pub_user', array('userid' => $USER->id, 'publisherid' => $publisherid));
        }
        return (!empty($chk->id) && $chk->id > 0);
    }

    private static function get_role_id_from_identifier(int|string $role): int {
        if (in_array($role, ['defaultrolestudent', 'defaultroleteacher', 'course_role_package_viewing'])) {
            return (int)get_config('block_edupublisher', $role);
        } elseif (is_numeric($role)) {
            return (int)$role;
        } else {
            throw new \moodle_exception("role not allowed: {$role}");
        }
    }

    /**
     * Grants a role to a course.
     * @param int $courseid
     * @param int $userid
     * @param int|string $role number of role or known identifier (defaultroleteacher, defaultrolestudent, course_role_package_viewing)
     */
    public static function role_assign(int $courseid, int $userid, int|string $role) {
        global $CFG;

        require_once("$CFG->dirroot/enrol/manual/lib.php");

        $role = static::get_role_id_from_identifier($role);
        if (!$role) {
            throw new \moodle_exception('role not set');
        }

        $enrol = enrol_get_plugin('manual');
        if (!$enrol) {
            throw new \moodle_exception('enrol_plugin manual not found');
        }

        // Check if course exists.
        $course = get_course($courseid);
        if (!$course) {
            throw new \moodle_exception('course not found');
        }

        // Check manual enrolment plugin instance is enabled/exist.
        $instance = null;
        $enrolinstances = enrol_get_instances($courseid, false);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance = $courseenrolinstance;
                break;
            }
        }
        if (!$instance) {
            // Add a "manual-enrolment"-instance
            $fields = array(
                'status' => 0,
                'roleid' => $role,
                'enrolperiod' => 0,
                'expirynotify' => 0,
                'expirytreshold' => 0,
                'notifyall' => 0,
            );
            $emp = new enrol_manual_plugin();
            $instance = $emp->add_instance($course, $fields);
        }

        if ($instance->status == 1) {
            // It is inactive - activate it!
            $data = (object)array('status' => 0);
            $emp = new enrol_manual_plugin();
            $emp->update_instance($instance, $data);
            $instance->status = $data->status;
        }

        $enrol->enrol_user($instance, $userid, $role, 0, 0, ENROL_USER_ACTIVE);
    }

    /**
     * Revokes a role from a course.
     * @param int $courseid
     * @param int $userid
     * @param int|string $role number of role or known identifier (defaultroleteacher, defaultrolestudent, course_role_package_viewing)
     */
    public static function role_unassign(int $courseid, int $userid, int|string $role) {
        global $DB, $CFG;

        require_once("$CFG->dirroot/enrol/manual/lib.php");

        $role = static::get_role_id_from_identifier($role);
        if (!$role) {
            throw new \moodle_exception('TODO: remove all is not implemented');
        }

        $enrol = enrol_get_plugin('manual');
        if (!$enrol) {
            throw new \moodle_exception('enrol_plugin manual not found');
        }

        // Check if course exists.
        $course = get_course($courseid);
        if (!$course) {
            throw new \moodle_exception('course not found');
        }

        // Check manual enrolment plugin instance is enabled/exist.
        $instance = null;
        $enrolinstances = enrol_get_instances($courseid, false);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance = $courseenrolinstance;
                break;
            }
        }

        role_unassign($role, $userid, \context_course::instance($course->id)->id);

        $remaining_roles = $DB->get_records('role_assignments', array('userid' => $userid, 'contextid' => \context_course::instance($course->id)->id));

        if (!$remaining_roles) {
            // Unenroll the user from the course
            $enrol->unenrol_user($instance, $userid);
        }
    }
}
