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

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

class block_edupublisher extends block_base {
    /**
     * Gets an existing package by its courseid.
     * @param courseid the courseid.
     */
    public static function get_package_by_courseid($courseid, $strictness = MUST_EXIST) {
        global $DB;
        $item = $DB->get_record('block_edupublisher_packages', array('course' => $courseid), '*', $strictness);
        if (!empty($item->id)) {
            return self::get_package($item->id);
        }
    }
    /**
     * Creates an empty package and fills with data from course.
     * This is used when we create a new package.
    **/
    public static function get_package_from_course($courseid){
        global $DB, $USER;
        $package = self::get_package(0);
        $course = get_course($courseid);
        $package->active = 0;
        $package->sourcecourse = $course->id;
        $package->default_title = $course->fullname;
        $package->default_authorname = $USER->firstname . ' ' . $USER->lastname;
        $package->default_authormail = $USER->email;
        $package->default_summary = $course->summary;

        return $package;
    }
    /**
     * Gets a publisher from database.
     * @param publisherid
     */
    public static function get_publisher($publisherid) {
        global $DB, $USER;
        $publisher = $DB->get_record('block_edupublisher_pub', array('id' => $publisherid), '*', IGNORE_MISSING);
        if (empty($publisher->id)) return null;
        $is_coworker = $DB->get_record('block_edupublisher_pub_user', array('publisherid' => $publisherid, 'userid' => $USER->id));
        $publisher->is_coworker = (!empty($is_coworker->userid) && $is_coworker->userid == $USER->id);
        // Load Logo of publisher.
        $fs = get_file_storage();
        $context = context_system::instance();
        $files = $fs->get_area_files($context->id, 'block_edupublisher', 'publisher_logo', $publisherid);
        foreach ($files as $f) {
            if (empty(str_replace('.', '', $f->get_filename()))) continue;
            $publisher->publisher_logo = '' . moodle_url::make_pluginfile_url($f->get_contextid(), $f->get_component(), $f->get_filearea(), $f->get_itemid(), $f->get_filepath(), $f->get_filename(), false);
            break;
        }
        return $publisher;
    }

    /**
     * Load a specific comment and enhance data.
     * @param id of comment
     */
    public static function load_comment($id) {
        global $CFG, $DB;
        $comment = $DB->get_record('block_edupublisher_comments', array('id' => $id));
        $user = $DB->get_record('user', array('id' => $comment->userid));
        $comment->userfullname = fullname($user);
        if (!empty($comment->linkurl)) {
            $comment->linkurl = new \moodle_url($comment->linkurl);
        }
        $ctx = context_user::instance($comment->userid);
        $comment->userpictureurl = $CFG->wwwroot . '/pluginfile.php/' . $ctx->id . '/user/icon';
        $comment->wwwroot = $CFG->wwwroot;
        return $comment;
    }
    /**
     * Load all comments for a package.
     * @param packageid of package
     * @param includeprivate whether or not to include private  communication
     * @param sortorder ASC or DESC
     */
    public static function load_comments($packageid, $private = false, $sortorder = 'ASC') {
        global $DB;
        if ($sortorder != 'ASC' && $sortorder != 'DESC') $sortorder = 'ASC';
        $sql = "SELECT id
                    FROM {block_edupublisher_comments}
                    WHERE package=?";
        if (!$private) {
            $sql .= " AND ispublic=1";
        }
        $sql .= ' ORDER BY id ' . $sortorder;
        $commentids = array_keys($DB->get_records_sql($sql, array($packageid)));
        $comments = array();
        foreach ($commentids AS $id) {
            $comments[] = self::load_comment($id);
        }
        return $comments;
    }
    /**
     * Grants or revokes a role from a course.
     * @param courseids array with courseids
     * @param userids array with userids
     * @param role -1 to remove user, number of role or known identifier (defaultroleteacher, defaultrolestudent) to assign role.
     */
    public static function role_set($courseids, $userids, $role) {
        if ($role == 'defaultroleteacher') $role = get_config('block_edupublisher', 'defaultroleteacher');
        if ($role == 'defaultrolestudent') $role = get_config('block_edupublisher', 'defaultrolestudent');
        if (empty($role)) return;

        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            return false;
        }

