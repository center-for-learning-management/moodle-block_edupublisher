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

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/blocks/edupublisher/block_edupublisher.php");

class block_edupublisher_external extends external_api {
    public static function init_import_load_courses_parameters() {
        return new external_function_parameters(array(
            'packageid' => new external_value(PARAM_INT, 'id of package'),
        ));
    }

    /**
     * Return all courses the user has trainer capabilities in.
     * @return list of courses as json encoded string.
     */
    public static function init_import_load_courses($packageid) {
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::init_import_load_courses_parameters(), array('packageid' => $packageid));

        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        $courses = block_edupublisher::get_courses(null, 'moodle/course:update');
        $package = block_edupublisher::get_package($params['packageid']);
        if (!empty($package->commercial_publishas) && $package->commercial_publishas) {
            // The licence must allow us to import into certain courses.
            foreach ($courses AS $courseid => $course) {
                $orgid = 0;

                if (block_edupublisher::uses_eduvidual()) {
                    // This is some functionality specific to a plugin that is not published!
                    $org = \local_eduvidual\locallib::get_org_by_courseid($courseid);
                    $orgid = $org->orgid;
                }
                $sql = "SELECT *
                          FROM
                            {block_edupublisher_lic} l,
                            {block_edupublisher_lic_pack} lp
                          WHERE l.id=lp.licenceid
                            AND lp.packageid=?
                            AND (
                                lp.amounts = -1 OR lp.amounts > 0
                            )
                            AND (
                                (l.target = 3 AND l.redeemid>0 AND l.redeemid = ?)
                                OR
                                (l.target = 2 AND l.redeemid>0 AND l.redeemid = ?)
                                OR
                                (l.target = 1 AND l.redeemid>0 AND l.redeemid = ?)
                            )";
                //$reply['sql'] = $sql;
                //$reply['params'] = array($package->id, $USER->id, $params['courseid'], $orgid);
                $licence = $DB->get_records_sql($sql, array($package->id, $USER->id, $courseid, $orgid));
                if (empty($licence->id)) {
                    // No licence for this course.
                    unset($courses[$courseid]);
                };
            }
        }

        // Re-sort by name.
        $_courses = array();
        foreach ($courses AS $course) {
            $_courses[$course->fullname . '_' . $course->id] = $course;
        }
        return json_encode(array('courses' => array_reverse(array_values($_courses))));
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function init_import_load_courses_returns() {
        return new external_value(PARAM_RAW, 'All courses as JSON-string');
    }

