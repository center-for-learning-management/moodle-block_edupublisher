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
    /*
     * Multiple fields without options must be stored in one column.
     * Therefore a delimiter is set. If you change this, you must ensure,
     * that all database-records are modified accordingly!
     */
    public const ARRAY_DELIMITER = '~~~|~~~';
    // Object holding all metadata of this package.
    private $metadata;

    /**
     * Loads a package from database or creates and empty one prior to insert.
     * @param id ID of package or 0
     * @param withmetadata also load all metadata
     * @param withdetails array specifying which details to include. If empty include everything!
     * @return a package as array.
     **/
    public function __construct($id = 0, $withmetadata = false, $withdetails = []) {
        global $CFG, $DB, $USER;
        $this->metadata = (object)[];
        $basedata = [
            'id' => 0,
            'course' => 0,
            'sourcecourse' => 0,
            'channels' => '',
            'title' => '',
            'userid' => $USER->id,
            'created' => time(),
            'modified' => time(),
            'deleted' => 0,
            'active' => 0,
        ];
        $this->set_array($basedata);
        if ($id > 0) {
            $record = $DB->get_record('block_edupublisher_packages', array('id' => $id), '*', IGNORE_MISSING);
            if (empty($record->channels))
                return;

            foreach ($record as $field => $value) {
                $this->set($value, $field);
            }

            if ($withmetadata) {
                foreach (\block_edupublisher\lib::channels() as $channel) {
                    $short = substr($channel, 0, 3);
                    $cr = $DB->get_record("block_edupublisher_md_$short", ['package' => $id], '*', IGNORE_MISSING);
                    if (empty($cr->id))
                        continue;
                    foreach ($cr as $field => $value) {
                        $this->set($value, $field, $channel);
                        if (preg_match('/<\s?[^\>]*\/?\s?>/i', $value ?? '')) {
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
                ? get_string('etapas_status_' . $this->get('status', 'etapas'), 'block_edupublisher')
                : $this->get('status', 'etapas');
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
            foreach ($ratings as $rating) {
                $this->set(round($rating->avg ?? 0), 'ratingaverage');
                $this->set(intval($rating->cnt ?? 0), 'ratingcount');
            }
            $ratingselection = [];
            $max = 5;
            for ($a = 0; $a < $max; $a++) {
                $rating = $a + 1;
                $ratingselection[$a] = array(
                    'num' => $rating,
                    'active' => ($this->get('ratingaverage') >= $rating) ? 1 : 0,
                    'selected' => ($this->get('ratingown') == $rating) ? 1 : 0,
                );
            }
            $this->set($ratingselection, 'ratingselection');
        }

        // Explode all multiple fields without splitcols.
        $channels = \block_edupublisher\lib::get_channel_definition();
        foreach ($channels as $channel => $fields) {
            foreach ($fields as $field => $fieldparams) {
                if (!empty($fieldparams['multiple']) && empty($fieldparams['splitcols'])) {
                    $this->set(explode(self::ARRAY_DELIMITER, $this->get($field, $channel) ?? ''), $field, $channel);
                }
            }
        }
    }

    /**
     * return package item as XML
     * @param package to print.
     * @param includechannels list of channel-data to include, * as first element means 'all'.
     * @param items XMLElement to attach new item to.
     * @return xml string representation in xml format.
     **/
    public function as_xml($includechannels = array(), $items = '') {
        global $DB;
        if (!in_array('default', $includechannels)) {
            $includechannels[] = 'default';
        }

        $exclude = ['channels', 'sourcecourse', 'wwwroot'];
        $include = [
            'id', 'course', 'title', 'created', 'modified', 'deleted', 'active',
            'rating', 'ratingaverage', 'ratingcount',
        ];
        if (in_array('etapas', $includechannels)) {
            $this->exacompetencies();
        }
        $flattened = $this->get_flattened(true);

        if (get_class($items) == 'SimpleXMLElement') {
            $item = $items->addChild('item');
        } else {
            $item = new \SimpleXMLElement('<item />');
        }

        if (!empty($flattened->deleted)) {
            $item->addChild("id", $flattened->id);
            $item->addChild("active", 0);
            $item->addChild("deleted", $flattened->deleted);
        } else {
            foreach ($include as $inc) {
                $item->addChild($inc, htmlspecialchars($flattened->{$inc}));
            }
            $channels = \block_edupublisher\lib::get_channel_definition();
            foreach ($channels as $channel => $fields) {
                if (!in_array($channel, $includechannels) && $includechannels[0] != '*') {
                    continue;
                }
                foreach ($fields as $field => $fieldparams) {
                    // Exclude some fields
                    if (in_array($field, $exclude))
                        continue;
                    if (!empty($fieldparams['multiple']) && !empty($fieldparams['options'])) {
                        $values = [];
                        foreach ($fieldparams['options'] as $option => $optionlabel) {
                            if (!empty($flattened->{"{$channel}_{$field}_{$option}"})) {
                                $values[] = $option;
                            }
                        }
                        $this->as_xml_array($item, "{$channel}_{$field}", $values);
                    } else if (!empty($flattened->{"{$channel}_{$field}"})) {
                        $this->as_xml_array($item, $channel . '_' . $field, $flattened->{"{$channel}_{$field}"});
                    } else {
                        $valtoset = (!empty($flattened->{"{$channel}_{$field}"})) ? $flattened->{"{$channel}_{$field}"} : '';
                        $item->addChild($channel . '_' . $field, htmlspecialchars(str_replace("\n", "", $valtoset)));
                    }
                }
            }

            if (in_array('etapas', $includechannels)) {
                $sql = "SELECT bee.*,u.firstname,u.lastname
                            FROM {block_edupublisher_evaluatio} bee, {user} u
                            WHERE bee.packageid = :packageid
                                AND u.id = bee.userid
                            ORDER BY evaluated_on DESC";
                $params = ['packageid' => $this->get('id')];
                $evaluations = $DB->get_records_sql($sql, $params);
                if (count($evaluations) > 0) {
                    $evalsitem = $item->addChild('evaluations');
                    foreach ($evaluations as $e) {
                        $evalitem = $evalsitem->addChild('evaluation');
                        // The evaluation
                        $evalitem->addChild('id', $e->id);
                        $evalitem->addChild('evaluated_on', $e->evaluated_on);
                        $evalitem->addChild('evaluated_at', $e->evaluated_at);
                        $evalitem->addChild('comprehensible_description', htmlspecialchars($e->comprehensible_description));
                        $evalitem->addChild('suitable_workflow', htmlspecialchars($e->suitable_workflow));
                        $evalitem->addChild('reasonable_preconditions', htmlspecialchars($e->reasonable_preconditions));
                        $evalitem->addChild('correct_content', htmlspecialchars($e->correct_content));
                        $evalitem->addChild('improvement_specification', htmlspecialchars($e->improvement_specification));
                        $evalitem->addChild('technology_application', htmlspecialchars($e->technology_application));
                        $evalitem->addChild('comments', htmlspecialchars($e->comments));
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
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $xmlinfo->addChild("$key");
                    $this->as_xml_array2($value, $subnode);
                } else {
                    $subnode = $xmlinfo->addChild("$key");
                    $this->as_xml_array2($value, $subnode);
                }
            } else {
                $xmlinfo->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    /**
     * Recursively adds the content of an array to an xml-tree.
     * @param xml reference to the SimpleXMLElement
     * @param subtree the array to add.
     */
    private function as_xml_array(&$xml, $elementname, $subtree) {
        if (substr($elementname, -6) === ":dummy")
            return;
        if (is_array($subtree) || is_object($subtree)) {
            $subtree = (array)$subtree;
            // This subtree again is an array, go deeper.
            $keys = array_keys($subtree);
            $element = $xml->addChild("$elementname");
            foreach ($keys as $key) {
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
                // Suppress errors in case the link of the cartridge is incorrect!

                $child = simplexml_load_string(file_get_contents($subtree));
                if ($child) {
                    $child = dom_import_simplexml($child);

                    if ($child) {
                        // Import the <cat> into the dictionary document
                        $child = $parent->ownerDocument->importNode($child, TRUE);

                        // Append the <cat> to <c> in the dictionary
                        $parent->appendChild($child);
                    }
                }
            } else {
                $element = $xml->addChild("$elementname", htmlspecialchars(str_replace("\n", "", $subtree)));
            }
        }
    }

    /**
     * Load exacomp competencies for this package.
     */
    public function exacompetencies() {
        global $CFG, $DB;
        // Get competencies.
        $exacompdatasources = array();
        $exacompsourceids = array();
        $exacomptitles = array();
        $flagfound = array();

        // 1. Moodle competencies
        $sql = "SELECT c.id,c.*
                    FROM {competency} c, {competency_modulecomp} mc, {course_modules} cm
                    WHERE cm.course=? AND cm.id=mc.cmid AND mc.competencyid=c.id";
        $competencies = $DB->get_records_sql($sql, array($this->get('course')));
        $supportstranslator = file_exists("$CFG->dirroot/local/komettranslator/version.php");
        foreach ($competencies as $competence) {
            if ($supportstranslator) {
                // Try mapping to exacomp.
                $mapping = \local_komettranslator\locallib::mapping_internal('descriptor', $competence->id);
                if (!empty($mapping->id) && empty($flagfound[$mapping->sourceid . '_' . $mapping->itemid])) {
                    $exacomptitles[] = !empty($competence->description) ? $competence->description : $competence->shortname;
                    $exacompdatasources[] = $mapping->sourceid;
                    $exacompsourceids[] = $mapping->itemid;
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
        $competencies = $DB->get_records_sql($sql, array($this->get('course')));

        foreach ($competencies as $competence) {
            $source = $DB->get_record('block_exacompdatasources', array('id' => $competence->source));
            if (!empty($source->id) && empty($flagfound[$source->source . '_' . $competence->sourceid])) {
                $exacompdatasources[] = $source->source;
                $exacompsourceids[] = $competence->sourceid;
                $exacomptitles[] = $competence->title;
                $flagfound[$source->source . '_' . $competence->sourceid] = true;
            }
        }
        $this->set(nl2br(implode("\n", $exacomptitles)), 'kompetenzen', 'etapas');
        $this->set($exacompdatasources, 'exacompdatasources', 'default');
        $this->set($exacompsourceids, 'exacompsourceids', 'default');
        $this->set($exacomptitles, 'exacomptitles', 'default');
    }

    /**
     * Get a meta-data field from this package.
     * @param field name.
     * @param channel
     * @return the fields content.
     */
    public function get($field, $channel = '_') {
        if (!empty($this->metadata->$channel->$field)) {
            return $this->metadata->$channel->$field;
        }
    }

    /**
     * Get data of particular channel.
     * @param channel the channel to return.
     * @param flatten (optional) whether or not to flatten objects and arrays.
     */
    public function get_channel($channel = '_', $flatten = false) {
        if (empty($this->metadata->$channel))
            return;
        if ($flatten) {
            $md = json_decode(json_encode($this->metadata->$channel));
            foreach ($md as $field => &$value) {
                if (is_array($value) || is_object($value)) {
                    // Convert recursively to an array.
                    $value = json_decode(json_encode($value), 1);
                    if (count($value) == count($value, COUNT_RECURSIVE)) {
                        // This is a onedimensional array - implode.
                        $value = implode(self::ARRAY_DELIMITER, $value);
                    } else {
                        // This is a multidimensional array - json_encode.
                        $value = json_encode($value);
                    }
                }
            }
            return $md;
        } else {
            return $this->metadata->$channel;
        }
    }

    /**
     * Get metadata flattened in one object. Intended for use with templates.
     * @param absolutepaths whether or not relative paths should be extended by wwwroot.
     * @return object with all metadata.
     */
    public function get_flattened($absolutepaths = false) {
        global $CFG;
        if (!$absolutepaths && !empty($this->flattened)) {
            return $this->flattened;
        }
        if ($absolutepaths && !empty($this->flattened_absolute)) {
            return $this->flattened_absolute;
        }
        $flattened = (object)[];
        foreach ($this->metadata as $channel => $fields) {
            foreach ($fields as $field => $value) {
                $fieldid = ($channel == '_') ? $field : "{$channel}_{$field}";
                $flattened->{$fieldid} = $value;
            }
        }

        $this->flattened = $flattened;
        $this->flattened_absolute = \json_decode(\json_encode($flattened));
        if (!empty($this->flattened_absolute->default_image)) {
            $this->flattened_absolute->default_image = $CFG->wwwroot . $this->flattened_absolute->default_image;
        }

        if ($absolutepaths) {
            return $this->flattened_absolute;
        } else {
            return $this->flattened;
        }
    }

    /**
     * Get all existing keys within a channel.
     */
    public function get_keys($channel = '_') {
        if (!empty($this->metadata->$channel)) {
            return array_keys((array)$this->metadata->$channel);
        }
    }

    /**
     * Checks if a user can edit a package (has course:update-capability).
     * @param userid to check, if not set use $USER->id
     * @return true if user is author of a package.
     **/
    public function is_author_editing($userid = 0) {
        global $USER;
        if (empty($this->get('course')))
            return false;
        if (empty($userid))
            $userid = $USER->id;
        $ctx = \context_course::instance($this->get('course'), IGNORE_MISSING);
        if (empty($ctx->id))
            return false;
        return has_capability('moodle/course:update', $ctx);
    }

    /**
     * Load a specific comment and enhance data.
     * @param id of comment
     */
    public function load_comment($id) {
        global $CFG, $DB;
        $comment = $DB->get_record('block_edupublisher_comments', array('id' => $id));
        $user = $DB->get_record('user', array('id' => $comment->userid));
        $comment->userfullname = fullname($user);
        if (!empty($comment->linkurl)) {
            $comment->linkurl = new \moodle_url($comment->linkurl);
        }
        $ctx = \context_user::instance($comment->userid);
        $comment->userpictureurl = $CFG->wwwroot . '/pluginfile.php/' . $ctx->id . '/user/icon';
        $comment->wwwroot = $CFG->wwwroot;
        return $comment;
    }

    /**
     * Load all comments for a package.
     * @param includeprivate whether or not to include private  communication
     * @param sortorder ASC or DESC
     */
    public function load_comments($private = false, $sortorder = 'ASC') {
        global $DB;
        if ($sortorder != 'ASC' && $sortorder != 'DESC')
            $sortorder = 'ASC';
        $sql = "SELECT id
                    FROM {block_edupublisher_comments}
                    WHERE package=?";
        if (!$private) {
            $sql .= " AND ispublic=1";
        }
        $sql .= ' ORDER BY id ' . $sortorder;
        $commentids = array_keys($DB->get_records_sql($sql, array($this->get('id'))));
        $comments = array();
        foreach ($commentids as $id) {
            $comments[] = $this->load_comment($id);
        }
        return $comments;
    }

    /**
     * Loads originals based of this package.
     * @return array with all package objects that this was a derivative of.
     **/
    public function load_origins() {
        global $DB;
        $origins = array();
        if (!empty($this->get('origins', 'default'))) {
            foreach ($this->get('origins', 'default') as $origin) {
                $origins[] = new package($origin, false);
            }
        }
        return $origins;
    }

    /**
     * Loads possible originals based on the sourcecourse of this package.
     * @return array with all package objects that this was a derivative of.
     **/
    public function load_possible_origins() {
        global $DB;
        if (!empty($this->get('possible_origins'))) {
            return $this->get('possible_origins');
        }
        $possible_origins = array();
        $sql = "SELECT DISTINCT(p.id) AS id
                    FROM {block_edupublisher_packages} p, {block_edupublisher_uses} u
                    WHERE p.id=u.package
                        AND u.targetcourse=?";
        $origins = $DB->get_records_sql($sql, [$this->get('sourcecourse')]);
        foreach ($origins as $origin) {
            $possible_origins[] = new package($origin->id, false);
        }
        $this->set($possible_origins, 'possible_origins');
        return $possible_origins;
    }

    /**
     * Prepares a package to be shown in a form.
     * @return prepared package
     **/
    public function prepare_package_form() {
        global $CFG, $COURSE;

        require_once("$CFG->dirroot/blocks/edupublisher/classes/package_create_form.php");

        if (empty($this->get('id')) && !empty($this->get('sourcecourse'))) {
            $context = \context_course::instance($this->get('sourcecourse'));
        } elseif (!empty($this->get('course'))) {
            $context = \context_course::instance($this->get('course'));
        } else {
            $context = \context_course::instance($COURSE->id);
        }
        $channels = \block_edupublisher\lib::get_channel_definition();
        foreach ($channels as $channel => $fields) {
            foreach ($fields as $field => $ofield) {
                // If this package is newly created and the field is default_image load course image.
                if (empty($this->get('id')) && $channel == 'default' && $field == 'image') {
                    $draftitemid = file_get_submitted_draft_itemid($channel . '_' . $field);
                    file_prepare_draft_area($draftitemid, $context->id, 'course', 'overviewfiles', 0,
                        array(
                            'subdirs' => (!empty($ofield['subdirs']) ? $ofield['subdirs'] : \package_create_form::$subdirs),
                            'maxbytes' => (!empty($ofield['maxbytes']) ? $ofield['maxbytes'] : \package_create_form::$maxbytes),
                            'maxfiles' => (!empty($ofield['maxfiles']) ? $ofield['maxfiles'] : \package_create_form::$maxfiles),
                        )
                    );
                    $this->set($draftitemid, $field, $channel);
                    continue;
                }

                if (isset($ofield['type']) && $ofield['type'] == 'select' && !empty($ofield['multiple'])) {
                    if (!empty($ofield['splitcols'])) {
                        $selected = [];
                        foreach ($ofield['options'] as $option => $optionlabel) {
                            if (!empty($this->get("{$field}_{$option}", $channel))) {
                                $selected[] = $option;
                            }
                        }
                        $this->set($selected, $field, $channel);
                    }

                }

                if (empty($this->get($field, $channel)))
                    continue;
                if ($ofield['type'] == 'editor') {
                    $this->set(['text' => $this->get($field, $channel)], $field, $channel);
                }
                if (isset($ofield['type']) && $ofield['type'] == 'filemanager') {
                    $draftitemid = file_get_submitted_draft_itemid($channel . '_' . $field);
                    file_prepare_draft_area($draftitemid, $context->id, 'block_edupublisher', $channel . '_' . $field, $this->get('id'),
                        array(
                            'subdirs' => (!empty($ofield['subdirs']) ? $ofield['subdirs'] : \package_create_form::$subdirs),
                            'maxbytes' => (!empty($ofield['maxbytes']) ? $ofield['maxbytes'] : \package_create_form::$maxbytes),
                            'maxfiles' => (!empty($ofield['maxfiles']) ? $ofield['maxfiles'] : \package_create_form::$maxfiles),
                        )
                    );
                    $this->set($draftitemid, $field, $channel);
                }
            }
        }

        $this->set(1, 'exportcourse');
    }

    /**
     * Set a meta-data field in this package.
     * @param value
     * @param field name.
     * @param channel
     */
    public function set($value, $field, $channel = '_') {
        if (empty($this->metadata->$channel)) {
            $this->metadata->$channel = (object)[];
        }
        $this->metadata->$channel->$field = $value;
        unset($this->flattened);
        unset($this->flattened_absolute);
    }

    /**
     * Set meta-data based on indexed array.
     * @param values (array) indices are field names
     * @param channel
     */
    public function set_array($values, $channel = '_') {
        if (empty($this->metadata->$channel)) {
            $this->metadata->$channel = (object)[];
        }
        foreach ($values as $field => $value) {
            $this->metadata->$channel->$field = $value;
        }
        unset($this->flattened);
        unset($this->flattened_absolute);
    }

    /**
     * Stores a comment and sents info mails to target groups.
     * @param text
     * @param sendto-identifiers array of identifiers how should be notified
     * @param commentlocalize languageidentifier for sending the comment localized
     * @param channel whether this comment refers to a particular channel.
     * @param linkurl if comment should link to a url.
     * @return id of comment.
     */
    public function store_comment($text, $sendto = array(), $isautocomment = false, $ispublic = 0, $channel = "", $linkurl = "") {
        global $DB, $OUTPUT, $USER;
        if (isloggedin() && !isguestuser($USER)) {
            $comment = (object)array(
                'content' => $text,
                'created' => time(),
                'forchannel' => $channel,
                'isautocomment' => ($isautocomment) ? 1 : 0,
                'ispublic' => ($ispublic) ? 1 : 0,
                'package' => $this->get('id'),
                'permahash' => md5(date('YmdHis') . time() . $USER->firstname),
                'userid' => $USER->id,
                'linkurl' => $linkurl,
            );
            $comment->id = $DB->insert_record('block_edupublisher_comments', $comment);

            if (in_array('allmaintainers', $sendto)) {
                $possiblechannels = array('default', 'eduthek', 'etapas');
                foreach ($possiblechannels as $channel) {
                    if (empty($this->get('publishas', $channel)) || !$this->get('publishas', $channel))
                        continue;
                    if (!in_array('maintainers_' . $channel, $sendto)) {
                        $sendto[] = 'maintainers_' . $channel;
                    }
                }
            }
            $recipients = array();
            $category = get_config('block_edupublisher', 'category');
            $context = \context_coursecat::instance($category);
            foreach ($sendto as $identifier) {
                switch ($identifier) {
                    case 'author':
                        $recipients[$this->get('userid')] = true;
                        break;
                    case 'commentors':
                        $commentors = $DB->get_records_sql('SELECT DISTINCT(userid) AS id FROM {block_edupublisher_comments} WHERE package=?', array($this->get('id')));
                        foreach ($commentors as $commentor) {
                            $recipients[$commentor->id] = true;
                        }
                        break;
                    case 'maintainers_default':
                        $maintainers = get_users_by_capability($context, 'block/edupublisher:managedefault', '', '', '', 100);
                        foreach ($maintainers as $maintainer) {
                            $recipients[$maintainer->id] = true;
                        }
                        break;
                    case 'maintainers_eduthek':
                        $maintainers = get_users_by_capability($context, 'block/edupublisher:manageeduthek', '', '', '', 100);
                        foreach ($maintainers as $maintainer) {
                            $recipients[$maintainer->id] = true;
                        }
                        break;
                    case 'maintainers_etapas':
                        $maintainers = get_users_by_capability($context, 'block/edupublisher:manageetapas', '', '', '', 100);
                        foreach ($maintainers as $maintainer) {
                            $recipients[$maintainer->id] = true;
                        }
                        break;
                    case 'self':
                        $recipients[$USER->id] = true;
                        break;
                }
            }
            if (count($recipients) > 0) {
                $comment = $this->load_comment($comment->id);
                $comment->userpicturebase64 = \block_edupublisher\lib::user_picture_base64($USER->id);
                $fromuser = $USER; // core_user::get_support_user(); //$USER;
                $comments = array();
                $subjects = array();
                $messagehtmls = array();
                $messagetexts = array();

                $recipients = array_keys($recipients);
                foreach ($recipients as $_recipient) {
                    $recipient = $DB->get_record('user', array('id' => $_recipient));
                    if (!isset($subjects[$recipient->lang])) {
                        if (!empty($comment->linkurl)) {
                            $this->set($comment->linkurl->__toString(), 'commentlink');
                        }
                        if ($isautocomment) {
                            $comments[$recipient->lang] = get_string_manager()->get_string($text, 'block_edupublisher', $this->get_flattened(), $recipient->lang);
                            $comments[$recipient->lang] .= get_string_manager()->get_string('comment:notify:autotext', 'block_edupublisher', $this->get_flattened(), $recipient->lang);
                        } else {
                            $comments[$recipient->lang] = $text;
                        }
                        $subjects[$recipient->lang] = get_string_manager()->get_string('comment:mail:subject', 'block_edupublisher', $this->get_flattened(), $recipient->lang);
                        $tmpcomment = $comment;
                        $tmpcomment->content = $comments[$recipient->lang];
                        $messagehtmls[$recipient->lang] = $OUTPUT->render_from_template(
                            'block_edupublisher/package_comment_notify',
                            $tmpcomment
                        );
                        $messagehtmls[$recipient->lang] = \block_edupublisher\lib::enhance_mail_body($subjects[$recipient->lang], $messagehtmls[$recipient->lang]);
                        $messagetexts[$recipient->lang] = html_to_text($messagehtmls[$recipient->lang]);
                    }
                    try {
                        email_to_user($recipient, $fromuser, $subjects[$recipient->lang], $messagetexts[$recipient->lang], $messagehtmls[$recipient->lang], '', '', true);
                    } catch (Exception $e) {
                        throw new \moodle_exception('send_email_failed', 'block_edupublisher', $PAGE->url->__toString(), $recipient, $e->getMessage());
                    }
                }
            }
            return $comment->id;
        }
    }

    /**
     * Updates or inserts a specific metadata field.
     * @param package to set
     * @param channel to which the field belongs
     * @param field complete name of field (channel_fieldname)
     * @param content (optional) content to set, if not set will be retrieved from $package
     **/
    public static function store_metadata($package, $channel, $field, $content = '') {
        global $DB;

        $metaobject = (object)array(
            'package' => $package->id,
            'field' => $field,
            'content' => !empty($content) ? $content : $package->{$field},
            'created' => time(),
            'modified' => time(),
            'active' => !empty($package->{$channel . '_active'}) ? $package->{$channel . '_active'} : 0,
        );

        $o = $DB->get_record('block_edupublisher_metadata', array('package' => $metaobject->package, 'field' => $metaobject->field));
        if (isset($o->id) && $o->id > 0) {
            if ($o->content != $metaobject->content) {
                $metaobject->id = $o->id;
                $metaobject->active = $o->active;
                $DB->update_record('block_edupublisher_metadata', $metaobject);
            }
        } else {
            $DB->insert_record('block_edupublisher_metadata', $metaobject);
        }
    }

    /**
     * Stores a package and all of its meta-data based on the data of package_create_form.
     * @param data Object containing additional data
     **/
    public function store_package($data) {
        global $CFG, $DB;
        // Every author must publish in  the default channel.
        $this->set(1, 'publishas', 'default');

        $context = \context_course::instance($this->get('course'));

        // flatten html editors' data.
        foreach ($data as $field => $value) {
            if (!empty($value['text'])) {
                $data->$field = $value['text'];
            }
        }

        // To proceed we must have a package id!
        if (empty($this->get('id'))) {
            $id = $DB->insert_record('block_edupublisher_packages', $this->get_channel('_'));
            $this->set($id, 'id');
            $this->set($id, 'package', 'commercial');
            $this->set($id, 'package', 'default');
            $this->set($id, 'package', 'eduthek');
            $this->set($id, 'package', 'etapas');

            $id = $DB->insert_record('block_edupublisher_md_com', $this->get_channel('commercial', true));
            $this->set($id, 'id', 'commercial');
            $id = $DB->insert_record('block_edupublisher_md_def', $this->get_channel('default', true));
            $this->set($id, 'id', 'default');
            $id = $DB->insert_record('block_edupublisher_md_edu', $this->get_channel('eduthek', true));
            $this->set($id, 'id', 'eduthek');
            $id = $DB->insert_record('block_edupublisher_md_eta', $this->get_channel('etapas', true));
            $this->set($id, 'id', 'etapas');
        }

        // Retrieve all channels that we publish to.
        $channels = \block_edupublisher\lib::get_channel_definition();
        $_channels = array();
        foreach ($channels as $channel => $fields) {
            if (!empty($this->get('publishas', $channel))) {
                $_channels[] = $channel;
            }
        }
        $this->set(',' . implode(',', $_channels) . ',', 'channels');

        $wordpressaction = 'updated';
        if (empty($this->get('id'))) {
            $wordpressaction = 'created';
        }

        $this->exacompetencies();

        // Now store all data.
        $channels = \block_edupublisher\lib::get_channel_definition();
        foreach ($channels as $channel => $fields) {
            foreach ($fields as $field => $fieldparams) {
                if (!empty($fieldparams['donotstore']))
                    continue;
                $dbfield = $channel . '_' . $field;
                if (empty($data->{$dbfield}))
                    continue;

                if ($fieldparams['type'] == 'filemanager' && !empty($draftitemid = file_get_submitted_draft_itemid($dbfield))) {
                    // We retrieve a file and set the value to the url.
                    // Store files and set value to url.
                    $fs = get_file_storage();
                    require_once("$CFG->dirroot/blocks/edupublisher/classes/package_create_form.php");
                    $options = (object)array(
                        'accepted_types' => (!empty($fieldparams['accepted_types']) ? $fieldparams['accepted_types'] : \package_create_form::$accepted_types),
                        'areamaxbytes' => (!empty($fieldparams['areamaxbytes']) ? $fieldparams['areamaxbytes'] : \package_create_form::$areamaxbytes),
                        'maxbytes' => (!empty($fieldparams['maxbytes']) ? $fieldparams['maxbytes'] : \package_create_form::$maxbytes),
                        'maxfiles' => (!empty($fieldparams['maxfiles']) ? $fieldparams['maxfiles'] : \package_create_form::$maxfiles),
                        'subdirs' => (!empty($fieldparams['subdirs']) ? $fieldparams['subdirs'] : \package_create_form::$subdirs),
                    );
                    file_save_draft_area_files(
                        $draftitemid, $context->id, 'block_edupublisher', $dbfield, $this->get('id'),
                        array('subdirs' => $options->subdirs, 'maxbytes' => $options->maxbytes, 'maxfiles' => $options->maxfiles)
                    );

                    $files = $fs->get_area_files($context->id, 'block_edupublisher', $dbfield, $this->get('id'));
                    $urls = array();
                    foreach ($files as $file) {
                        if (in_array($file->get_filename(), array('.')))
                            continue;
                        $urls[] = '' . \moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                    }
                    if (count($urls) == 0) {
                        $this->set(null, $field, $channel);
                    } elseif (count($urls) == 1) {
                        $this->set($urls[0], $field, $channel);
                    }
                } else if (!empty($data->{$dbfield})) {
                    // We retrieve anything else.
                    unset($allowedoptions);
                    unset($allowedkeys);
                    if (!empty($fieldparams['options'])) {
                        $allowedoptions = $fieldparams['options'];
                        $allowedkeys = array_keys($allowedoptions);
                    }
                    if (!empty($fieldparams['multiple']) && !empty($fieldparams['options']) && !empty($fieldparams['splitcols'])) {
                        // multiple with fixed options and separate columns in table!
                        foreach ($data->$dbfield as $val) {
                            if (in_array($val, $allowedkeys)) {
                                $this->set(1, "{$field}_{$val}", $channel);
                            }
                        }
                    } else if (!empty($fieldparams['multiple'])) {
                        // Multiple without separate columns in table!
                        $this->set(implode(self::ARRAY_DELIMITER, $data->{$dbfield}), $field, $channel);
                    } else {
                        $this->set($data->$dbfield, $field, $channel);
                    }
                }
            }
        }

        if (!empty($this->get('publishas', 'etapas')) || !empty($this->get('publishas', 'eduthek'))) {
            // Publish as lti tools
            $targetcourse = get_course($this->get('course'));
            $targetcontext = \context_course::instance($this->get('course'));
            require_once("$CFG->dirroot/enrol/lti/lib.php");
            $elp = new \enrol_lti_plugin();
            $ltichannels = array('etapas', 'eduthek');
            $_channels = explode(',', $this->get('channels'));
            foreach ($_channels as $_channel) {
                // Only some channels allow to be published as lti tool.
                if (!in_array($_channel, $ltichannels))
                    continue;
                // Check if this channel is already published via LTI.
                if (!empty($this->get('ltisecret', $_channel)))
                    continue;
                $this->set(substr(md5(date("Y-m-d H:i:s") . rand(0, 1000)), 0, 30), 'ltisecret', $_channel);
                $lti = array(
                    'contextid' => $targetcontext->id,
                    'gradesync' => 1,
                    'gradesynccompletion' => 0,
                    'membersync' => 1,
                    'membersyncmode' => 1,
                    'name' => $this->get('title', 'default') . ' [' . $_channel . ']',
                    'roleinstructor' => get_config('block_edupublisher', 'defaultrolestudent'),
                    'rolelearner' => get_config('block_edupublisher', 'defaultrolestudent'),
                    'secret' => $this->get('ltisecret', $_channel),
                );
                $elpinstanceid = $elp->add_instance($targetcourse, $lti);
                if (!empty($elpinstanceid)) {
                    require_once("$CFG->dirroot/enrol/lti/classes/helper.php");
                    $elpinstance = $DB->get_record('enrol_lti_tools', array('enrolid' => $elpinstanceid), 'id', MUST_EXIST);
                    $tool = \enrol_lti\helper::get_lti_tool($elpinstance->id);
                    $this->set('' . \enrol_lti\helper::get_launch_url($elpinstance->id), 'ltiurl', $_channel);
                    $this->set('' . \enrol_lti\helper::get_cartridge_url($tool), 'lticartridge', $_channel);
                }
            }
        }

        // If there is a default_imageurl store the file as course image.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'block_edupublisher', 'default_image', $this->get('id'));
        $courseimage = (object)array('imagepath' => '', 'imagename' => '');
        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                $courseimage->imagename = $file->get_filename();
                $contenthash = $file->get_contenthash();
                $courseimage->imagepath = $CFG->dataroot . '/filedir/' . substr($contenthash, 0, 2) . '/' . substr($contenthash, 2, 2) . '/' . $contenthash;
                $url = \moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                $url = str_replace($CFG->wwwroot, '', $url->__toString());
                $this->set($url, 'image', 'default');
                break;
            }
        }
        if ($courseimage->imagepath != '') {
            $context = \context_course::instance($this->get('course'));
            \block_edupublisher\lib::clear_file_storage($context, 'course', 'overviewfiles', 0, $fs);

            // Load new image to file area of targetcourse
            $fs = get_file_storage();
            $file_record = array('contextid' => $context->id, 'component' => 'course', 'filearea' => 'overviewfiles',
                'itemid' => 0, 'filepath' => '/', 'filename' => $courseimage->imagename,
                'timecreated' => time(), 'timemodified' => time());
            $fs->create_file_from_pathname($file_record, $courseimage->imagepath);
        }
        $this->set($this->get('title', 'default'), 'title');
        $course = get_course($this->get('course'));
        $course->summary = $this->get('summary', 'default');
        $course->fullname = $this->get('title', 'default');
        $DB->update_record('course', $course);
        rebuild_course_cache($course->id, true);
        $this->store_package_db();

        \block_edupublisher\wordpress::action($wordpressaction, $this);
    }

    /**
     * Store the current values to the database.
     */
    public function store_package_db() {
        global $DB;
        $this->set(time(), 'modified');
        $DB->update_record('block_edupublisher_packages', $this->get_channel('_', true));
        $channels = [
            'com' => 'commercial',
            'def' => 'default',
            'edu' => 'eduthek',
            'eta' => 'etapas',
        ];
        foreach ($channels as $chan => $channel) {
            if (empty($this->get('id', $channel))) {
                $rec = $DB->get_record('block_edupublisher_md_' . $chan, ['package' => $this->get('id')]);
                if (!empty($rec->id)) {
                    $this->set($rec->id, 'id', $channel);
                }
            }
            $channelo = $this->get_channel($channel, true);
            if (empty($this->get('id', $channel))) {
                $id = $DB->insert_record('block_edupublisher_md_' . $chan, $channelo);
                $this->set($id, 'id', $channel);
            } else {
                $DB->update_record("block_edupublisher_md_$chan", $channelo);
            }
        }

        $exacompdatasources = $this->get('exacompdatasources');
        $exacompsourceids = $this->get('exacompsourceids');
        $exacomptitles = $this->get('exacomptitles');

        if (!empty($exacompdatasources)) {
            for ($a = 0; $a < count($exacompdatasources); $a++) {
                $params = [
                    'package' => $this->get('id'),
                    'datasource' => $exacompdatasources[$a],
                    'sourceid' => $exacompsourceids[$a],
                ];

                $rec = $DB->get_record('block_edupublisher_md_exa', $params);
                if (empty($rec->id)) {
                    $params['title'] = $exacomptitles[$a];
                    $DB->insert_record('block_edupublisher_md_exa', $params);
                } else {
                    if ($rec->title != $exacomptitles[$a]) {
                        $rec->title = $exacomptitles[$a];
                        $DB->update_record('block_edupublisher_md_exa', $rec);
                    }
                }
            }
        }
    }
}