        global $DB;
        foreach ($courseids AS $courseid) {
            // Check if course exists.
            $course = get_course($courseid);
            if (empty($course->id)) continue;
            // Check manual enrolment plugin instance is enabled/exist.
            $instance = null;
            $enrolinstances = enrol_get_instances($courseid, false);
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
                    'roleid' => get_config('block_edupublisher', 'defaultrolestudent'),
                    'enrolperiod' => 0,
                    'expirynotify' => 0,
                    'expirytreshold' => 0,
                    'notifyall' => 0
                );
                require_once($CFG->dirroot . '/enrol/manual/lib.php');
                $emp = new enrol_manual_plugin();
                $instance = $emp->add_instance($course, $fields);
            }
            if ($instance->status == 1) {
                // It is inactive - we have to activate it!
                $data = (object)array('status' => 0);
                require_once($CFG->dirroot . '/enrol/manual/lib.php');
                $emp = new enrol_manual_plugin();
                $emp->update_instance($instance, $data);
                $instance->status = $data->status;
            }
            foreach ($userids AS $userid) {
                if ($role == -1) {
                    $enrol->unenrol_user($instance, $userid);
                } else {
                    $enrol->enrol_user($instance, $userid, $role, 0, 0, ENROL_USER_ACTIVE);
                }
            }
        }
    }

    /**
     * Enables or disables guest access to a course.
     * @param courseid the course id
     * @param setto 1 (default) to enable, 0 to disable access.
     */
    public static function toggle_guest_access($courseid, $setto = 1) {
        global $CFG;

        require_once($CFG->dirroot . '/enrol/guest/lib.php');
        $course = \get_course($courseid);
        $fields = array(
            'status' => (empty($setto) ? 1 : 0), // status in database reversed
            'password' => '',
        );
        $gp = new \enrol_guest_plugin();
        if (!empty($setto)) {
            $gp->add_instance($course, $fields);
        } else {
            require_once($CFG->dirroot . '/lib/enrollib.php');
            $instances = \enrol_get_instances($courseid, false);
            foreach ($instances as $instance) {
                if ($instance->enrol != 'guest') continue;
                $gp->delete_instance($instance);
            }
        }
    }
    /**
     * Gets the user picture and returns it as base64 encoded string.
     * @param userid
     * @return picture base64 encoded
     */
    public static function user_picture_base64($userid) {
        $context = context_user::instance($userid);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'user', 'icon');
        $find = array('f1.jpg', 'f1.png');
        foreach ($files as $f) {
            if (in_array($f->get_filename(), $find)) {
                $extension = explode(".", $f->get_filename());
                $extension = $extension[count($extension) - 1];
                return 'data:image/' . $extension . ';base64,' . base64_encode($f->get_content());
            }
        }
        return '';
    }

    public function init() {
        $this->title = get_string('pluginname', 'block_edupublisher');
    }
    public function get_content() {
        global $CFG, $COURSE, $DB, $OUTPUT, $PAGE, $USER;

        $PAGE->requires->css('/blocks/edupublisher/style/main.css');
        $PAGE->requires->css('/blocks/edupublisher/style/ui.css');

        if (!isset($COURSE->id) || $COURSE->id <= 1) {
            return;
        }

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = (object) array(
            'text' => '',
            'footer' => ''
        );

        // 1. in a package-course: show author
        // 2. in a course + trainer permission: show publish link and list packages
        // 3. show nothing

        $context = \context_course::instance($COURSE->id);
        $isenrolled = is_enrolled($context, $USER->id, '', true);
        $canedit = has_capability('moodle/course:update', $context);

        $package = $DB->get_record('block_edupublisher_packages', array('course' => $COURSE->id), '*', IGNORE_MULTIPLE);
        $options = array();
        if (!empty($package->id)) {
            $package = new \block_edupublisher\package($package->id, true);
            if ($package->get('licence', 'default') == 'other') {
                $package->set(get_string('default_licenceother', 'block_edupublisher'), 'licence', 'default');
            }
            if ($package->get('subtype', 'etapas') == 'etapa' && has_capability('block/edupublisher:canseeevaluation', \context_system::instance())) {
                $package->set(true, 'can_see_evaluation');
            }
            // Show use package-button
            $courses = \block_edupublisher\lib::get_courses(null, 'moodle/course:update');
            if (count(array_keys($courses)) > 0) {
                $package->set(true, 'can_import');
                $package->set($allowsubcourses = \get_config('block_edupublisher', 'allowsubcourses') ? 1 : 0, 'allow_subcourses');
            }
            $package->set((is_enrolled($context, null, 'block/edupublisher:canselfenrol')) ? 1 : 0, 'can_unenrol');

            if (!empty($package->get('active', 'etapas')) && !empty($package->get('subtype', 'etapas'))) {
                $package->set(str_replace(array(' ', '.'), '', $package->etapas_subtype), 'graphic', 'etapas');
            }
            $this->content->text .= $OUTPUT->render_from_template('block_edupublisher/block_inpackage', $package->get_flattened());
        } elseif($canedit) {
            $cache = \cache::make('block_edupublisher', 'publish');
            $pendingpublication = $cache->get("pending_publication_$COURSE->id");
            if (empty($pendingpublication)) {
                $cache->set("pending_publication_$COURSE->id", -1);
                $sql = "SELECT *
                            FROM {block_edupublisher_publish}
                            WHERE sourcecourseid = ?
                                OR targetcourseid = ?";
                $pendingpublications = $DB->get_records_sql($sql, [ $COURSE->id, $COURSE->id ]);
                foreach ($pendingpublications as $pendingpublication) {
                    $pendingpublication = $pendingpublication->sourcecourseid;
                    $cache->set("pending_publication_$COURSE->id", $pendingpublication);
                    break;
                }
            }
            $params = (object) [
                'courseid' => $COURSE->id,
                'packages' => array_values($DB->get_records_sql('SELECT * FROM {block_edupublisher_packages} WHERE sourcecourse=? AND (active=1 OR userid=?)', array($COURSE->id, $USER->id))),
                'pendingpublication' => $pendingpublication,
                'uses'     => array_values($DB->get_records_sql('SELECT DISTINCT(package) FROM {block_edupublisher_uses} WHERE targetcourse=?', array($COURSE->id))),
            ];
            $params->haspackages = (count($params->packages) > 0) ? 1 : 0;
            $params->hasuses     = (count($params->uses)     > 0) ? 1 : 0;

            $this->content->text .= $OUTPUT->render_from_template('block_edupublisher/block_canedit', $params);
        }
        return $this->content;
    }
    public function hide_header() {
        return false;
    }
    public function has_config() {
        return true;
    }
    public function instance_allow_multiple() {
        return false;
    }
}
