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
 * @copyright  2020 Center for Learningmanagement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;

defined('MOODLE_INTERNAL') || die;

class lib {
    /**
     * Ensures that within a context an instance of block_edupublisher exists.
     * @param
    **/
    public static function add_to_context($context) {
        global $DB;
        $count = $DB->count_records('block_instances', array('blockname' => 'edupublisher', 'parentcontextid' => $context->id));
        if ($count == 0) {
            // Create edupublisher-block in targetcourse.
            $blockdata = (object) array(
                'blockname' => 'edupublisher',
                'parentcontextid' => $context->id,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'course-view-*',
                'defaultregion' => 'side-post',
                'defaultweight' => -10,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time(),
            );
            $DB->insert_record('block_instances', $blockdata);
        }
    }
    /**
     * Determines whether or not the user can create groups.
     * @return boolean
     */
    public static function can_create_groups() {
        if (has_capability('block/edupublisher:cancreategroups', \context_system::instance())) {
            return true;
        }
        // Test if users e-mail domain matches.
        global $DB, $USER;
        $domains = get_config('block_edupublisher', 'groupsdomains');
        $usermail = explode('@', $USER->email);
        if (strpos($domains, '@' . $usermail[1]) > -1) {
            return true;
        }
        return false;
    }
    /**
     * Create a temporary directory and return its path.
     * @return path to tempdir.
     */
    public static function get_tempdir() {
        global $CFG;
        $dir = $CFG->tempdir . '/edupublisher-coursefiles';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        return $dir;
    }
    /**
     * Enrols users to specific courses
     * @param courseids array containing courseid or a single courseid
     * @param userids array containing userids or a single userid
     * @param roleid roleid to assign, or -1 if wants to unenrol
     * @return true or false
    **/
    public static function course_manual_enrolments($courseids, $userids, $roleid, $remove = 0) {
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
                    'roleid' => get_config('local_eduvidual', 'defaultrolestudent'),
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


    /**
     * Hold the path of visited packages in cache and
     * receive danube.ai-recommendations.
     * @param packageid that is visited.
     */
    public static function get_danubeai_recommendations($packageid = 0) {
        $danubeai_apikey = get_config('block_edupublisher', 'danubeai_apikey');
        if (!empty($danubeai_apikey)) {
            $cache = \cache::make('block_edupublisher', 'packagepath');
            $path = explode(',', $cache->get('path'));
            if (!empty($packageid)) {
                $path[] = $packageid;
                $cache->set('path', implode(',', $path));
            }

            $pathdata = array();
            foreach ($path AS $p) {
                $pathdata[] = array('page' => $p);
            }
            $data = array(
                'query' => 'mutation ($data: RecommendationInputData!) { danubeRecommendation(data: $data) { correlatedData } }',
                'variables' => array(
                    'data' => array('data' => json_encode($pathdata, JSON_NUMERIC_CHECK)),
                    'n' => 3,
                ),
            );

            $url = "https://api.danube.ai/graphql";
            $content = json_encode($data);

            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json", "apitoken: Bearer $danubeai_apikey"));
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
            $json_response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $response = json_decode($json_response, true);
        }
    }
    /**
     * Load exacomp competencies for this package.
     * @param package object.
     */
    public static function exacompetencies(&$package) {
        global $CFG, $DB;
        // Get competencies.
        $package->default_exacompdatasources = array();
        $package->default_exacompids = array();
        $package->default_exacomptitles = array();
        $flagfound = array();

        // 1. Moodle competencies
        $sql = "SELECT c.id,c.*
                    FROM {competency} c, {competency_modulecomp} mc, {course_modules} cm
                    WHERE cm.course=? AND cm.id=mc.cmid AND mc.competencyid=c.id";
        $competencies = $DB->get_records_sql($sql, array($package->course));
        $supportstranslator = file_exists($CFG->dirroot . '/local/komettranslator/version.php');
        foreach ($competencies as $competence) {
            if ($supportstranslator) {
                // Try mapping to exacomp.
                $mapping = \local_komettranslator\locallib::mapping_internal('descriptor', $competence->id);
                if (!empty($mapping->id) && empty($flagfound[$mapping->sourceid . '_' . $mapping->itemid])) {
                    $package->default_exacomptitles[] = !empty($competence->description) ? $competence->description : $competence->shortname;
                    $package->default_exacompdatasources[] = $mapping->sourceid;
                    $package->default_exacompsourceids[] = $mapping->itemid;
                    $flagfound[$mapping->sourceid . '_' . $mapping->itemid] = true;
                }
            }
        }
        // 2. Exacomp competencies
        $sql = "SELECT ecd.id id,ecd.title title, ecd.sourceid sourceid, ecd.source source
                    FROM {block_exacompdescriptors} ecd,
                         {block_exacompdescrexamp_mm} ecde,
                         {block_exacompexamples} ecex
                    WHERE ecex.courseid=?
                        AND ecex.id=ecde.exampid
                        AND ecde.descrid=ecd.id
                    ORDER BY ecd.title ASC";
        $competencies = $DB->get_records_sql($sql, array($package->course));

        foreach($competencies AS $competence) {
            $source = $DB->get_record('block_exacompdatasources', array('id' => $competence->source));
            if (!empty($source->id) && empty($flagfound[$source->source . '_' . $competence->sourceid])) {
                $package->default_exacompdatasources[] = $source->source;
                $package->default_exacompsourceids[] = $competence->sourceid;
                $package->default_exacomptitles[] = $competence->title;
                $flagfound[$source->source . '_' . $competence->sourceid] = true;
            }
        }

        $package->etapas_kompetenzen = nl2br(implode("\n", $package->default_exacomptitles));
    }

    /**
     * Load all roles of a user in a context and check if it contains a given roleid.
     * @param context the context to check.
     * @param roleid the roleid to search for.
     * @param userorid the user as integer or object. If non given, use $USER->id.
     */
    public static function has_role($context, $roleid, $userorid = null) {
        global $USER;
        if (is_object($userorid)) $userid = $userorid->id;
        elseif (is_numeric($userorid)) $userid = $userorid;
        else $userid = $USER->id;
        $roles = \get_user_roles($context, $userid);
        foreach ($roles as $role) {
            if ($role->roleid == $roleid) return true;
        }
        return false;
    }

    /**
     * Log that a user visited a course-page of a package.
     * @param packageid that is visited.
     * @param action String, either 'viewed', 'enrolled', 'unenrolled' or 'cloned'
     */
    public static function log_user_visit($packageid, $action) {
        if (empty($packageid)) return;
        // Ensure the action is a valid value.
        if (!in_array($action, array('viewed', 'enrolled', 'unenrolled', 'cloned'))) return;

        global $DB, $USER;
        // The viewed action is only logged if it does not double the last entry.
        if ($action == 'viewed') {
            // If we use danube.ai use a cache to track the visited packages.
            // Disable danube.ai
            // self::get_danubeai_recommendations($packageid);
            $sql = "SELECT *
                        FROM {block_edupublisher_log}
                        WHERE userid=?
                            AND viewed=1
                        ORDER BY id DESC
                        LIMIT 0,1";
            $lastrecord = $DB->get_record_sql($sql, array($USER->id));
            if (!empty($lastrecord->packageid) && $lastrecord->packageid == $packageid) return;
        }

        // Log this event now.
        $data = array(
            'packageid' => $packageid,
            'userid' => $USER->id,
            'timeentered' => time(),
            $action => 1,
        );
        $DB->insert_record('block_edupublisher_log', $data);
    }
    /**
     * Sets the capabilities of a course to allow course imports.
     * @param courseid.
     * @param trigger true if we enable the package, false if we disable it.
    **/
    public static function package_setcaps($courseid, $trigger) {
        global $DB, $USER;

        $ctxcourse = \context_course::instance($courseid);
        $capabilities = array(
            'moodle/backup:backupcourse',
            'moodle/backup:backuptargetimport',
        );
        $roles = array(
            7,
            7,
        );
        $contexts = array(
            $ctxcourse,
            $ctxcourse,
        );
        $permission = ($trigger) ? CAP_ALLOW : CAP_INHERIT;
        for ($a = 0; $a < count($capabilities); $a++) {
            \role_change_permission($roles[$a], $contexts[$a], $capabilities[$a], $permission);
        }
    }

    /**
     * Checks if a package has a coursebackup and extracts to backuptempdir for restore.
     * @param package
     */
    public static function prepare_restore($package) {
        global $CFG, $DB, $OUTPUT;
        if ($package->backuped == 0) {
            $alert = array(
                'content' => \get_string('coursebackup:missing', 'block_edupublisher'),
                'type' => 'danger',
                'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->course,
            );
            echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
            return;
        }

        $course = $DB->get_record('course', array('id' => $package->course), '*', MUST_EXIST);
        $ctx = \context_course::instance($course->id);

        $fs = \get_file_storage();
        $file = $fs->get_file($ctx->id, 'block_edupublisher', 'coursebackup', 0, '/', 'coursebackup.mbz');

        if (!$file) {
            $alert = array(
                'content' => \get_string('coursebackup:notfound', 'block_edupublisher'),
                'type' => 'danger',
                'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->course,
            );
            echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
            return;
        }

        $fp = \get_file_packer('application/vnd.moodle.backup');
        $backuptempdir = \make_backup_temp_directory('edupublisher' . $package->id);
        if (!is_dir($backuptempdir) || !file_exists($backuptempdir . '/moodle_backup.xml')) {
            $file->extract_to_pathname($fp, $backuptempdir);
        }

        return 'edupublisher' . $package->id;
    }
}
