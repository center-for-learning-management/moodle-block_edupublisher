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
            'search' => new external_value(PARAM_TEXT, 'search term'),
        ));
    }

    /**
     * Perform the search.
     * @return list of packages as json encoded string.
     */
    public static function search($search) {
        global $CFG, $DB, $OUTPUT, $PAGE;
        // page-context is required for output of templates.
        $PAGE->set_context(context_system::instance());
        $params = self::validate_parameters(self::search_parameters(), array('search' => $search));

        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        $reply = array();
        $reply['relevance'] = array();
        $reply['packages'] = array();

        if (!empty($params['search'])) {
            $searchkeys = explode(' ', $params['search']);

            $SQL = 'SELECT package, COUNT(package) AS cnt FROM {block_edupublisher_metadata} WHERE 1=0 OR ';
            for ($b = 0; $b < count($searchkeys); $b++) {
                if (is_numeric($searchkeys[$b])) {
                    $SQL .= ' (`content`="' . $searchkeys[$b] . '" AND `active`=1)';
                } else {
                    $SQL .= ' (`content` LIKE "%' . $searchkeys[$b] . '%" AND `active`=1)';
                }
                if ($b < (count($searchkeys) -1)) {
                    $SQL .= ' OR';
                }
            }
            $SQL .= ' OR (`content` LIKE "%' . $params['search'] . '%" AND `active`=1)';
            $SQL .= ' GROUP BY package ORDER BY cnt DESC LIMIT 0,20';
            $relevance = $DB->get_records_sql($SQL, array());

            foreach($relevance AS $relevant) {
                if (!isset($reply['relevance'][$relevant->cnt])) {
                    $reply['relevance'][$relevant->cnt] = array();
                }
                $reply['relevance'][$relevant->cnt][] = $relevant->package;
                $reply['packages'][$relevant->package] = block_edupublisher::get_package($relevant->package, true);
            }
            $reply['sql'] = $SQL;
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
        $package = block_edupublisher::get_package($params['packageid'], false);

        if (isset($package->{'cantriggeractive' . $params['type']}) && $package->{'cantriggeractive' . $params['type']}) {
            $active = ($params['to'] >= 1) ? 1 : 0;
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
                $DB->execute('UPDATE {block_edupublisher_metadata} SET `active`=? WHERE `field` LIKE ? ESCAPE "+" AND package=?', array($package->default_active, 'default' . '+_%', $params['packageid']));
            } else {
                $package->default_active = $active;
            }
            block_edupublisher::store_metadata($package, 'default', 'default_active', $package->default_active);

            $DB->execute('UPDATE {block_edupublisher_metadata} SET `active`=? WHERE `field` LIKE ? ESCAPE "+" AND package=?', array($active, $params['type'] . '+_%', $params['packageid']));
            $package->modified = time();
            if ($params['type'] == 'default') {
                block_edupublisher::store_metadata($package, 'default', 'default_active', $package->default_active);
                $package->active = $active;
            } else {
                $activeentry = $DB->get_record('block_edupublisher_metadata', array('package' => $package->id, 'field' => $params['type'] . '_active'));
                if (isset($activeentry) && $activeentry->id > 0) {
                    $activeentry->content = $active;
                    $DB->update_record('block_edupublisher_metadata', $activeentry);
                } else {
                    $DB->insert_record('block_edupublisher_metadata', (object) array('package' => $package->id, 'field' => $params['type'] . '_active', 'content' => $active, 'created' => time(), 'modified' => time(), 'active' => $active));
                }
            }
            $package->active = $package->default_active;
            block_edupublisher::toggle_guest_access($package->course, $package->active);
            $DB->update_record('block_edupublisher_packages', $package);

            global $PAGE;
            $PAGE->set_context(context_system::instance());
            require_login();
            $sendto = array('author');
            if ($package->active) {
                block_edupublisher::store_comment($package, 'comment:template:package_published', $sendto, true, false);
            } else {
                block_edupublisher::store_comment($package, 'comment:template:package_unpublished', $sendto, true, false);
            }
        }
        $chans = $DB->get_records_sql('SELECT id,field,content FROM {block_edupublisher_metadata} WHERE field LIKE "%+_active" ESCAPE "+" AND package=?', array($params['packageid']));
        $statusses = array();
        foreach ($chans AS $chan) {
            $statusses[$chan->field] = ($chan->content == 1) ? 1 : 0;
        }
        return json_encode($statusses);
    }
    public static function trigger_active_returns() {
        return new external_value(PARAM_RAW, 'Returns current state of all types as json encoded object.');
    }
}
