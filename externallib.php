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

class block_edupublisher_external extends external_api {
    public static function group_rename_parameters() {
        return new external_function_parameters(array(
            'groupid' => new external_value(PARAM_INT, 'the groupid'),
            'name' => new external_value(PARAM_TEXT, 'the new name'),
        ));
    }

    /**
     * Return all courses the user has trainer capabilities in.
     * @return list of courses as json encoded string.
     */
    public static function group_rename($groupid, $name) {
        $params = self::validate_parameters(
            self::group_rename_parameters(),
            ['groupid' => $groupid, 'name' => $name]
        );

        $result = (object)[];

        $group = \groups_get_group($params['groupid'], 'id,courseid', MUST_EXIST);
        $context = \context_course::instance($group->courseid);

        $roleteacher = \get_config('block_edupublisher', 'groupsroleteacher');
        //$result->error = get_string('groups:not_member', 'block_edupublisher');
        if (empty($params['name'])) {
            throw new \moodle_exception('exception:name_must_not_be_empty', 'block_edupublisher');
        } else if (!\block_edupublisher\permissions::has_role($context, $roleteacher)) {
            $result->error = get_string('groups:no_permission', 'block_edupublisher');
        } else if (!\groups_is_member($group->id, $USER->id)) {
            $result->error = get_string('groups:not_member', 'block_edupublisher');
        } else {
            global $CFG;
            require_once("$CFG->dirroot/group/lib.php");
            $group->name = $params['name'];
            \groups_update_group($group, false);
            $result->id = $group->id;
        }

        return json_encode($result);
    }

    /**
     * Return definition.
     * @return external_value
     */
    public static function group_rename_returns() {
        return new external_value(PARAM_RAW, 'The result as JSON-string');
    }

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

