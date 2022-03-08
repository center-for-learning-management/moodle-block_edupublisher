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

namespace block_edupublisher;

defined('MOODLE_INTERNAL') || die;


class package {
    // Array holding all metadata of this package.
    private $metadata = [];

    /**
     * Loads a package from database or creates and empty one prior to insert.
     * @param id ID of package or 0
     * @param withmetadata also load all metadata
     * @param withdetails array specifying which details to include. If empty include everything!
     * @return a package as array.
    **/
    public function __construct($id = 0, $withmetadata = false, $withdetails = []) {
        global $CFG, $DB, $USER;
        $basedata = [
            'id' => 0,
            'course' => 0,
            'sourcecourse' => 0,
            'channels' => '',
            'title' => '',
            'userid' => $USER->id,
            'created' => time(),
            'modified' => time()
        ];
        $this->set_array($basedata);
        if ($id > 0) {
            $record = $DB->get_record('block_edupublisher_packages', array('id' => $id), '*', IGNORE_MISSING);
            if (empty($record->channels)) return;

            if ($withmetadata) {
                foreach (\block_edupublisher\lib::channels() as $channel) {
                    $short = substr($channel, 0, 3);
                    $cr = (array) $DB->get_records("block_edupublisher_md_$short", [ 'package' => $id ], '*', IGNORE_MISSING);
                    foreach(array_keys($cr) AS $field => $value) {
                        $this->set($value, $field, $channel);
                        if (preg_match('/<\s?[^\>]*\/?\s?>/i', $value)) {
                            $this->set(strip_tags($value), "$field:stripped", $channel);
                        }
                    }
                }
            }
        }
        if (!empty($this->get('status', 'etapas'))) {
            $lstr = get_string_manager()->string_exists(
                        'etapas_status_' . $this->get('status', 'etapas'),
                        'block_edupublisher'
                    )
                    ? get_string('etapas_status_' . $package->etapas_status, 'block_edupublisher')
                    : $package->etapas_status;
            $this->set($lstr, 'status_localized', 'etapas');
        }
        $this->set($CFG->wwwroot, 'wwwroot');
        if (count($withdetails) == 0 || in_array('internal', $withdetails)) {
            $category = get_config('block_edupublisher', 'category');
            $context = \context_coursecat::instance($category);
            $this->set(\block_edupublisher\lib::is_admin(), 'candelete');
            $canedit = \block_edupublisher\lib::is_admin()
                        || $this->is_author_editing()
                        || (!empty($this->get('publishas', 'default')) && has_capability('block/edupublisher:managedefault', $context))
                        || (!empty($this->get('publishas', 'etapas')) && has_capability('block/edupublisher:manageetapas', $context))
                        || (!empty($this->get('publishas', 'eduthek')) && has_capability('block/edupublisher:manageeduthek', $context));
            $this->set($canedit, 'canedit');
            $this->set(has_capability('block/edupublisher:managedefault', $context), 'cantriggeractive', 'default');
            $this->set(has_capability('block/edupublisher:manageeduthek', $context), 'cantriggeractive', 'eduthek');
            $this->set(has_capability('block/edupublisher:manageetapas', $context), 'cantriggeractive', 'etapas');

            $canmoderate =
                $this->get('cantriggeractive', 'default')
                || $this->get('cantriggeractive', 'eduthek')
                || $this->get('cantriggeractive', 'etapas')
                || \block_edupublisher\lib::is_admin();
            $this->set($canmoderate, 'canmoderate');
            $cantriggeractive = ($this->get('userid') == $USER->id) || $this->get('cantriggeractive', 'default') || \block_edupublisher\lib::is_admin();
            $this->set($cantriggeractive, 'cantriggeractive');
            $canrate = ($this->get('userid') != $USER->id);
            $this->set($canrate, 'canrate');
            $haslti = (!empty($this->get('channel', 'etapas')) || !empty($this->get('channel', 'eduthek')));
            $this->set($haslti, 'haslti');
            if (\block_edupublisher\lib::is_admin() || $this->get('cantriggeractive', 'etapas')) {
                $this->set(true, 'canviewuser');
                $_user = array($DB->get_record('user', array('id' => $this->get('userid')), 'id,email,firstname,lastname,username'));
                $this->set($_user, '_user');
            }
            if (!empty($this->get('course'))) {
                $ctx = \context_course::instance($this->get('course'), IGNORE_MISSING);
                if (!empty($ctx->id)) {
                    $authoreditingpermission = user_has_role_assignment($this->get('userid'), get_config('block_edupublisher', 'defaultroleteacher'), $ctx->id);
                    $this->set($authoreditingpermission, 'authoreditingpermission');
                }
            }
        }
        if (count($withdetails) == 0 || in_array('rating', $withdetails)) {
            $rating = $DB->get_record('block_edupublisher_rating', array('package' => $this->get('id'), 'userid' => $USER->id));
            $ratingown = (!empty($rating->id)) ? $rating->rating : -1;
            $this->set($ratingown, 'ratingown');
            $ratings = $DB->get_records_sql('SELECT AVG(rating) avg,COUNT(rating) cnt FROM {block_edupublisher_rating} WHERE package=?', array($this->get('id')));
            foreach($ratings AS $rating) {
                $this->set(round($rating->avg), 'ratingaverage');
                $package->set(intval($rating->cnt), 'ratingcount');
            }
            $ratingselection = [];
            $max = 5;
            for ($a = 0; $a < $max; $a++) {
                $rating = $a + 1;
                $ratingselection[$a] = array(
                    'num' => $rating,
                    'active' => ($package->ratingaverage >= $rating) ? 1 : 0,
                    'selected' => ($this->get('ratingown') == $rating) ? 1 : 0
                );
            }
            $this->set($ratingselection, 'ratingselection');
        }
    }