    public static function init_import_load_sections_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'id of course'),
        ));
    }

    /**
     * Return all sections of a course.
     * The user has to be enrolled in the course.
     * @param courseid id of course.
     * @return list of courses as json encoded string.
     */
    public static function init_import_load_sections($courseid) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        $params = self::validate_parameters(self::init_import_load_sections_parameters(), array('courseid' => $courseid));

        $course = $DB->get_record('course', array('id' => $params['courseid']));
        $context = context_course::instance($course->id);
        if (is_enrolled($context, $USER->id, '', true)) {
            $sections = array_values($DB->get_records('course_sections', array('course' => $course->id)));
            return json_encode(array('sections' => $sections));
        } else {
            return json_encode(array('error' => 'no permission'));
        }
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function init_import_load_sections_returns() {
        return new external_value(PARAM_RAW, 'All sections as JSON-string');
    }
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function licence_generate_parameters() {
        return new external_function_parameters(array(
            'amount' => new external_value(PARAM_INT, 'amount of licences'),
            'publisherid' => new external_value(PARAM_INT, 'id of publisher'),
        ));
    }

    /**
     * Generate licence-keys without storing them!
     * @return list of licencekeys as json encoded string.
     */
    public static function licence_generate($amount, $publisherid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        $params = self::validate_parameters(self::licence_generate_parameters(), array('amount' => $amount, 'publisherid' => $publisherid));
        if (block_edupublisher::is_admin() || block_edupublisher::is_publisher($params['publisherid'])) {
            $licencekeys = array();
            $pre = $params['publisherid'];
            while(count($licencekeys) < $params['amount']) {
                $code = substr(md5(rand(0, 9999) . time()), 0, 10);
                $licencekey = $pre . '-' . $code;
                $chk = $DB->get_record('block_edupublisher_lic', array('licencekey' => $licencekey));
                if (!$chk) $licencekeys[] = $licencekey;
            }

            return implode(' ', $licencekeys);
        } else {
            return json_encode(array('error' => 'no permission'));
        }
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function licence_generate_returns() {
        return new external_value(PARAM_RAW, 'All generated licencekeys as JSON-string');
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function licence_generatenow_parameters() {
        return new external_function_parameters(array(
            'amount' => new external_value(PARAM_INT, 'amount of usages'),
            'licencekeys' => new external_value(PARAM_TEXT, 'licencekeys delimited by space'),
            'type' => new external_value(PARAM_TEXT, 'type of licence - user, course or org'),
            'publisherid' => new external_value(PARAM_INT, 'id of publisher'),
        ));
    }

    /**
     * Generate licence-keys without storing them!
     * @return list of licencekeys as json encoded string.
     */
    public static function licence_generatenow($amount, $licencekeys, $type, $publisherid) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        $params = self::validate_parameters(self::licence_generatenow_parameters(), array('amount' => $amount, 'licencekeys' => $licencekeys, 'type' => $type, 'publisherid' => $publisherid));
        if (block_edupublisher::is_admin() || block_edupublisher::is_publisher($params['publisherid'])) {
            $types = array('course', 'org', 'user');
            if (in_array($params['type'], $types)) {
                $licencekeys = explode(' ', $params['licencekeys']);
                $createdkeys = array();
                $failedkeys = array();
                foreach ($licencekeys AS $licencekey) {
                    $chk = $DB->get_record('block_edupublisher_lic', array('licencekey' => $licencekey));
                    if (!$chk) {
                        $DB->insert_record('block_edupublisher_lic', array(
                            'publisherid' => $params['publisherid'],
                            'userid' => $USER->id,
                            'licencekey' => $licencekey,
                            'type' => $params['type'],
                            'amount' => $params['amount']
                        ));
                    } else {

                    }
                }
                $licencekeys = array();
                $pre = substr(md5($params['publisherid']), 0, 10);
                while(count($licencekeys) < $params['amount']) {
                    $code = substr(md5(rand(0, 9999) . time()), 0, 10);
                    $licencekey = $pre . '-' . $code;
                    $chk = $DB->get_record('block_edupublisher_lic', array('licencekey' => $licencekey));
                    if (!$chk) $licencekeys[] = $licencekey;
                }

                return implode(' ', $licencekeys);
            } else {
                return json_encode(array('error' => 'invalid type'));
            }

        } else {
            return json_encode(array('error' => 'no permission'));
        }
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function licence_generatenow_returns() {
        return new external_value(PARAM_RAW, 'All stored licencekeys as JSON-string');
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function licence_list_parameters() {
        return new external_function_parameters(array(
            'publisherid' => new external_value(PARAM_INT, 'id of publisher'),
        ));
    }

    /**
     * Get list of licences
     * @return list of packages as json encoded string.
     */
    public static function licence_list($publisherid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        $params = self::validate_parameters(self::licence_list_parameters(), array('publisherid' => $publisherid));
        if (block_edupublisher::is_admin() || block_edupublisher::is_publisher($params['publisherid'])) {
            $licences = $DB->get_records_sql('SELECT * FROM {block_edupublisher_lic} WHERE publisherid=? ORDER BY licencekey ASC', array($params['publisherid']));
            return json_encode($licences, JSON_NUMERIC_CHECK);
        } else {
            return json_encode(array('error' => 'no permission'));
        }
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function licence_list_returns() {
        return new external_value(PARAM_RAW, 'All licencekeys as JSON-string');
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function licence_redeem_parameters() {
        return new external_function_parameters(array(
            'licencekey' => new external_value(PARAM_TEXT, 'licence key'),
            'targetid' => new external_value(PARAM_INT, 'target id'),
        ));
    }

    /**
     * Redeem a licence.
     * @param licencekey
     * @param targetid
     * @return target of packages as json encoded string.
     */
    public static function licence_redeem($licencekey, $targetid) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        $params = self::validate_parameters(self::licence_redeem_parameters(), array('licencekey' => $licencekey, 'targetid' => $targetid));

        $lic = $DB->get_record('block_edupublisher_lic', array('licencekey' => $params['licencekey']));
        $result = array('licencekey' => $params['licencekey'], 'options' => array());
        if (empty($lic->id)) {
            $result['heading'] = get_string('error');
            $result['error'] = get_string('licence_invalid', 'block_edupublisher');
        } elseif (!empty($lic->redeemid)) {
            $result['heading'] = get_string('error');
            $result['error'] = get_string('licence_already_redeemed', 'block_edupublisher');
        } elseif($params['targetid'] > 0) {
            $lic->redeemid = $params['targetid'];
            $DB->update_record('block_edupublisher_lic', $lic);
            $result['heading'] = get_string('success');
            $result['success'] = true;
        } else {
            // Get possible targets for licencekey.
            switch ($lic->target) {
                case 1: // org, only in use for local_eduvidual
                    $result['heading'] = get_string('licence_target_org', 'block_edupublisher');
                    $orgs = \local_eduvidual\locallib::get_organisations('teacher');
                    foreach ($orgs AS $org) {
                        $result['options'][] = array(
                            'id' => $org->orgid,
                            'name' => $org->orgid . ': ' . $org->name
                        );
                    }
                break;
                case 2: // course
                    $result['heading'] = get_string('licence_target_course', 'block_edupublisher');
                    $courses = enrol_get_all_users_courses($USER->id, true);
                    foreach ($courses AS $course) {
                        $context = context_course::instance($course->id);
                        if (has_capability('moodle/course:update', $context)) {
                            $result['options'][] = array(
                                'id' => $course->id,
                                'name' => $course->fullname
                            );
                        }
                    }
                break;
                case 3: // user
                    $result['heading'] = get_string('licence_target_user', 'block_edupublisher');
                    $result['options'][] = array(
                        'id' => $USER->id,
                        'name' => fullname($USER)
                    );
                break;
            }
        }
        return json_encode($result, JSON_NUMERIC_CHECK);
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function licence_redeem_returns() {
        return new external_value(PARAM_RAW, 'JSON encoded data for licencekey or success for redeem.');
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function list_parameters() {
        return new external_function_parameters(array(
            'channel' => new external_value(PARAM_INT, 'Channel to generate list, 0 for all')
        ));
    }

    /**
     * @return array of published items
     */
    public static function list() {
        global $DB;
        /*
        $entries = $DB->get_records_sql('SELECT COUNT(id) AS amount FROM {user} WHERE confirmed=1 AND deleted=0 AND suspended=0', array());
        $k = array_keys($entries);
        return $entries[$k[0]]->amount;
        */
        return null;
    }
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function list_returns() {
        return new external_value(PARAM_INT, 'Returns an array of published resources');
    }

    public static function rate_parameters() {
        return new external_function_parameters(array(
            'packageid' => new external_value(PARAM_INT, 'Package-ID'),
            'to' => new external_value(PARAM_INT, 'Value to set to'),
        ));
    }
    public static function rate($packageid, $to) {
        $params = self::validate_parameters(self::rate_parameters(), array('packageid' => $packageid, 'to' => $to));
        // Store rating if we are permitted to.
        global $DB, $USER;

        $package = block_edupublisher::get_package($params['packageid'], false);
        if ($params['to'] <= 5 && $params['to'] >= 0 && isset($package->canrate) && $package->canrate) {
            $rating = $DB->get_record('block_edupublisher_rating', array('package' => $params['packageid'], 'userid' => $USER->id));
            if (isset($rating->id) && $rating->id > 0) {
                if ($rating->rating == $params['to']) {
                    // We want to remove our current rating!
                    $DB->delete_records('block_edupublisher_rating', array('id' => $rating->id));
                } else {
                    $rating->rating = $params['to'];
                    $rating->modified = time();
                    $DB->update_record('block_edupublisher_rating', $rating);
                }
            } else {
                $rating = (object) array(
                    'userid' => $USER->id,
                    'package' => $package->id,
                    'rating' => $params['to'],
                    'created' => time(),
                    'modified' => time(),
                );
                $DB->insert_record('block_edupublisher_rating', $rating);
            }
        }

        $average = $DB->get_records_sql('SELECT AVG(rating) avg, COUNT(rating) cnt FROM {block_edupublisher_rating} WHERE package=?', array($params['packageid']));
        $avg = -1;
        foreach($average AS $average) {
            $avg = $average->avg;
            $cnt = $average->cnt;
            break;
        }
        $rating = $DB->get_record('block_edupublisher_rating', array('package' => $params['packageid'], 'userid' => $USER->id));
        return array('average' => intval($avg), 'amount' => intval($cnt), 'current' => intval(($rating && $rating->id > 0) ? $rating->rating : -1));
    }
    public static function rate_returns() {
        //return new external_multiple_structure(
        return new external_single_structure(
                array(
                    'average' => new external_value(PARAM_INT, 'Average rating for this package.'),
                    'amount' => new external_value(PARAM_INT, 'Amount of users that rated.'),
                    'current' => new external_value(PARAM_INT, 'Rating of user for this package.'),
                )
        //    )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function search_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'courseid'),
            'search' => new external_value(PARAM_TEXT, 'search term'),
            'subjectareas' => new external_value(PARAM_TEXT, 'comma-separated list of subjectareas'),
            'schoollevels' => new external_value(PARAM_TEXT, 'comma-separated list of schoollevels'),
        ));
    }

    /**
     * Perform the search.
     * @return list of packages as json encoded string.
     */
    public static function search($courseid, $search, $subjectareas, $schoollevels) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        // page-context is required for output of templates.
        $PAGE->set_context(context_system::instance());
        $params = self::validate_parameters(self::search_parameters(), array('courseid' => $courseid, 'search' => $search, 'subjectareas' => $subjectareas, 'schoollevels' => $schoollevels));
        $params['subjectareas'] = array_filter(explode(',', $params['subjectareas']));
        $params['schoollevels'] = array_filter(explode(',', $params['schoollevels']));

        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        $reply = array();
        $reply['relevance'] = array();
        $reply['packages'] = array();
        $reply['subjectareas'] = $params['subjectareas'];
        $reply['schoollevels'] = $params['schoollevels'];

        $conditions = array(
            'and' => array(),
            'or' => array(),
            'schoollevels' => array(),
            'subjectareas' => array(),
        );
        $sqlparams = array(
            'and' => array(),
            'or' => array(),
            'schoollevels' => array(),
            'subjectareas' => array(),
        );
        $sql = "SELECT package, COUNT(package) AS cnt\n
                    FROM {block_edupublisher_metadata}\n
                    WHERE \n";

        $basesql = "SELECT package FROM {block_edupublisher_metadata} WHERE field LIKE '%field%' AND content=? AND active=1";
        if (count($params['subjectareas']) > 0) {
            // At least one of the areas should match!
            for ($a = 0; $a < count($params['subjectareas']); $a++) {
                $conditions['subjectareas'][] = "package IN (" . str_replace("%field%", "default_subjectarea%", $basesql) . ")\n";
                $sqlparams['subjectareas'][] = $params['subjectareas'][$a];
            }
        }
        if (count($params['schoollevels']) > 0) {
            // At least one of the levels should match!
            for ($a = 0; $a < count($params['schoollevels']); $a++) {
                $conditions['schoollevels'][] = "package IN (" . str_replace("%field%", "default_schoollevel%", $basesql) . ")\n";
                $sqlparams['schoollevels'][] = $params['schoollevels'][$a];
            }
        }

        if (!empty($params['search'])) {
            $searchkeys = explode(' ', $params['search']);

            for ($b = 0; $b < count($searchkeys); $b++) {
                if (is_numeric($searchkeys[$b])) {
                    $conditions['or'][] = "package IN (SELECT package FROM {block_edupublisher_metadata} WHERE content=? AND active=1)";
                    $sqlparams['or'][] = $searchkeys[$b];
                } else {
                    $conditions['or'][] = "package IN (SELECT package FROM {block_edupublisher_metadata} WHERE content LIKE ? AND active=1)";
                    $sqlparams['or'][] = '%' . $searchkeys[$b] . '%';
                }
            }
            $conditions['or'][] = "package IN (SELECT package FROM {block_edupublisher_metadata} WHERE content LIKE ? AND active=1)";
            $sqlparams['or'][] = '%' . $params['search'] . '%';
        }

        $qparams = array();
        if (count($conditions['or']) > 0) {
            $sqlor = implode(' OR ', $conditions['or']);
            $qparams = $sqlparams['or'];
        }

        if (count($conditions['schoollevels']) > 0) {
            $conditions['and'][] = '(' . implode(' OR ', $conditions['schoollevels']) . ')';
            $sqlparams['and'] = array_merge($sqlparams['and'], $sqlparams['schoollevels']);
        }
        if (count($conditions['subjectareas']) > 0) {
            $conditions['and'][] = '(' . implode(' OR ', $conditions['subjectareas']) . ')';
            $sqlparams['and'] = array_merge($sqlparams['and'], $sqlparams['subjectareas']);
        }
        if (count($conditions['and']) > 0) {
            $sqland = implode(' AND ', $conditions['and']);
            $qparams = array_merge($qparams, $sqlparams['and']);
        }
        if (!empty($sqlor)) {
            $sql .= '(' . $sqlor . ')';
        }
        if (!empty($sqland)) {
            if (!empty($sqlor)) {
                $sql .= ' AND ';
            }
            $sql .= $sqland;
        }
        if (empty($sqlor) && empty($sqland)) {
            $sql .= ' 1=0';
        }

        $sql .= " GROUP BY package ORDER BY cnt DESC";

        $reply['conditions'] = $conditions;
        $reply['sqlparams'] = $sqlparams;
        $reply['sql'] = $sql;
        $reply['qparams'] = $qparams;
        //return json_encode($reply, JSON_NUMERIC_CHECK);

        $relevance = $DB->get_records_sql($sql, $qparams);

        foreach($relevance AS $relevant) {
            if (!isset($reply['relevance'][$relevant->cnt])) {
                $reply['relevance'][$relevant->cnt] = array();
            }
            $package = block_edupublisher::get_package($relevant->package, true);
            $addpackage = true;
            if (!empty($package->commercial_publishas) && $package->commercial_publishas == 1) {
                // For commercial content we need the licence!
                $reply['commercial'][] = $package->id;

                $orgid = 0;

                if (block_edupublisher::uses_eduvidual()) {
                    // This is some functionality specific to a plugin that is not published!
                    $org = \local_eduvidual\locallib::get_org_by_courseid($params['courseid']);
                    $orgid = !empty($org->orgid) ? $org->orgid : 0;
                }
                $sql = "SELECT *
                          FROM
                            {block_edupublisher_lic} l,
                            {block_edupublisher_lic_pack} lp
                          WHERE l.id=lp.licenceid
                            AND lp.packageid=?
                            AND (
                                lp.amounts = -1 OR lp.amounts > 0
                            )
                            AND (
                                (l.target = 3 AND l.redeemid>0 AND l.redeemid = ?)
                                OR
                                (l.target = 2 AND l.redeemid>0 AND l.redeemid = ?)
                                OR
                                (l.target = 1 AND l.redeemid>0 AND l.redeemid = ?)
                            )";
                //$reply['sql'] = $sql;
                //$reply['params'] = array($package->id, $USER->id, $params['courseid'], $orgid);
                $licence = $DB->get_records_sql($sql, array($package->id, $USER->id, $params['courseid'], $orgid));
                $addpackage = (!empty($licence->id) && $licence->id > 0);
            }
            if ($addpackage) {
                $reply['relevance'][$relevant->cnt][] = $relevant->package;
                $reply['packages'][$relevant->package] = $package;
            }
        }
        return json_encode($reply, JSON_NUMERIC_CHECK);
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function search_returns() {
        return new external_value(PARAM_RAW, 'List of packages as json-encoded string.');
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function store_publisher_parameters() {
        return new external_function_parameters(array(
            'active' => new external_value(PARAM_INT, 'whether it is active (1) or not (0)'),
            'id' => new external_value(PARAM_INT, 'id of publisher, if 0 will create new one'),
            'name' => new external_value(PARAM_TEXT, 'name of publisher'),
        ));
    }

    /**
     * Store data of a publisher
     * @return list of packages as json encoded string.
     */
    public static function store_publisher($active, $id, $name) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        if (block_edupublisher::is_admin()) {
            $params = self::validate_parameters(self::store_publisher_parameters(), array('active' => $active, 'id' => $id, 'name' => $name));

            if (!empty($params['name'])) {
                if ($params['id'] > 0) {
                    $obj = $DB->get_record('block_edupublisher_pub', array('id' => $params['id']), '*', MUST_EXIST);
                } else {
                    $obj = (object) array('id' => 0);
                }
                $obj->active = $params['active'];
                $obj->name = $params['name'];
                if ($obj->id > 0) {
                    $DB->update_record('block_edupublisher_pub', $obj);
                } else {
                    $DB->insert_record('block_edupublisher_pub', $obj);
                }
            }
            $publishers = $DB->get_records_sql('SELECT * FROM {block_edupublisher_pub} ORDER BY name ASC');
            return json_encode($publishers, JSON_NUMERIC_CHECK);
        } else {
            return json_encode(array('error' => 'no permission'));
        }
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function store_publisher_returns() {
        return new external_value(PARAM_RAW, 'All publisher-objects as JSON-string');
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function store_publisher_user_parameters() {
        return new external_function_parameters(array(
            'action' => new external_value(PARAM_TEXT, 'action to perform (add, remove or none)'),
            'publisherid' => new external_value(PARAM_INT, 'id of publisher'),
            'userids' => new external_value(PARAM_TEXT, 'userids to add or remove as string, delimited by space'),
        ));
    }

    /**
     * Store data of a publisher
     * @return list of packages as json encoded string.
     */
    public static function store_publisher_user($action, $publisherid, $userids) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        if (block_edupublisher::is_admin()) {
            $params = self::validate_parameters(self::store_publisher_user_parameters(), array('action' => $action, 'publisherid' => $publisherid, 'userids' => $userids));

            $userids = explode(' ', $params['userids']);
            switch($params['action']) {
                case 'add':
                    foreach($userids AS $userid) {
                        $user = $DB->get_record('user', array('id' => $userid), '*', IGNORE_MISSING);
                        if ($user && $user->id == $userid) {
                            $chk = $DB->get_record('block_edupublisher_pub_user', array('publisherid' => $params['publisherid'], 'userid' => $userid));
                            if (!$chk || empty($chk->id)) {
                                $DB->insert_record('block_edupublisher_pub_user', array('publisherid' => $params['publisherid'], 'userid' => $userid));
                            }
                        }
                    }
                break;
                case 'remove':
                    foreach($userids AS $userid) {
                        $DB->delete_records('block_edupublisher_pub_user', array('publisherid' => $params['publisherid'], 'userid' => $userid));
                    }
                break;
            }

            $_users = $DB->get_records_sql('SELECT u.* FROM {user} AS u, {block_edupublisher_pub_user} pu WHERE u.id=pu.userid AND pu.publisherid=? ORDER BY u.lastname ASC, u.firstname ASC', array($params['publisherid']));
            $users = array();
            foreach($_users AS $user) {
                $users[$user->id] = array(
                    'id' => $user->id,
                    'fullname' => $user->lastname . ' ' . $user->firstname . ' (' . $user->email . ')'
                );
            }
            return json_encode($users, JSON_NUMERIC_CHECK);
        } else {
            return json_encode(array('error' => 'no permission'));
        }
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function store_publisher_user_returns() {
        return new external_value(PARAM_RAW, 'All publisher-users as JSON-string');
    }

    public static function trigger_active_parameters() {
        return new external_function_parameters(array(
            'packageid' => new external_value(PARAM_INT, 'Package-ID'),
            'type' => new external_value(PARAM_TEXT, 'default for whole package otherwise channel name'),
            'to' => new external_value(PARAM_INT, 'Value to set to'),
        ));
    }
    public static function trigger_active($packageid, $type, $to) {
        $params = self::validate_parameters(self::trigger_active_parameters(), array('packageid' => $packageid, 'type' => $type, 'to' => $to));
        global $CFG, $DB, $USER;
        $package = block_edupublisher::get_package($params['packageid'], true);

        $statusses = array();
        $statusses['cantriggeractive' . $params['type']] = $package->{'cantriggeractive' . $params['type']};
        if (!empty($package->{'cantriggeractive' . $params['type']})) {
            $active = ($params['to'] >= 1) ? 1 : 0;
            $package->{$params['type'] .'_active'} = $active;
            if ($params['type'] != 'default') {
                /*
                 * If any channel gets activated, also activate default
                 * If last gets deactivated, also deactivate default
                */
                if ($active == 1) {
                    $package->default_active = 1;
                } else {
                    $package->default_active = $package->eduthek_active || $package->etapas_active;
                }
                // Trigger metadata in default channel.
                $sql = "UPDATE {block_edupublisher_metadata}
                            SET active=?
                            WHERE field LIKE ? ESCAPE '+'
                                AND package=?";
                $DB->execute($sql, array($package->default_active, 'default' . '+_%', $params['packageid']));
            } else {
                $package->default_active = $active;
            }
            block_edupublisher::store_metadata($package, 'default', 'default_active', $package->default_active);

            $sql = "UPDATE {block_edupublisher_metadata}
                        SET active=?
                        WHERE field LIKE ? ESCAPE '+'
                            AND package=?";
            $DB->execute($sql, array($active, $params['type'] . '+_%', $params['packageid']));
            $package->modified = time();
            if ($params['type'] == 'default') {
                block_edupublisher::store_metadata($package, 'default', 'default_active', $package->default_active);
                $package->active = $active;
            } else {
                block_edupublisher::store_metadata($package, $params['type'], $params['type'] . '_active', $active);
            }
            $package->active = $package->default_active;
            block_edupublisher::toggle_guest_access($package->course, $package->active);
            $DB->update_record('block_edupublisher_packages', $package);

            if (!empty($package->etapas_active) && $package->etapas_status == 'inspect') {
                block_edupublisher::store_metadata($package, 'etapas', 'etapas_status', 'eval');
            }

            global $PAGE;
            $PAGE->set_context(context_system::instance());
            require_login();
            $sendto = array('author');
            require_once($CFG->dirroot . '/blocks/edupublisher/classes/channel_definition.php');
            $channels = array_keys($definition);
            foreach ($channels AS $channel) {
                $statusses[$channel . '_active'] = (!empty($package->{$channel . '_active'}) && $package->{$channel . '_active'} == 1) ? 1 : 0;
                if (
                    !empty($package->{$channel . '_active'}) && $package->{$channel . '_active'} == "1"
                    &&
                    (empty($package->{$channel . '_published'}) || $package->{$channel . '_published'} == "0")) {
                    block_edupublisher::store_metadata($package, $channel, $channel . '_published', time());
                }
            }
            if ($package->active) {
                block_edupublisher::store_comment($package, 'comment:template:package_published', $sendto, true, false, $params["type"]);
            } else {
                block_edupublisher::store_comment($package, 'comment:template:package_unpublished', $sendto, true, false, $params["type"]);
            }
            // Toggle course visibility
            $course = get_course($package->course);
            $course->visible = $package->active;
            $DB->update_record('course', $course);
        }
        return json_encode($statusses);
    }
    public static function trigger_active_returns() {
        return new external_value(PARAM_RAW, 'Returns current state of all types as json encoded object.');
    }
}