        require_once("$CFG->dirroot/blocks/edupublisher/block_edupublisher.php");
        $courses = \block_edupublisher\lib::get_courses(null, 'moodle/course:update');
        $package = new \block_edupublisher\package($params['packageid']);
        if (!empty($package->get('publishas', 'commercial'))) {
            // The licence must allow us to import into certain courses.
            foreach ($courses as $courseid => $course) {
                $orgid = 0;

                if (\block_edupublisher\lib::uses_eduvidual()) {
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
                $licence = $DB->get_records_sql($sql, array($package->id, $USER->id, $courseid, $orgid));
                if (empty($licence->id)) {
                    // No licence for this course.
                    unset($courses[$courseid]);
                };
            }
        }

        // Re-sort by name.
        $_courses = array();
        foreach ($courses as $course) {
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
        $params = self::validate_parameters(self::licence_generate_parameters(), array('amount' => $amount, 'publisherid' => $publisherid));
        if (\block_edupublisher\permissions::is_admin() || \block_edupublisher\permissions::is_publisher($params['publisherid'])) {
            $licencekeys = array();
            $pre = $params['publisherid'];
            while (count($licencekeys) < $params['amount']) {
                $code = substr(md5(rand(0, 9999) . time()), 0, 10);
                $licencekey = $pre . '-' . $code;
                $chk = $DB->get_record('block_edupublisher_lic', array('licencekey' => $licencekey));
                if (!$chk)
                    $licencekeys[] = $licencekey;
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
        $params = self::validate_parameters(self::licence_generatenow_parameters(), array('amount' => $amount, 'licencekeys' => $licencekeys, 'type' => $type, 'publisherid' => $publisherid));
        if (\block_edupublisher\permissions::is_admin() || \block_edupublisher\permissions::is_publisher($params['publisherid'])) {
            $types = array('course', 'org', 'user');
            if (in_array($params['type'], $types)) {
                $licencekeys = explode(' ', $params['licencekeys']);
                $createdkeys = array();
                $failedkeys = array();
                foreach ($licencekeys as $licencekey) {
                    $chk = $DB->get_record('block_edupublisher_lic', array('licencekey' => $licencekey));
                    if (!$chk) {
                        $DB->insert_record('block_edupublisher_lic', array(
                            'publisherid' => $params['publisherid'],
                            'userid' => $USER->id,
                            'licencekey' => $licencekey,
                            'type' => $params['type'],
                            'amount' => $params['amount'],
                        ));
                    }
                }
                $licencekeys = array();
                $pre = substr(md5($params['publisherid']), 0, 10);
                while (count($licencekeys) < $params['amount']) {
                    $code = substr(md5(rand(0, 9999) . time()), 0, 10);
                    $licencekey = $pre . '-' . $code;
                    $chk = $DB->get_record('block_edupublisher_lic', array('licencekey' => $licencekey));
                    if (!$chk)
                        $licencekeys[] = $licencekey;
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
        $params = self::validate_parameters(self::licence_list_parameters(), array('publisherid' => $publisherid));
        if (\block_edupublisher\permissions::is_admin() || \block_edupublisher\permissions::is_publisher($params['publisherid'])) {
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
        $params = self::validate_parameters(self::licence_redeem_parameters(), array('licencekey' => $licencekey, 'targetid' => $targetid));
        $lic = $DB->get_record('block_edupublisher_lic', array('licencekey' => $params['licencekey']));
        $result = array('licencekey' => $params['licencekey'], 'options' => array());
        if (empty($lic->id)) {
            $result['heading'] = get_string('error');
            $result['error'] = get_string('licence_invalid', 'block_edupublisher');
        } elseif (!empty($lic->redeemid)) {
            $result['heading'] = get_string('error');
            $result['error'] = get_string('licence_already_redeemed', 'block_edupublisher');
        } elseif ($params['targetid'] > 0) {
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
                    foreach ($orgs as $org) {
                        $result['options'][] = array(
                            'id' => $org->orgid,
                            'name' => $org->orgid . ': ' . $org->name,
                        );
                    }
                    break;
                case 2: // course
                    $result['heading'] = get_string('licence_target_course', 'block_edupublisher');
                    $courses = enrol_get_all_users_courses($USER->id, true);
                    foreach ($courses as $course) {
                        $context = context_course::instance($course->id);
                        if (has_capability('moodle/course:update', $context)) {
                            $result['options'][] = array(
                                'id' => $course->id,
                                'name' => $course->fullname,
                            );
                        }
                    }
                    break;
                case 3: // user
                    $result['heading'] = get_string('licence_target_user', 'block_edupublisher');
                    $result['options'][] = array(
                        'id' => $USER->id,
                        'name' => fullname($USER),
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
            'channel' => new external_value(PARAM_INT, 'Channel to generate list, 0 for all'),
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

        $package = new \block_edupublisher\package($params['packageid'], false);
        if ($params['to'] <= 5 && $params['to'] >= 0 && !empty($package->get('canrate'))) {
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
                $rating = (object)array(
                    'userid' => $USER->id,
                    'package' => $package->id,
                    'rating' => $params['to'],
                    'created' => time(),
                    'modified' => time(),
                );
                $DB->insert_record('block_edupublisher_rating', $rating);
            }
        }

        $package->update_tstamp();

        $sql = "SELECT AVG(rating) avg, COUNT(rating) cnt
                    FROM {block_edupublisher_rating}
                    WHERE package=?";
        $average = $DB->get_records_sql($sql, [$params['packageid']]);
        $avg = -1;
        foreach ($average as $average) {
            $avg = $average->avg;
            $cnt = $average->cnt;
            break;
        }
        $rating = $DB->get_record('block_edupublisher_rating', array('package' => $params['packageid'], 'userid' => $USER->id));
        return [
            'average' => intval($avg),
            'amount' => intval($cnt),
            'current' => intval(($rating && $rating->id > 0) ? $rating->rating : -1),
        ];
    }

    public static function rate_returns() {
        return new external_single_structure(
            array(
                'average' => new external_value(PARAM_INT, 'Average rating for this package.'),
                'amount' => new external_value(PARAM_INT, 'Amount of users that rated.'),
                'current' => new external_value(PARAM_INT, 'Rating of user for this package.'),
            )
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
            'schoollevels' => new external_value(PARAM_ALPHANUMEXT, 'comma-separated list of schoollevels'),
            'subjectareas' => new external_value(PARAM_ALPHANUMEXT, 'comma-separated list of subjectareas'),
            'stars' => new external_value(PARAM_ALPHANUMEXT, 'comma-separated list of stars'),
        ));
    }

    /**
     * Perform the search.
     * @return list of packages as json encoded string.
     */
    public static function search($courseid, $search, $schoollevels, $subjectareas, $stars) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        // page-context is required for output of templates.
        $PAGE->set_context(\context_system::instance());
        $params = self::validate_parameters(
            self::search_parameters(),
            array(
                'courseid' => $courseid,
                'search' => $search,
                'schoollevels' => $schoollevels,
                'subjectareas' => $subjectareas,
                'stars' => $stars,
            )
        );

        $params['schoollevels'] = array_filter(explode('zzzZZZzzz', $params['schoollevels']));
        $params['subjectareas'] = array_filter(explode('zzzZZZzzz', $params['subjectareas']));
        $params['stars'] = array_filter(explode('zzzZZZzzz', $params['stars']));

        $filters = [];
        $filter_params = [];

        if ($params['schoollevels']) {
            $filter_params = array_merge($filter_params, $params['schoollevels']);
            $subfilter = array_map(fn() => \block_edupublisher\db::find_in_set('?', "mdef.schoollevels"), $params['schoollevels']);
            $filters[] = join(' OR ', $subfilter);
        }

        if ($params['subjectareas']) {
            $filter_params = array_merge($filter_params, $params['subjectareas']);
            $subfilter = array_map(fn() => \block_edupublisher\db::find_in_set('?', "mdef.subjectareas"), $params['subjectareas']);
            $filters[] = join(' OR ', $subfilter);
        }

        if ($params['stars']) {
            $subfilter = [];
            foreach ($params['stars'] as $star) {
                if ($star == -1) {
                    $star = 0;
                }

                $subfilter[] = 'p.rating=?';
                $filter_params[] = $star;
            }
            $filters[] = join(' OR ', $subfilter);
        }

        $filters_search = [];
        // filter unwanted characters
        $params['search'] = preg_replace('![\'"\-?]+!', '', $params['search']);
        $params['search'] = trim($params['search']);
        if (!empty($params['search'])) {
            $needles = explode(' ', $params['search']);
            //$needles[] = $params['search'];
            foreach ($needles as $needle) {
                $s = $DB->sql_like_escape($needle, $escapechar = '\\');
                $channels = \block_edupublisher\lib::get_channel_definition();
                $filters_search_sub = [];
                foreach ($channels as $channel => $fields) {
                    $table = '';
                    if ($channel == 'default')
                        $table = 'mdef';
                    if ($channel == 'eduthek')
                        $table = 'medu';
                    if ($channel == 'eduthekneu')
                        $table = 'meduneu';
                    if ($channel == 'etapas')
                        $table = 'meta';
                    if (empty($table))
                        continue; // unsupported channel.

                    foreach ($fields as $field => $fieldparams) {
                        if (empty($fieldparams['searchable'])) {
                            continue;
                        }
                        // Certain fields have own filters.
                        if ($channel == 'default' && in_array($field, ['schoollevels', 'subjectareas']))
                            continue;
                        // Only search in text-fields.
                        if (!in_array($fieldparams['datatype'], [PARAM_TEXT]))
                            continue;
                        if (!empty($fieldparams['multiple']) && !empty($fieldparams['options'])) {
                            foreach ($fieldparams['options'] as $option) {
                                $filters_search_sub[] = "{$table}.{$field}_{$option} LIKE '%$s%'";
                            }
                        } else {
                            $filters_search_sub[] = "{$table}.{$field} LIKE ?";
                            $filter_params[] = "%$s%";
                        }
                    }
                }
                $filters_search[] = "(" . implode(' OR ', $filters_search_sub) . ")";
            }
        }

        $filters = implode(') AND (', $filters);
        if ($filters) {
            $filters = "AND ($filters)";
        }

        $filters_search = implode(' AND ', $filters_search);
        if ($filters_search) {
            $filters_search = "AND ($filters_search)";
        }

        // @todo commercial channel and licences.

        $order_by = "ORDER BY mdef.title ASC";
        // @todo rating, title, ??

        $limit = "LIMIT 50 OFFSET 0";

        $sql = "SELECT DISTINCT p.id
                    FROM {block_edupublisher_packages} p,
                         {block_edupublisher_md_def} mdef,
                         {block_edupublisher_md_edu} medu,
                         {block_edupublisher_md_eduneu} meduneu,
                         {block_edupublisher_md_eta} meta
                    WHERE p.id = mdef.package
                        AND p.id = medu.package
                        AND p.id = meta.package
                        AND p.active > 0
                        $filters
                        $filters_search
                    $order_by
                    $limit";

        $reply['packages'] = array_values($DB->get_records_sql($sql, $filter_params));

        $show_star_rating = \block_edupublisher\lib::show_star_rating();

        for ($a = 0; $a < count($reply['packages']); $a++) {
            $package = new \block_edupublisher\package($reply['packages'][$a]->id, true, ['internal', 'rating']);
            $data = $package->get_flattened();

            $data->default_image ??= $package->get_preview_image_url()?->out(false);

            if (!$show_star_rating) {
                // remove rating info
                unset($data->rating);
                unset($data->ratingown);
                unset($data->ratingaverage);
                unset($data->ratingcount);
                unset($data->ratingselection);
            }

            $reply['packages'][$a] = $data;

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
            'mail' => new external_value(PARAM_EMAIL, 'email of publisher', VALUE_OPTIONAL),
        ));
    }

    /**
     * Store data of a publisher
     * @return list of packages as json encoded string.
     */
    public static function store_publisher($active, $id, $name, $mail) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        if (\block_edupublisher\permissions::is_admin()) {
            $params = self::validate_parameters(self::store_publisher_parameters(), array('active' => $active, 'id' => $id, 'name' => $name, 'mail' => $mail));

            if (!empty($params['name'])) {
                if ($params['id'] > 0) {
                    $obj = $DB->get_record('block_edupublisher_pub', array('id' => $params['id']), '*', MUST_EXIST);
                } else {
                    $obj = (object)array('id' => 0);
                }
                $obj->active = $params['active'];
                $obj->name = $params['name'];
                $obj->mail = $params['mail'];
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
        if (\block_edupublisher\permissions::is_admin()) {
            $params = self::validate_parameters(self::store_publisher_user_parameters(), array('action' => $action, 'publisherid' => $publisherid, 'userids' => $userids));

            $userids = explode(' ', $params['userids']);
            switch ($params['action']) {
                case 'add':
                    foreach ($userids as $userid) {
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
                    foreach ($userids as $userid) {
                        $DB->delete_records('block_edupublisher_pub_user', array('publisherid' => $params['publisherid'], 'userid' => $userid));
                    }
                    break;
            }

            $_users = $DB->get_records_sql('SELECT u.* FROM {user} AS u, {block_edupublisher_pub_user} pu WHERE u.id=pu.userid AND pu.publisherid=? ORDER BY u.lastname ASC, u.firstname ASC', array($params['publisherid']));
            $users = array();
            foreach ($_users as $user) {
                $users[$user->id] = array(
                    'id' => $user->id,
                    'fullname' => $user->lastname . ' ' . $user->firstname . ' (' . $user->email . ')',
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
            'to' => new external_value(PARAM_INT, 'Value to set to (0 or 1)'),
        ));
    }

    public static function trigger_active($packageid, $type, $to) {
        global $DB;

        [
            'packageid' => $packageid,
            'type' => $type,
            'to' => $to,
        ] = self::validate_parameters(self::trigger_active_parameters(), [
            'packageid' => $packageid,
            'type' => $type,
            'to' => $to,
        ]);
        $package = new \block_edupublisher\package($packageid, true);

        $statusses = array();
        $statusses['cantriggeractive' . $type] = $package->get('cantriggeractive', $type);
        if (!$package->get('cantriggeractive', $type)) {
            throw new \moodle_exception('can not trigger active for type ' . $type);
        }


        $published = !empty($to) ? time() : 0;
        // Trigger the channel itself.
        $package->set($published, 'published', $type);

        if ($type != 'default') {
            if ($published > 0) {
                // OLD: If any channel gets activated, also activate default
                // $package->set($published, 'published', 'default');
            } elseif (empty($package->get('published', 'eduthek')) && empty($package->get('published', 'eduthekneu')) && empty($package->get('published', 'etapas'))) {
                // If last gets deactivated, also deactivate default
                $package->set(0, 'published', 'default');
            }
        } else {
            // $type == 'default'
            if ($published > 0) {
                // Schreibrechte entziehen
                \block_edupublisher\permissions::role_unassign($package->courseid, $package->userid, 'defaultroleteacher');
            }
        }

        if (!empty($package->get('published', 'etapas')) && $package->get('status', 'etapas') == 'inspect') {
            $package->set('eval', 'status', 'etapas');
        }

        $package->set(empty($package->get('published', 'default')) ? 0 : 1, 'active');

        // Toggle course visibility
        $course = \get_course($package->courseid);
        $course->visible = !empty($package->get('active')) ? 1 : 0;
        $DB->update_record('course', $course);
        \rebuild_course_cache($course->id, true);

        \block_edupublisher\lib::toggle_guest_access($package->courseid, $package->get('active'));

        $published = $package->get('published', 'etapas');
        if (!empty($published)) {
            $evaluation = $DB->get_record('block_edupublisher_evaluatio', ['packageid' => $package->id]);
            if (!empty($evaluation->id)) {
                $package->set('eval', 'status', 'etapas');
            } else {
                $package->set('public', 'status', 'etapas');
            }

        } else if (empty($published)) {
            $package->set('inspect', 'status', 'etapas');
        }

        $package->store_package_db();

        // The comment system is using mustache templates
        // and requires a page context.
        global $PAGE;
        $PAGE->set_context(\context_system::instance());
        require_login();
        $sendto = array('author');
        if ($package->get('active')) {
            $package->store_comment('comment:template:package_published', $sendto, true, false, $type);
        } else {
            $package->store_comment('comment:template:package_unpublished', $sendto, true, false, $type);
        }

        $channels = \block_edupublisher\lib::channels();
        foreach ($channels as $channel) {
            $statusses["{$channel}_active"] = !empty($package->get('published', $channel)) ? 1 : 0;
        }

        return json_encode($statusses);
    }

    public static function trigger_active_returns() {
        return new external_value(PARAM_RAW, 'Returns current state of all types as json encoded object.');
    }
}
