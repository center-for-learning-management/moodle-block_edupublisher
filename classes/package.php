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


/**
 * @property int $id
 * @property int $courseid
 * @property int $userid
 * @property string $title
 */
class package {

    /*
     * Multiple fields without options must be stored in one column.
     * Therefore a delimiter is set. If you change this, you must ensure,
     * that all database-records are modified accordingly!
     */
    public const ARRAY_DELIMITER = ',';

    const FILLING_MODE_SIMPLE = 0;
    const FILLING_MODE_EXPERT = 100;

    private bool $metadata_loaded = false;
    // Object holding all metadata of this package.
    private ?object $metadata = null;
    private ?object $flattened = null;
    private ?object $flattened_absolute = null;

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
            if (empty($record->channels)) {
                return;
            }

            foreach ($record as $field => $value) {
                $this->set_v2($field, $value);
            }

            if ($withmetadata) {
                $this->load_metadata();
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
                || (!empty($this->get('publishas', 'eduthekneu')) && has_capability('block/edupublisher:manageeduthekneu', $context))
                || (!empty($this->get('publishas', 'eduthek')) && has_capability('block/edupublisher:manageeduthek', $context));
            $this->set($canedit, 'canedit');
            $this->set(has_capability('block/edupublisher:managedefault', $context), 'cantriggeractive', 'default');
            $this->set(has_capability('block/edupublisher:manageeduthek', $context), 'cantriggeractive', 'eduthek');
            $this->set(has_capability('block/edupublisher:manageeduthekneu', $context), 'cantriggeractive', 'eduthekneu');
            $this->set(has_capability('block/edupublisher:manageetapas', $context), 'cantriggeractive', 'etapas');

            $canmoderate =
                $this->get('cantriggeractive', 'default')
                || $this->get('cantriggeractive', 'eduthek')
                || $this->get('cantriggeractive', 'eduthekneu')
                || $this->get('cantriggeractive', 'etapas')
                || \block_edupublisher\lib::is_admin();
            $this->set($canmoderate, 'canmoderate');
            $cantriggeractive = ($this->userid == $USER->id) || $this->get('cantriggeractive', 'default') || \block_edupublisher\lib::is_admin();
            $this->set($cantriggeractive, 'cantriggeractive');

            $this->set($this->canrate(), 'canrate');
            $haslti = (!empty($this->get('channel', 'etapas')) || !empty($this->get('channel', 'eduthek')) || !empty($this->get('channel', 'eduthekneu')));
            $this->set($haslti, 'haslti');
            $this->set_v2('canviewuser', $this->canviewuser());
            if (!empty($this->courseid)) {
                $ctx = \context_course::instance($this->courseid, IGNORE_MISSING);
                if (!empty($ctx->id)) {
                    $authoreditingpermission = user_has_role_assignment($this->userid, get_config('block_edupublisher', 'defaultroleteacher'), $ctx->id);
                    $this->set($authoreditingpermission, 'authoreditingpermission');
                }
            }
        }
        if (count($withdetails) == 0 || in_array('rating', $withdetails)) {
            $rating = $DB->get_record('block_edupublisher_rating', array('package' => $this->id, 'userid' => $USER->id));
            $ratingown = (!empty($rating->id)) ? $rating->rating : -1;
            $this->set($ratingown, 'ratingown');
            $ratings = $DB->get_records_sql('SELECT AVG(rating) avg,COUNT(rating) cnt FROM {block_edupublisher_rating} WHERE package=?', array($this->id));
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
                    if (!$this->get($field, $channel)) {
                        $value = [];
                    } else {
                        $value = explode(self::ARRAY_DELIMITER, $this->get($field, $channel));
                    }
                    $this->set_v2($field, $value, $channel);
                }
            }
        }
    }

    private function load_metadata() {
        global $DB;

        if ($this->metadata_loaded) {
            return $this->metadata;
        }

        $this->metadata_loaded = true;

        foreach (\block_edupublisher\lib::channels() as $channel) {
            $short = substr($channel, 0, 3);
            if ($channel == 'eduthekneu')
                $short = 'eduneu';
            $cr = $DB->get_record("block_edupublisher_md_$short", ['package' => $this->id], '*', IGNORE_MISSING);
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

    /**
     * return package item as XML
     * @param package to print.
     * @param includechannels list of channel-data to include, * as first element means 'all'.
     * @param items XMLElement to attach new item to.
     * @return xml string representation in xml format.
     **/
    public function as_xml($includechannels = array(), $items = null) {
        global $DB;
        if (!in_array('default', $includechannels)) {
            $includechannels[] = 'default';
        }

        $exclude = ['channels', 'sourcecourse', 'wwwroot', 'filling_mode'];
        $include = [
            'id', 'course', 'title', 'created', 'modified', 'deleted', 'active',
            'rating', 'ratingaverage', 'ratingcount',
        ];
        if (in_array('etapas', $includechannels) || in_array('eduthekneu', $includechannels)) {
            $this->exacompetencies();
        }
        $flattened = $this->get_flattened(true);

        if ($items instanceof \SimpleXMLElement) {
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
                    if (in_array($field, $exclude)) {
                        continue;
                    }
                    if (!empty($fieldparams['multiple']) && !empty($fieldparams['options']) && !empty($fieldparams['splitcols'])) {
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
                        if ($field == 'image') {
                            $valtoset = $this->get_preview_image_url();
                        } else {
                            $valtoset = (!empty($flattened->{"{$channel}_{$field}"})) ? $flattened->{"{$channel}_{$field}"} : '';
                        }
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
                $params = ['packageid' => $this->id];
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
        if ($items instanceof \SimpleXMLElement) {
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

            // TODO: wird dieser code noch benötigt?!?
            if (substr($elementname, -13) === "_lticartridge") {
                $cartridge = $xml->addChild("$elementname");
                $cartridge->addAttribute('source', htmlspecialchars($subtree));
                $parent = dom_import_simplexml($cartridge);

                // Suppress errors in case the link of the cartridge is incorrect!
                ob_start();
                $child = simplexml_load_string(file_get_contents($subtree));
                ob_clean();
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
        // old: competencies from modules
        // $sql = "SELECT c.id,c.*
        //             FROM {competency} c
        //             JOIN {competency_modulecomp} mc, {course_modules} cm
        //             WHERE cm.course=? AND cm.id=mc.cmid AND mc.competencyid=c.id
        //         ";

        $competenciesByParent = [];
        if (class_exists(\local_komettranslator\locallib::class)) {
            // new: competencies from course
            $sql = "SELECT c.id, c.*
                    FROM {competency} c
                    JOIN {competency_coursecomp} cc ON cc.competencyid=c.id
                    WHERE cc.courseid=?
                ";
            $competencies = $DB->get_records_sql($sql, [$this->courseid, $this->courseid]);

            foreach ($competencies as $competence) {
                // Try mapping to exacomp.
                $mapping = \local_komettranslator\api::get_copmetency_mapping('descriptor', $competence->id);
                if (!empty($mapping->id) && empty($flagfound[$mapping->sourceid . '_' . $mapping->itemid])) {
                    $title = \local_komettranslator\api::get_competency_longname($competence);
                    $exacomptitles[] = $title;
                    $exacompdatasources[] = $mapping->sourceid;
                    $exacompsourceids[] = $mapping->itemid;
                    $flagfound[$mapping->sourceid . '_' . $mapping->itemid] = true;

                    $parentName = '';
                    $parent = $competence;
                    while ($parent = $DB->get_record('competency', array('id' => $parent->parentid))) {
                        $parentName = $parent->shortname . ($parentName ? ' / ' . $parentName : '');
                    }
                    if (!isset($competenciesByParent[$parentName])) {
                        $competenciesByParent[$parentName] = [];
                    }
                    $competenciesByParent[$parentName][] = $title;
                }
            }
        }

        // $this->set_v2('coursecompetencies', $exacomptitles, 'default');

        // 2. Exacomp competencies
        $sql = "SELECT ecd.id id,ecd.title title, ecd.sourceid sourceid, ecd.source sourceexacomptitles
                    FROM {block_exacompdescriptors} ecd,
                         {block_exacompdescrexamp_mm} ecde,
                         {block_exacompexamples} ecex
                    WHERE ecex.courseid=?
                        AND ecex.id=ecde.exampid
                        AND ecde.descrid=ecd.id
                    ORDER BY ecd.title ASC";
        $competencies = $DB->get_records_sql($sql, array($this->courseid));

        foreach ($competencies as $competence) {
            $source = $DB->get_record('block_exacompdatasources', array('id' => $competence->source));
            if (!empty($source->id) && empty($flagfound[$source->source . '_' . $competence->sourceid])) {
                $exacompdatasources[] = $source->source;
                $exacompsourceids[] = $competence->sourceid;
                $exacomptitles[] = $competence->title;
                $flagfound[$source->source . '_' . $competence->sourceid] = true;

                if (!isset($competenciesByParent[$parentName])) {
                    $competenciesByParent[$parentName] = [];
                }
                $competenciesByParent['Kompetenzraster'][] = $competence->title;
            }
        }

        $this->set(nl2br(implode("\n", $exacomptitles)), 'kompetenzen', 'etapas');
        $this->set(nl2br(implode("\n", $exacomptitles)), 'kompetenzen', 'eduthekneu');
        $this->set($exacompdatasources, 'exacompdatasources', 'default');
        $this->set($exacompsourceids, 'exacompsourceids', 'default');

        return $competenciesByParent;
    }

    /**
     * Get a meta-data field from this package.
     * @param field name.
     * @param channel
     * @return mixed the fields content.
     */
    public function get($field, $channel = '_') {
        return $this->metadata->$channel->$field ?? null;
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

        if (!$absolutepaths && $this->flattened) {
            return $this->flattened;
        }

        if ($absolutepaths && $this->flattened_absolute) {
            return $this->flattened_absolute;
        }

        $flattened = (object)[];
        foreach ($this->metadata as $channel => $fields) {
            foreach ($fields as $field => $value) {
                $fieldid = ($channel == '_') ? $field : "{$channel}_{$field}";
                $flattened->{$fieldid} = $value;
            }
        }

        $flattened->courseid = $flattened->course;
        $flattened->preview_image_url = $this->get_preview_image_url();

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
     **/
    public function is_author_editing(): bool {
        if (!$this->courseid) {
            return false;
        }

        $context = \context_course::instance($this->courseid, IGNORE_MISSING);
        if (!$context) {
            return false;
        }

        return \has_capability('moodle/course:update', $context);
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
        $commentids = array_keys($DB->get_records_sql($sql, array($this->id)));
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
    public function get_form_data() {
        global $DB, $COURSE;

        // Kompetenzenausgabe laden
        $this->exacompetencies();

        if (empty($this->id) && !empty($this->get('sourcecourse'))) {
            $context = \context_course::instance($this->get('sourcecourse'));
        } elseif (!empty($this->courseid)) {
            $context = \context_course::instance($this->courseid);
        } else {
            $context = \context_course::instance($COURSE->id);
        }
        $channels = \block_edupublisher\lib::get_channel_definition();
        foreach ($channels as $channel => $fields) {
            foreach ($fields as $field => $ofield) {
                // If this package is newly created and the field is default_image load course image.
                if (empty($this->id) && $channel == 'default' && $field == 'image') {
                    $draftitemid = file_get_submitted_draft_itemid($channel . '_' . $field);
                    file_prepare_draft_area($draftitemid, $context->id, 'course', 'overviewfiles', 0,
                        array(
                            'subdirs' => (!empty($ofield['subdirs']) ? $ofield['subdirs'] : \block_edupublisher\package_edit_form::$subdirs),
                            'maxbytes' => (!empty($ofield['maxbytes']) ? $ofield['maxbytes'] : \block_edupublisher\package_edit_form::$maxbytes),
                            'maxfiles' => (!empty($ofield['maxfiles']) ? $ofield['maxfiles'] : \block_edupublisher\package_edit_form::$maxfiles),
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

                if (isset($ofield['type']) && $ofield['type'] == 'filemanager') {
                    $draftitemid = file_get_submitted_draft_itemid($channel . '_' . $field);
                    file_prepare_draft_area($draftitemid, $context->id, 'block_edupublisher', $channel . '_' . $field, $this->id,
                        array(
                            'subdirs' => (!empty($ofield['subdirs']) ? $ofield['subdirs'] : \block_edupublisher\package_edit_form::$subdirs),
                            'maxbytes' => (!empty($ofield['maxbytes']) ? $ofield['maxbytes'] : \block_edupublisher\package_edit_form::$maxbytes),
                            'maxfiles' => (!empty($ofield['maxfiles']) ? $ofield['maxfiles'] : \block_edupublisher\package_edit_form::$maxfiles),
                        )
                    );
                    $this->set($draftitemid, $field, $channel);
                }

                if (empty($this->get($field, $channel))) {
                    continue;
                }

                if ($ofield['type'] == 'editor') {
                    $this->set(['text' => $this->get($field, $channel)], $field, $channel);
                }
            }
        }

        // $content_items = $DB->get_records('block_edupublisher_pkg_items', ['packageid' => $this->id], 'sorting');

        $this->set(1, 'exportcourse');

        $data = $this->get_flattened();

        $content_items_old = $DB->get_records('block_edupublisher_pkg_items', ['packageid' => $this->id], 'sorting');

        $data->content_items = array_values($content_items_old);
        $data->content_items = array_map(function($content_item_i, $content_item) {
            $content_item->delete = '0';

            foreach (['files', 'dh_files'] as $fileKey) {
                $content_item->{$fileKey} = $_REQUEST['content_items'][$content_item_i][$fileKey] ?? 0;
                file_prepare_draft_area(
                    $content_item->{$fileKey},
                    $this->get_context()->id,
                    'block_edupublisher',
                    "pkg_item_{$fileKey}",
                    $content_item->id ?? 0,
                    [
                        'subdirs' => 0,
                        'maxfiles' => 10,
                    ]
                );
            }

            return (array)$content_item;
        }, array_keys($data->content_items), $data->content_items);

        $data->origins = $this->load_origins();

        return $data;
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
        $this->flattened = null;
        $this->flattened_absolute = null;
    }

    /**
     * Set a meta-data field in this package.
     */
    public function set_v2(string $field, $value, $channel = '_'): void {
        $this->set($value, $field, $channel);
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
        $this->flattened = null;
        $this->flattened_absolute = null;
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
                'package' => $this->id,
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
                        $recipients[$this->userid] = true;
                        break;
                    case 'commentors':
                        $commentors = $DB->get_records_sql('SELECT DISTINCT(userid) AS id FROM {block_edupublisher_comments} WHERE package=?', array($this->id));
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
     * Stores a package and all of its meta-data based on the data of package_edit_form.
     * @param data Object containing additional data
     **/
    public function store_package($data) {
        global $CFG, $DB;
        // Every author must publish in  the default channel.
        $this->set(1, 'publishas', 'default');

        $context = \context_course::instance($this->courseid);

        // flatten html editors' data.
        foreach ($data as $field => $value) {
            if (!empty($value['text'])) {
                $data->$field = $value['text'];
            }
        }

        // To proceed we must have a package id!
        $subtables = [
            'com' => 'commercial',
            'def' => 'default',
            'edu' => 'eduthek',
            'eduneu' => 'eduthekneu',
            'eta' => 'etapas',
        ];
        if (!$this->id) {
            $packageid = $DB->insert_record('block_edupublisher_packages', $this->get_channel('_'));
            $this->set_v2('id', $packageid);
        } else {
            $packageid = $this->id;
        }
        foreach ($subtables as $subtable => $channel) {
            if (!$DB->record_exists("block_edupublisher_md_{$subtable}", ['package' => $packageid])) {
                $this->set($packageid, 'package', $channel);
                $id = $DB->insert_record("block_edupublisher_md_{$subtable}", $this->get_channel($channel, true));
                $this->set($id, 'id', $channel);
            }
        }

        // Retrieve all channels that we publish to.
        $channels = \block_edupublisher\lib::get_channel_definition();
        $_channels = array();
        foreach ($channels as $channel => $fields) {
            if (!empty($this->get('publishas', $channel))) {
                $_channels[] = $channel;
            }
        }
        $this->set_v2('channels', implode(',', $_channels));

        $this->exacompetencies();

        // Now store all data.
        $channels = \block_edupublisher\lib::get_channel_definition();
        foreach ($channels as $channel => $fields) {
            foreach ($fields as $field => $fieldparams) {
                if (!empty($fieldparams['donotstore'])) {
                    continue;
                }
                $dbfield = $channel . '_' . $field;
                if (!isset($data->{$dbfield})) {
                    continue;
                }

                if ($fieldparams['type'] == 'filemanager' && !empty($draftitemid = file_get_submitted_draft_itemid($dbfield))) {
                    // We retrieve a file and set the value to the url.
                    // Store files and set value to url.
                    $fs = get_file_storage();
                    $options = (object)array(
                        'accepted_types' => (!empty($fieldparams['accepted_types']) ? $fieldparams['accepted_types'] : \block_edupublisher\package_edit_form::$accepted_types),
                        'areamaxbytes' => (!empty($fieldparams['areamaxbytes']) ? $fieldparams['areamaxbytes'] : \block_edupublisher\package_edit_form::$areamaxbytes),
                        'maxbytes' => (!empty($fieldparams['maxbytes']) ? $fieldparams['maxbytes'] : \block_edupublisher\package_edit_form::$maxbytes),
                        'maxfiles' => (!empty($fieldparams['maxfiles']) ? $fieldparams['maxfiles'] : \block_edupublisher\package_edit_form::$maxfiles),
                        'subdirs' => (!empty($fieldparams['subdirs']) ? $fieldparams['subdirs'] : \block_edupublisher\package_edit_form::$subdirs),
                    );
                    file_save_draft_area_files(
                        $draftitemid, $context->id, 'block_edupublisher', $dbfield, $this->id,
                        array('subdirs' => $options->subdirs, 'maxbytes' => $options->maxbytes, 'maxfiles' => $options->maxfiles)
                    );

                    $files = $fs->get_area_files($context->id, 'block_edupublisher', $dbfield, $this->id);
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
                } elseif (!empty($fieldparams['multiple'])) {
                    // tags hat keine "options"
                    $allowedkeys = array_keys($fieldparams['options'] ?? []);
                    if ($fieldparams['splitcols'] ?? false) {

                        // multiple with fixed options and separate columns in table!
                        foreach ($allowedkeys as $val) {
                            $this->set_v2("{$field}_{$val}", in_array($val, $data->{$dbfield}) ? 1 : 0, $channel);
                        }
                    } else {
                        if ($allowedkeys) {
                            // tags hat keine "options" und somit keine allowedkeys
                            $values = array_intersect($allowedkeys, $data->{$dbfield});
                        } else {
                            $values = $data->{$dbfield};
                        }

                        // Multiple without separate columns in table!
                        $this->set_v2($field, implode(self::ARRAY_DELIMITER, $values), $channel);
                    }
                } else {
                    // We retrieve anything else.
                    $this->set_v2($field, $data->$dbfield, $channel);
                }
            }
        }

        if (!empty($this->get('publishas', 'etapas')) || !empty($this->get('publishas', 'eduthek'))) {
            // Publish as lti tools
            $targetcourse = get_course($this->courseid);
            $targetcontext = \context_course::instance($this->courseid);
            require_once("$CFG->dirroot/enrol/lti/lib.php");
            $elp = new \enrol_lti_plugin();
            $ltichannels = array('etapas', 'eduthek', 'eduthekneu');
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
        $files = $fs->get_area_files($context->id, 'block_edupublisher', 'default_image', $this->id);
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
            $context = \context_course::instance($this->courseid);
            \block_edupublisher\lib::clear_file_storage($context, 'course', 'overviewfiles', 0, $fs);

            // Load new image to file area of targetcourse
            $fs = get_file_storage();
            $file_record = array('contextid' => $context->id, 'component' => 'course', 'filearea' => 'overviewfiles',
                'itemid' => 0, 'filepath' => '/', 'filename' => $courseimage->imagename,
                'timecreated' => time(), 'timemodified' => time());
            $fs->create_file_from_pathname($file_record, $courseimage->imagepath);
        }
        $this->set($this->get('title', 'default'), 'title');
        $course = get_course($this->courseid);

        // $course->summary = $this->get_course_summary();
        // $course->summaryformat = 1; // html
        $course->fullname = $this->get('title', 'default');
        $DB->update_record('course', $course);
        rebuild_course_cache($course->id, true);

        $this->store_package_db();

        if (isset($data->content_items) && $data->default_filling_mode == package::FILLING_MODE_SIMPLE) {
            // nur wenn filling_mode nicht auf expert ist, werden die aufgabenstellungen gespeichert

            $context = \context_course::instance($this->courseid);

            $content_items_old = $DB->get_records('block_edupublisher_pkg_items', ['packageid' => $this->id], 'sorting');

            // Falls keine aufgabenstellungen übermittelt wurden, diese NICHT speichern (bzw. vorhandene NICHT löschen)
            // Grund: falls ein resource nicht mehr im status "showFull" ist, werden die aufgabenstellungen-Felder nicht angezeigt und würden sonst gelöscht werden.
            $content_items_to_delete = [];

            $sorting = 0;
            foreach ($data->content_items as $key => $content_item_data) {
                if ($content_item_data['delete']) {
                    if ($content_item_data['id'] && isset($content_items_old[$content_item_data['id']])) {
                        $content_items_to_delete[$content_item_data['id']] = $content_item_data;
                    }

                    continue;
                }

                $data = (object)[
                    'packageid' => $this->id,
                    'description' => $content_item_data['description'],
                    'link' => $content_item_data['link'],
                    'didaktische_hinweise' => $content_item_data['didaktische_hinweise'],
                    'sorting' => $sorting++,
                ];

                $content_item = $DB->get_record('block_edupublisher_pkg_items', [
                    'id' => (int)($content_item_data['id'] ?? 0),
                    'packageid' => $this->id,
                ]);
                if (!$content_item) {
                    $data->id = $DB->insert_record('block_edupublisher_pkg_items', (object)$data);
                    $content_item = $data;
                } else {
                    $data->id = $content_item->id;
                    $DB->update_record('block_edupublisher_pkg_items', (object)$data);
                }

                $handleUpload = function($fileKey) use ($content_item, $content_item_data, $context) {
                    if (empty($content_item_data[$fileKey])) {
                        return;
                    }

                    // Now save the files in correct part of the File API.
                    file_save_draft_area_files(
                        $content_item_data[$fileKey],
                        $context->id,
                        'block_edupublisher',
                        'pkg_item_' . $fileKey,
                        $content_item->id,
                        [
                            'subdirs' => 0,
                            'maxfiles' => 10,
                            'accepted_types' => ['image', 'document', '.h5p'],
                        ]
                    );
                };

                $handleUpload('files');
                $handleUpload('dh_files');
            }

            $fs = get_file_storage();
            foreach ($content_items_to_delete as $delete_id => $tmp) {
                $DB->delete_records('block_edupublisher_pkg_items', ['id' => $delete_id]);

                // also delete the files
                foreach (['files', 'dh_files'] as $fileKey) {
                    $files = $fs->get_area_files($context->id, 'block_edupublisher', 'pkg_item_' . $fileKey, $delete_id);
                    foreach ($files as $file) {
                        $file->delete();
                    }
                }
            }
        }
    }

    public static function create(object $data): static {
        global $CFG, $DB;

        $category = \get_config('block_edupublisher', 'category');

        // // course_category exists?
        // $course_category = $DB->get_record('course_categories', ['idnumber' => 'block_edupublisher']);
        //
        // if (!$course_category) {
        //     $course_category = \core_course_category::create([
        //         'name' => 'Edupublisher Courses',
        //         'parent' => 0,
        //         'idnumber' => 'block_edupublisher',
        //         'description' => 'Automatically created',
        //     ]);
        // }

        $course = new \stdClass();
        $course->shortname = $data->default_title . '-' . round((microtime(true) - 1600000000) * 1000);
        $course->fullname = $data->default_title;
        $course->summary = '';

        $course->idnumber = 'block_edupublisher-' . round((microtime(true) - 1600000000) * 1000);
        // $course->format = $courseconfig->format;
        $course->visible = 0;
        // $course->newsitems = $courseconfig->newsitems;
        // $course->showgrades = $courseconfig->showgrades;
        // $course->showreports = $courseconfig->showreports;
        // $course->maxbytes = $courseconfig->maxbytes;
        // $course->groupmode = $courseconfig->groupmode;
        // $course->groupmodeforce = $courseconfig->groupmodeforce;
        // $course->enablecompletion = $courseconfig->enablecompletion;
        // Insert default names for teachers/students, from the current language.

        // $course->category = $course_category->id;
        $course->category = intval($category);

        // $course->startdate = time();
        // Choose a sort order that puts us at the start of the list!
        $course->sortorder = 0;

        require_once($CFG->dirroot . '/course/lib.php');
        $course = \create_course($course);

        $context = \context_course::instance($course->id);
        \block_edupublisher\lib::add_to_context($context);

        $package = new static();
        $package->set_v2('active', 0);
        $package->set_v2('course', $course->id);

        $package->store_package($data);

        // Weiterhin Schreibrechte geben?
        \block_edupublisher\lib::role_set(array($package->courseid), array($package->userid), 'defaultroleteacher');

        return $package;
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
            'eduneu' => 'eduthekneu',
            'eta' => 'etapas',
        ];
        foreach ($channels as $chan => $channel) {
            if (empty($this->get('id', $channel))) {
                $rec = $DB->get_record('block_edupublisher_md_' . $chan, ['package' => $this->id]);
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
                    'package' => $this->id,
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

    public function can_edit(): bool {
        return $this->get('canedit') ?: false;
    }

    public function __isset(string $name): bool {
        return $this->__get_internal($name, true) !== null;
    }

    public function __get(string $name): mixed {
        return $this->__get_internal($name, false);
    }

    private function __get_internal(string $name, bool $isset) {
        if (in_array($name, ['id', 'userid'])) {
            return (int)$this->get($name);
        } elseif (in_array($name, ['title'])) {
            return $this->get($name);
        } elseif (preg_match('!^(default|etapas|eduthekneu|eduthek)_(.*)$!', $name, $matches)) {
            $this->load_metadata();
            return $this->get($matches[2], $matches[1]);
        } elseif ($name == 'courseid') {
            return (int)$this->get('course');
        } elseif ($isset) {
            return null;
        } else {
            throw new \moodle_exception("Property $name not allowed");
        }
    }

    public function get_context(): \context_course {
        return \context_course::instance($this->courseid);
    }

    public function get_preview_image(): ?\stored_file {
        $fs = get_file_storage();
        return current($fs->get_area_files($this->get_context()->id, 'block_edupublisher', 'default_image', $this->id, '', false)) ?: null;
    }

    public function get_preview_image_url(): ?\moodle_url {
        $file = $this->get_preview_image();
        return $file ? \moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename()) : null;
    }

    public function canrate(): bool {
        global $USER;
        return ($this->userid != $USER->id);
    }

    public function canviewuser(): bool {
        return !!\block_edupublisher\lib::is_admin() || $this->get('cantriggeractive', 'etapas');
    }

    public function get_rating_data(): object {
        global $CFG;

        return (object)[
            'canrate' => $this->canrate(),
            'ratingselection' => $this->get('ratingselection'),
            'showpreviewbutton' => false,
            'uniqid' => '',
            'ratingcount' => $this->get('ratingcount'),
            'ratingshowcount' => $this->get('ratingshowcount') ?: false,
        ];
    }

    public function get_formatted_schoollevels() {
        $channels = \block_edupublisher\lib::get_channel_definition();

        $schoollevels = $this->get('schoollevels', 'default');
        if ($schoollevels) {
            return array_map(fn($schoollevel) => $channels['default']['schoollevels']['options'][$schoollevel],
                is_array($schoollevels) ? $schoollevels : explode(package::ARRAY_DELIMITER, $schoollevels));
        }

        return [];
    }

    public function get_formatted_subjectareas() {
        $channels = \block_edupublisher\lib::get_channel_definition();

        $subjectareas = $this->get('subjectareas', 'default');
        if ($subjectareas) {
            return array_map(fn($subjectarea) => $channels['default']['subjectareas']['options'][$subjectarea],
                is_array($subjectareas) ? $subjectareas : explode(package::ARRAY_DELIMITER, $subjectareas));
        }

        return [];

    }

    public function get_formatted_zeitbedarf() {
        $value = $this->get('zeitbedarf', 'etapas');
        if (!$value) {
            return null;
        }

        $channels = \block_edupublisher\lib::get_channel_definition();
        return $channels['etapas']['zeitbedarf']['options'][$value];
    }

    public function get_formatted_contenttypes() {
        $channels = \block_edupublisher\lib::get_channel_definition();

        $contenttypes = $this->get('contenttypes', 'eduthekneu');
        if ($contenttypes) {
            return array_map(fn($contenttype) => $channels['eduthekneu']['contenttypes']['options'][$contenttype],
                is_array($contenttypes) ? $contenttypes : explode(package::ARRAY_DELIMITER, $contenttypes));
        }

        return [];
    }

    public function get_formatted_purposes() {
        $channels = \block_edupublisher\lib::get_channel_definition();

        $purposes = $this->get('purposes', 'eduthekneu');
        if ($purposes) {
            return array_map(fn($purpose) => $channels['eduthekneu']['purposes']['options'][$purpose],
                is_array($purposes) ? $purposes : explode(package::ARRAY_DELIMITER, $purposes));
        }

        return [];
    }
}
