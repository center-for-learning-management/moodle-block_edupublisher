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
\core_privacy\local\request\plugin\provider
{

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

        $sql = "SELECT * FROM {block_edupublisher_comments} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT * FROM {block_edupublisher_evaluatio} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT * FROM {block_edupublisher_lic} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT * FROM {block_edupublisher_log} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT * FROM {block_edupublisher_packages} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT * FROM {block_edupublisher_pub_user} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT * FROM {block_edupublisher_rating} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT * FROM {block_edupublisher_uses} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    // @TODO Export user data

    // @TODO Delete user data
}