    /**
     * return package item as XML
     * @param package to print.
     * @param includechannels list of channel-data to include, * as first element means 'all'.
     * @param items XMLElement to attach new item to.
     * @return xml string representation in xml format.
    **/
    public function as_xml($id, $includechannels = array('default'), $items = '') {
        global $DB;
        array_unshift($includechannels, '_');
        $exclude = array('channels', 'sourcecourse', 'wwwroot');

        if (get_class($items) == 'SimpleXMLElement') {
            $item = $items->addChild('item');
        } else {
            $item = new SimpleXMLElement('<item />');
        }
        if (!empty($this->get('deleted'))) {
            $item->addChild("id", $this->get('id'));
            $item->addChild("active", 0);
            $item->addChild("deleted", $this->get('deleted'));
        } else {
            $channels = \block_edupublisher\lib::channels();
            array_unshift($channels, '_');
            foreach ($channels as $channel) {
                if (!in_array($channel, $includechannels) && $includechannels[0] != '*') {
                    continue;
                }
                $keys = $this->get_keys($channel);
                foreach($keys AS $key) {
                    // Exclude some fields.
                    if (in_array($key, $exclude)) continue;
                    // Exclude dummy-entries etc.
                    if (strpos($key, ':') > 0) continue;
                    $this->as_xml_array($item, $key, $this->get($key, $channel));
                }
            }

            if (in_array('etapas', $includechannels)) {
                $sql = "SELECT bee.*,u.firstname,u.lastname
                            FROM {block_edupublisher_evaluatio} bee, {user} u
                            WHERE bee.packageid = :packageid
                                AND u.id = bee.userid
                            ORDER BY evaluated_on DESC";
                $params = [ 'packageid' => $this->get('id') ];
                $evaluations = $DB->get_records_sql($sql, $params);
                if (count($evaluations) > 0) {
                    $evalsitem = $item->addChild('evaluations');
                    foreach ($evaluations as $e) {
                        $evalitem = $evalsitem->addChild('evaluation');
                        // The evaluation
                        $evalitem->addChild('id', $e->id);
                        $evalitem->addChild('evaluated_on', $e->evaluated_on);
                        $evalitem->addChild('evaluated_at', $e->evaluated_at);
                        $evalitem->addChild('comprehensible_description', $e->comprehensible_description);
                        $evalitem->addChild('suitable_workflow', $e->suitable_workflow);
                        $evalitem->addChild('reasonable_preconditions', $e->reasonable_preconditions);
                        $evalitem->addChild('correct_content', $e->correct_content);
                        $evalitem->addChild('improvement_specification', $e->improvement_specification);
                        $evalitem->addChild('technology_application', $e->technology_application);
                        $evalitem->addChild('comments', $e->comments);
                        // User data
                        $evalitem->addChild('userid', $e->userid);
                        $evalitem->addChild('firstname', $e->firstname);
                        $evalitem->addChild('lastname', $e->lastname);
                    }
                }
            }
        }
        if (get_class($items) != 'SimpleXMLElement') {
            return $item->asXML();
        }
    }
    private function as_xml_array2($array, &$xmlinfo) {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)){
                    $subnode = $xmlinfo->addChild("$key");
                    $this->as_xml_array2($value, $subnode);
                } else {
                    $subnode = $xmlinfo->addChild("$key");
                    $this->as_xml_array2($value, $subnode);
                }
            }else {
                $xmlinfo->addChild("$key",htmlspecialchars("$value"));
            }
        }
    }
    /**
     * Recursively adds the content of an array to an xml-tree.
     * @param xml reference to the SimpleXMLElement
     * @param subtree the array to add.
     */
    private function as_xml_array(&$xml, $elementname, $subtree) {
        if (substr($elementname, -6) === ":dummy") return;
        if (is_array($subtree)) {
            // This subtree again is an array, go deeper.
            $keys = array_keys($subtree);
            $element = $xml->addChild("$elementname");
            foreach ($keys AS $key) {
                $this->as_xml_array($element, $key, $subtree[$key]);
            }
            //$item->addChild($key, json_encode($package[$key]));
        } else {
            if (is_numeric($elementname)) {
                $elementname = "item";
            }
            //$xml->addChild($elementname, str_replace(array("\n", "\r") , "_____", htmlspecialchars($subtree)));
            if (substr($elementname, -13) === "_lticartridge") {
                $cartridge = $xml->addChild("$elementname");
                $cartridge->addAttribute('source', htmlspecialchars($subtree));
                $parent = dom_import_simplexml($cartridge);
                $child  = dom_import_simplexml(simplexml_load_string(file_get_contents($subtree)));

                if (!empty($child)) {
                    // Import the <cat> into the dictionary document
                    $child  = $parent->ownerDocument->importNode($child, TRUE);

                    // Append the <cat> to <c> in the dictionary
                    $parent->appendChild($child);
                }
            } else {
                $element = $xml->addChild("$elementname", htmlspecialchars(str_replace("\n", "", $subtree)));
            }
        }
    }

    /**
     * Get a meta-data field from this package.
     * @param field name.
     * @param channel
     * @return the fields content.
     */
    public function get($field, $channel = '_') {
        if (!empty($this->metadata[$channel][$field])) {
            return $this->metadata[$channel][$field];
        }
    }
    /**
     * Get all existing keys within a channel.
     */
    public function get_keys($channel = '_') {
        if (!empty($this->metadata[$channel])) {
            return array_keys($this->metadata[$channel]);
        }
    }
    /**
     * Checks if a user can edit a package (has course:update-capability).
     * @param userid to check, if not set use $USER->id
     * @return true if user is author of a package.
    **/
    public function is_author_editing($userid = 0) {
        global $USER;
        if (empty($this->get('course'))) return false;
        if (empty($userid)) $userid = $USER->id;
        $ctx = \context_course::instance($this->get('course'), IGNORE_MISSING);
        if (empty($ctx->id)) return false;
        return has_capability('moodle/course:update', $ctx);
    }

    /**
     * Set a meta-data field in this package.
     * @param value
     * @param field name.
     * @param channel
     */
    public function set($value, $field, $channel = '_') {
        if (empty($this->metadata[$channel])) {
            $this->metadata[$channel] = [];
        }
        $this->metadata[$channel][$field] = $value;
    }

    /**
     * Set meta-data based on indexed array.
     * @param values (array) indices are field names
     * @param channel
     */
    public function set_array($values, $channel = '_') {
        if (empty($this->metadata[$channel])) {
            $this->metadata[$channel] = [];
        }
        foreach ($values as $field => $value) {
            $this->metadata[$channel][$field] = $value;
        }
    }
}
