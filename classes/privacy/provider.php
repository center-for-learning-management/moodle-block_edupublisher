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
               2021 onwards Center for Learningmanagement (https://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
**/

namespace block_edupublisher\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use context_user;

defined('MOODLE_INTERNAL') || die;

class provider implements
\core_privacy\local\metadata\provider,
\core_privacy\local\request\core_userlist_provider,
\core_privacy\local\request\plugin\provider {
	public static function get_metadata(collection $collection) : collection {
        // Table edusuport subscribers.
        $collection->add_database_table(
            'block_edupublisher_comments', array(
                'id' => 'privacy:metadata:comments:id',
                'package' => 'privacy:metadata:comments:package',
                'userid' => 'privacy:metadata:comments:userid',
                'content' => 'privacy:metadata:comments:content',
                'created' => 'privacy:metadata:comments:created',
                'permahash' => 'privacy:metadata:comments:permahash',
                'isautocomment' => 'privacy:metadata:comments:isautocomment',
                'ispublic' => 'privacy:metadata:comments:ispublic',
                'forchannel' => 'privacy:metadata:comments:forchannel',
                'linkurl' => 'privacy:metadata:comments:linkurl',
            ),
            'privacy:metadata:comments'
        );
        $collection->add_database_table(
            'block_edupublisher_evaluatio', array(
                'id' => 'privacy:metadata:evaluatio:id',
                'package' => 'privacy:metadata:evaluatio:package',
                'userid' => 'privacy:metadata:evaluatio:userid',
                'evaluated_on' => 'privacy:metadata:evaluatio:evaluated_on',
                'evaluated_at' => 'privacy:metadata:evaluatio:evaluated_at',
                'comprehensible_description' => 'privacy:metadata:evaluatio:comprehensible_description',
                'suitable_workflow' => 'privacy:metadata:evaluatio:suitable_workflow',
                'reasonable_preconditions' => 'privacy:metadata:evaluatio:reasonable_preconditions',
                'correct_content' => 'privacy:metadata:evaluatio:correct_content',
                'improvement_specification' => 'privacy:metadata:evaluatio:improvement_specification',
                'technology_application' => 'privacy:metadata:evaluatio:technology_application',
                'comments' => 'privacy:metadata:evaluatio:comments',
                'timecreated' => 'privacy:metadata:evaluatio:timecreated',
            ),
            'privacy:metadata:evaluatio'
        );
        $collection->add_database_table(
            'block_edupublisher_lic', array(
                'id' => 'privacy:metadata:lic:id',
                'package' => 'privacy:metadata:lic:publisherid',
                'userid' => 'privacy:metadata:lic:userid',
                'licencekey' => 'privacy:metadata:lic:licencekey',
                'type' => 'privacy:metadata:lic:type',
                'target' => 'privacy:metadata:lic:target',
                'lic' => 'privacy:metadata:lic:redeemid',
                'created' => 'privacy:metadata:lic:created',
                'maturity' => 'privacy:metadata:lic:maturity',
            ),
            'privacy:metadata:lic'
        );
        $collection->add_database_table(
            'block_edupublisher_log', array(
                'id' => 'privacy:metadata:log:id',
                'packageid' => 'privacy:metadata:log:publisherid',
                'userid' => 'privacy:metadata:log:userid',
                'timeentered' => 'privacy:metadata:log:timeentered',
                'viewed' => 'privacy:metadata:log:viewed',
                'enrolled' => 'privacy:metadata:log:enrolled',
                'unenrolled' => 'privacy:metadata:log:unenrolled',
                'cloned' => 'privacy:metadata:log:cloned',
            ),
            'privacy:metadata:log'
        );
        $collection->add_database_table(
            'block_edupublisher_packages', array(
                'id' => 'privacy:metadata:packages:id',
                'userid' => 'privacy:metadata:packages:userid',
                'course' => 'privacy:metadata:packages:course',
                'sourcecourse' => 'privacy:metadata:packages:sourcecourse',
                'channels' => 'privacy:metadata:packages:channels',
                'title' => 'privacy:metadata:packages:title',
                'created' => 'privacy:metadata:packages:created',
                'modified' => 'privacy:metadata:packages:modified',
                'deleted' => 'privacy:metadata:packages:deleted',
                'active' => 'privacy:metadata:packages:active',
            ),
            'privacy:metadata:packages'
        );
        $collection->add_database_table(
            'block_edupublisher_rating', array(
                'id' => 'privacy:metadata:rating:id',
                'package' => 'privacy:metadata:rating:package',
                'userid' => 'privacy:metadata:rating:userid',
                'rating' => 'privacy:metadata:rating:rating',
                'created' => 'privacy:metadata:rating:created',
                'modified' => 'privacy:metadata:rating:modified',
            ),
            'privacy:metadata:rating'
        );
        $collection->add_database_table(
            'block_edupublisher_uses', array(
                'id' => 'privacy:metadata:uses:id',
                'userid' => 'privacy:metadata:uses:userid',
                'package' => 'privacy:metadata:uses:package',
                'targetcourse' => 'privacy:metadata:uses:targetcourse',
                'created' => 'privacy:metadata:uses:created',
            ),
            'privacy:metadata:uses'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist  {
        $contextlist = new \core_privacy\local\request\contextlist();

        // Packages
        $sql = "SELECT c.id
                    FROM {context} c
                    JOIN {block_edupublisher_packages} p ON c.instanceid = p.course
                    WHERE c.contextlevel = :contextlevel
                        AND p.deleted > 0
                        AND p.userid = :userid";

        $params = ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid];
        $contextlist->add_from_sql($sql, $params);
        // Comments
        $sql = "SELECT c.id
                    FROM {context} c
                    JOIN {block_edupublisher_packages} p ON c.instanceid = p.course
                    JOIN {block_edupublisher_comments} s ON p.id = s.package
                    WHERE c.contextlevel = :contextlevel
                        AND p.deleted > 0
                        AND p.userid = :userid";
        $params = ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid];
        $contextlist->add_from_sql($sql, $params);
        // Evaluations
        $sql = "SELECT c.id
                    FROM {context} c
                    JOIN {block_edupublisher_packages} p ON c.instanceid = p.course
                    JOIN {block_edupublisher_evaluatio} s ON p.id = s.packageid
                    WHERE c.contextlevel = :contextlevel
                        AND p.deleted > 0
                        AND p.userid = :userid";
        $params = ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid];
        $contextlist->add_from_sql($sql, $params);
        // Log of used courses.
        $sql = "SELECT c.id
                    FROM {context} c
                    JOIN {block_edupublisher_packages} p ON c.instanceid = p.course
                    JOIN {block_edupublisher_log} s ON p.id = s.packageid
                    WHERE c.contextlevel = :contextlevel
                        AND p.deleted > 0
                        AND p.userid = :userid";
        $params = ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid];
        $contextlist->add_from_sql($sql, $params);
        // Ratings.
        $sql = "SELECT c.id
                    FROM {context} c
                    JOIN {block_edupublisher_packages} p ON c.instanceid = p.course
                    JOIN {block_edupublisher_rating} s ON p.id = s.package
                    WHERE c.contextlevel = :contextlevel
                        AND p.deleted > 0
                        AND p.userid = :userid";
        $params = ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid];
        $contextlist->add_from_sql($sql, $params);
        // Uses.
        $sql = "SELECT c.id
                    FROM {context} c
                    JOIN {block_edupublisher_packages} p ON c.instanceid = p.course
                    JOIN {block_edupublisher_uses} s ON p.id = s.package
                    WHERE c.contextlevel = :contextlevel
                        AND p.deleted > 0
                        AND p.userid = :userid";
        $params = ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \CONTEXT_COURSE) {
            return;
        }

        $params = ['course' => $context->instanceid];

        $sql = "SELECT p.userid
                    FROM {block_edupublisher_packages} p
                    WHERE p.deleted > 0
                        AND course = :course";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT p.userid
                    FROM {block_edupublisher_packages} p,
                         {block_edupublisher_comments} s
                    WHERE p.id = s.packageid
                        AND p.deleted > 0
                        AND p.course = :course";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT p.userid
                    FROM {block_edupublisher_packages} p,
                         {block_edupublisher_evaluatio} s
                    WHERE p.id = s.packageid
                        AND p.deleted > 0
                        AND p.course = :course";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT p.userid
                    FROM {block_edupublisher_packages} p,
                         {block_edupublisher_lic} s
                    WHERE p.id = s.packageid
                        AND p.deleted > 0
                        AND p.course = :course";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT p.userid
                    FROM {block_edupublisher_packages} p,
                         {block_edupublisher_log} s
                    WHERE p.id = s.packageid
                        AND p.deleted > 0
                        AND p.course = :course";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT p.userid
                    FROM {block_edupublisher_packages} p,
                         {block_edupublisher_pub_user} s
                    WHERE p.id = s.packageid
                        AND p.deleted > 0
                        AND p.course = :course";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT p.userid
                    FROM {block_edupublisher_packages} p,
                         {block_edupublisher_rating} s
                    WHERE p.id = s.packageid
                        AND p.deleted > 0
                        AND p.course = :course";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT p.userid
                    FROM {block_edupublisher_packages} p,
                         {block_edupublisher_uses} s
                    WHERE p.id = s.packageid
                        AND p.deleted > 0
                        AND p.course = :course";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        if (!$contextlist->count()) {
            return;
        }
        $data_packages = array();
        // based on packages
        $data_comments = array();
        $data_evaluatio = array();
        $data_log = array();
        $data_rating = array();
        $data_uses = array();

        // special
        $data_lic = array();
        $data_pub_user = array();

        $datas = array('comments', 'evaluatio', 'log', 'rating', 'uses');

        $user = $contextlist->get_user();
        $context = context_user::instance($user->id);
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        foreach ($datas as $data) {
            $packagefield = in_array($data, array('evaluatio', 'log')) ? 'packageid' : 'package';
            $sql = "SELECT c.id as cmid, s.*
                        FROM {context} c
                        JOIN {block_edupublisher_packages} p ON c.instanceid = p.course
                        JOIN {block_edupublisher_$data} s ON p.id = s.$packagefield
                        WHERE c.id $contextsql
                            AND p.deleted > 0
                            AND s.userid = :userid";
            $rs = $DB->get_recordset_sql($sql, $contextparams + ['userid' => $user->id]);
            foreach ($rs as $row) {
                $fields = array();
                switch ($data) {
                    case 'comments':
                        $fields = array(
                            'id', 'package', 'userid', 'content', 'created', 'permahash',
                            'isautocomment', 'ispublic', 'forchannel', 'linkurl'
                        );
                    break;
                    case 'evaluatio':
                        $fields = array(
                            'id', 'packageid', 'evaluated_on', 'evaluated_at', 'comprehensible_description',
                            'suitable_workflow', 'reasonable_preconditions', 'correct_content',
                            'improvement_specification', 'technology_application', 'comments',
                            'schoollevel', 'evaldate'
                        );
                    break;
                    case 'log':
                        $fields = array(
                            'id', 'packageid', 'userid', 'timeentered', 'viewed',
                            'enrolled', 'unenrolled', 'cloned'
                        );
                    break;
                    case 'rating':
                        $fields = array(
                            'id', 'package', 'userid', 'rating', 'created', 'modified'
                        );
                    break;
                    case 'uses':
                        $fields = array(
                            'id', 'package', 'userid', 'targetcourse', 'created'
                        );
                    break;
                }
                if (count($fields) > 0) {
                    $o = (object) array();
                    foreach ($fields as $field) {
                        $o->{$field} = $row->{$field};
                    }
                    ${'data_' . $data}[] = $o;
                }
            }
            writer::with_context($context)->export_data(
                [
                    get_string('pluginname', 'block_edupublisher'),
                    get_string('privacy:export:' . $data,'block_edupublisher')
                ],
                (object)${'data_' . $data}
            );
        }
        $sql = "SELECT c.id as cmid, p.*
                    FROM {context} c
                    JOIN {block_edupublisher_packages} p ON c.instanceid = p.course
                    WHERE c.id $contextsql
                        AND p.deleted > 0
                        AND p.userid = :userid";

        $rs = $DB->get_recordset_sql($sql, $contextparams + ['userid' => $user->id]);
        foreach ($rs as $row) {
            $data_packages[] = (object)array(
                'id' => $row->id, 'userid' => $row->userid, 'course' => $row->course,
                'sourcecourse' => $row->sourcecourse, 'channels' => $row->channels,
                'title' => $row->title, 'created' => $row->created, 'modified' => $row->modified,
                'backuped' => $row->backuped, 'deleted' => $row->deleted, 'active' => $row->active,
            );
        }

        writer::with_context($context)->export_data(
            [
                get_string('pluginname', 'block_edupublisher'),
                get_string('privacy:export:packages', 'block_edupublisher')
            ],
            (object) $data_packages
        );
    }

    /**
     * Delete all user data for this context.
     *
     * @param  \context $context The context to delete data for.
     */
     public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        static::delete_user_data($context->instanceid);
    }
    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if ($context instanceof \context_user) {
            static::delete_user_data($context->instanceid);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        foreach ($contextlist as $context) {
            // Check what context we've been delivered.
            if ($context instanceof \context_user) {
                static::delete_user_data($context->instanceid);
            }
        }
    }
    /**
     * Delete data from $tablename with the IDs returned by $sql query.
     *
     * @param  string $sql    SQL query for getting the IDs of the uer enrolments entries to delete.
     * @param  array  $params SQL params for the query.
     */
    protected static function delete_user_data(int $userid) {
        global $DB;

        $DB->delete_records('block_edupublisher_uses', array('userid' => $userid));
        $DB->delete_records('block_edupublisher_rating', array('userid' => $userid));
        $DB->delete_records('block_edupublisher_metadata', array('userid' => $userid));

        // Comments must not be deleted. @todo anonymize??
        // $DB->delete_records('block_edupublisher_comments', array('userid' => $userid));
        // Evaluations must not be deleted. @todo anonymize??
        // $DB->delete_records('block_edupublisher_evaluatio', array('userid' => $userid));
    }
}
