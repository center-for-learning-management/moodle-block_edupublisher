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
     * return package item as XML
     * @param package to print.
     * @param includechannels list of channel-data to include, * as first element means 'all'.
     * @param items XMLElement to attach new item to.
     * @return xml string representation in xml format.
    **/
    public static function as_xml($id, $includechannels = array('default'), $items = '') {
        $exclude = array('channels', 'sourcecourse', 'wwwroot');
        $package = (array)self::get_package($id, true, array('rating'));
        $keys = array_keys($package);
        //echo get_class($items);
        if (get_class($items) == 'SimpleXMLElement') {
            $item = $items->addChild('item');
        } else {
            $item = new SimpleXMLElement('<item />');
        }
        if (!empty($package['deleted'])) {
            $item->addChild("id", $package['id']);
            $item->addChild("active", 0);
            $item->addChild("deleted", $package['deleted']);
        } else {
            //$xml = array("\t<item>");
            //print_r($package);
            //print_r($item);
            foreach($keys AS $key) {
                // Exclude some fields.
                if (in_array($key, $exclude)) continue;
                // Exclude dummy-entries etc.
                if (strpos($key, ':') > 0) continue;
                if (substr($key, 0, 6) == 'rating') continue;
                $parts = explode("_", $key);
                if (count($parts) == 1 || in_array($parts[0], $includechannels) || count($includechannels) > 0 && $includechannels[0] == '*') {
                    self::as_xml_array($item, $key, $package[$key]);
                    /*
                    if (is_array($package[$key])) {
                        $skeys = array_keys($package[$key]);
                        foreach ($skeys AS $skey) {
                            $item->addChild($skey, htmlspecialchars($package[$key][$skey]));
                        }
                        //$element = $item->addChild($key);
                        //$item->addChild($key, json_encode($package[$key]));
                    } else {
                        $item->addChild($key, htmlspecialchars($package[$key]));
                    }
                    */

                    /*
                    if (strpos($package[$key], "<") > -1) {
                        $xml[] = "\t\t<$key><![CDATA[" . $package[$key] . "]]></$key>";
                    } else {
                        $xml[] = "\t\t<$key>" . $package[$key] . "</$key>";
                    }
                    */
                }
            }
        }


        //print_r($item);
        //$xml[] = "\t</item>";
        if (get_class($items) != 'SimpleXMLElement') {
            return $item->asXML();
        }

        //return implode("\n", $xml);
    }
    private static function as_xml_array2($array, &$xmlinfo) {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)){
                    $subnode = $xmlinfo->addChild("$key");
                    self::as_xml_array2($value, $subnode);
                } else {
                    $subnode = $xmlinfo->addChild("$key");
                    self::as_xml_array2($value, $subnode);
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
    private static function as_xml_array(&$xml, $elementname, $subtree) {
        if (substr($elementname, -6) === ":dummy") return;
        if (is_array($subtree)) {
            // This subtree again is an array, go deeper.
            $keys = array_keys($subtree);
            $element = $xml->addChild("$elementname");
            foreach ($keys AS $key) {
                self::as_xml_array($element, $key, $subtree[$key]);
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
     * Determines if the plugin can be used.
     * @param die If true will show error and die if requirements are not fulfilled.
     * @param context Course context to test capabilities for.
     * @return true or false
    **/
    public static function check_requirements($die = true, $context = NULL) {
        global $CFG, $OUTPUT, $PAGE;
        $category = get_config('block_edupublisher', 'category');
        if (intval($category) == 0) {
            if ($die) {
                block_edupublisher::print_app_header();

                echo $OUTPUT->render_from_template(
                    'block_edupublisher/alert',
                    array(
                        'type' => 'warning',
                        'content' => 'No category was set by admin!',
                        'url' => $CFG->wwwroot . '/my',
                    )
                );
                block_edupublisher::print_app_footer();
            } else {
                return false;
            }
        }

        $context = $context || (isset($PAGE->context->id)) ? $PAGE->context : context_system::instance();
        $allowguests = get_config('block_edupublisher', 'allowguests');
        if (empty($allowguests) && !has_capability('block/edupublisher:canuse', $context)) {
            if ($die) {
                block_edupublisher::print_app_header();

                echo $OUTPUT->render_from_template(
                    'block_edupublisher/alert',
                    array(
                        'type' => 'warning',
                        'content' => get_string(!empty($allowguests) ? 'missing_capability' : 'guest_not_allowed', 'block_edupublisher'),
                        'url' => $CFG->wwwroot . '/my',
                    )
                );
                block_edupublisher::print_app_footer();
                die();
            } else {
                return false;
            }
        }

        /*
        if ($context && !has_capability('moodle/restore:restoretargetimport', $context)) {
            if ($die) {
                block_edupublisher::print_app_header();

                echo $OUTPUT->render_from_template(
                    'block_edupublisher/alert',
                    array(
                        'type' => 'warning',
                        'content' => 'No capability to for course restoring!',
                        'url' => $CFG->wwwroot . '/my',
                    )
                );
                block_edupublisher::print_app_footer();
                die();
            } else {
                return false;
            }
        }
        */

        return true;
    }
    /**
     * Removes any files in this filearea.
    **/
    public static function clear_file_storage($context, $component, $fieldname, $itemid, $fs = NULL) {
        if (!isset($fs)) {
            $fs = get_file_storage();
        }
        $files = $fs->get_area_files($context->id, $component, $fieldname, $itemid);
        foreach ($files as $f) {
            if (!$f) continue;
            $f->delete();
        }
    }
    /**
     * Generates a complete working html-body.
     * @param subject
     * @param content
     */
    public static function enhance_mail_body($subject, $content) {
        $mailtemplate = get_config('block_edupublisher', 'mail_template');
        return str_replace(array("{{{content}}}", "{{{subject}}}"), array($content, $subject), $mailtemplate);
    }
    /**
     * @param id ID of package or 0
     * @param withmetadata also load all metadata
     * @param withdetails array specifying which details to include. If empty include everything!
     * @return a package as array.
    **/
    public static function get_package($id, $withmetadata = false, $withdetails = array()) {
        global $CFG, $DB, $USER;
        $package = (object)array('id' => 0, 'course' => 0, 'sourcecourse' => 0, 'channels' => '', 'title' => '', 'userid' => $USER->id, 'created' => time(), 'modified' => time());
        if ($id > 0) {
            $package = $DB->get_record('block_edupublisher_packages', array('id' => $id), '*', IGNORE_MISSING);
            if (empty($package->channels)) return (object) array('id' => $id, 'missing' => 1);
            $channels = explode(',', $package->channels);
            $package->_channels = array();
            foreach($channels AS $channel) {
                if (empty($channel)) continue;
                $package->_channels[] = $channel;
            }
            if ($withmetadata) {
                $fields = $DB->get_records('block_edupublisher_metadata', array('package' => $id));
                foreach($fields AS $field) {
                    // Check for multi-select items.
                    $parts = explode('_', $field->field);
                    if (count($parts) == 3) {
                        if (is_numeric($parts[2])) {
                            if (!isset($package->{$parts[0] . '_' . $parts[1]})) {
                                $package->{$parts[0] . '_' . $parts[1]} = array();
                            }
                            $package->{$parts[0] . '_' . $parts[1]}[(int)$parts[2]] = $field->content;
                        }
                    } else {
                        $package->{$field->field} = $field->content;
                        if (preg_match('/<\s?[^\>]*\/?\s?>/i', $field->content)) {
                            $package->{$field->field . ':stripped'} = strip_tags($field->content);
                        }
                    }
                }
            }
        }
        if (!empty($package->etapas_status)) {
            $package->etapas_status_localized = get_string_manager()->string_exists('etapas_status_' . $package->etapas_status, 'block_edupublisher') ? get_string('etapas_status_' . $package->etapas_status, 'block_edupublisher') : $package->etapas_status;
        }
        $package->wwwroot = $CFG->wwwroot;
        if (count($withdetails) == 0 || in_array('internal', $withdetails)) {
            $category = get_config('block_edupublisher', 'category');
            $context = context_coursecat::instance($category);
            $package->canedit = self::is_admin()
                                || self::is_author_editing($package)
                                || (isset($package->default_publishas) && $package->default_publishas && has_capability('block/edupublisher:managedefault', $context))
                                || (isset($package->etapas_publishas) && $package->etapas_publishas && has_capability('block/edupublisher:manageetapas', $context))
                                || (isset($package->eduthek_publishas) && $package->eduthek_publishas && has_capability('block/edupublisher:manageeduthek', $context));
            $package->candelete = self::is_admin();
            $package->cantriggeractivedefault = has_capability('block/edupublisher:managedefault', $context);
            $package->cantriggeractiveetapas = has_capability('block/edupublisher:manageetapas', $context);
            $package->cantriggeractiveeduthek = has_capability('block/edupublisher:manageeduthek', $context);
            $package->canmoderate =
                $package->cantriggeractivedefault
                || $package->cantriggeractiveetapas
                || $package->cantriggeractiveeduthek
                || self::is_admin();
            $package->cantriggeractive = ($package->userid == $USER->id) || $package->cantriggeractivedefault || self::is_admin();
            $package->canrate = ($package->userid != $USER->id);
            $package->haslti = (isset($package->channel_etapas) && $package->channel_etapas || isset($package->channel_eduthek) && $package->channel_eduthek);
            if (self::is_admin() || $package->cantriggeractiveetapas) {
                $package->canviewuser = true;
                $package->_user = array($DB->get_record('user', array('id' => $package->userid), 'id,email,firstname,lastname,username'));
            }
            if (!empty($package->course)) {
                $ctx = context_course::instance($package->course, IGNORE_MISSING);
                if (!empty($ctx->id)) {
                    $package->authoreditingpermission = user_has_role_assignment($package->userid, get_config('block_edupublisher', 'defaultroleteacher'), $ctx->id);
                }
                //die(get_config('block_edupublisher', 'defaultroleteacher') . '|' . $package->authoreditingpermission . '|' . $ctx->id);
            }
        }
        if (count($withdetails) == 0 || in_array('rating', $withdetails)) {
            $rating = $DB->get_record('block_edupublisher_rating', array('package' => $package->id, 'userid' => $USER->id));
            $package->ratingown = (isset($rating->id) && $rating->id > 0) ? $rating->rating : -1;
            $ratings = $DB->get_records_sql('SELECT AVG(rating) avg,COUNT(rating) cnt FROM {block_edupublisher_rating} WHERE package=?', array($package->id));
            foreach($ratings AS $rating) {
                $package->ratingaverage = round($rating->avg); //round(round($rating->avg * 10 / 5) * 5) / 10; // should result in 0.5-steps
                $package->ratingcount = intval($rating->cnt);
            }
            $package->ratingselection = array();
            $max = 5;
            for ($a = 0; $a < $max; $a++) {
                $rating = $a + 1;
                $package->ratingselection[$a] = array('num' => $rating, 'active' => ($package->ratingaverage >= $rating) ? 1 : 0, 'selected' => ($package->ratingown == $rating) ? 1 : 0);
            }
        }
        return $package;
    }
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
     * Returns a definition of all channels.
    **/
    public static function get_channel_definition(){
        global $CFG, $package, $MODE_SHOW_FORM;
        include($CFG->dirroot . '/blocks/edupublisher/classes/channel_definition.php');
        if (isset($package)) {
            $package->default_publishas = 1;
            // Customize definition to package.
            $channels = array_keys($definition);
            foreach($channels AS $channel) {
                // If not set in package check for POST.
                if (empty($package->{$channel . '_publishas'}) && !empty(optional_param($channel . '_publishas', 0, PARAM_INT))) {
                    $package->{$channel . '_publishas'} = 1;
                } elseif(empty($package->{$channel . '_publishas'})) {
                    $package->{$channel . '_publishas'} = 0;
                }
                //echo $channel . '_publishas => ' . $package->{$channel . '_publishas'} . "\n";
                $fields = array_keys($definition[$channel]);
                foreach($fields AS $field) {
                    $ofield = &$definition[$channel][$field];
                    if (!empty($ofield['required'])) {
                        //echo $channel . '_' . $field . " is " . (!empty($ofield['required']) ? 'required' : 'not required') . " and is " . $package->{$channel . '_' . $field} ."\n";
                    }
                    if (isset($ofield['hidden_on_init']) && $ofield['hidden_on_init']) {
                        if (empty($package->id)) {
                            $ofield['type'] = 'hidden';
                        }
                    }
                    if ($channel == 'default' && $field == 'origins') {
                        $possible_origins = self::load_possible_origins($package);
                        $options = array();
                        foreach($possible_origins AS $po) {
                            if (empty($po->id)) continue;
                            $options[$po->id] = $po->title;
                        }
                        if (count($options) > 0) {
                            $ofield['options'] = $options;
                        } else {
                            $ofield['type'] = 'hidden';
                        }
                    }
                    if (isset($ofield['required']) && $ofield['required']
                        && (
                            isset($package->{$channel . '_publishas'}) && $package->{$channel . '_publishas'}
                            ||
                            isset($MODE_SHOW_FORM) && $MODE_SHOW_FORM
                        )) {
                        // Keep it required
                    } else {
                        //$ofield['required'] = 0;
                    }
                }
            }

        }
        return $definition;
    }
    /**
     * Gets a course image if exists.
     * @param course course object
     * @param localpath true if we want local path, false for wwwpath
     * @return object containing fields 'imagename' and 'imagepath'
    **/
    public static function get_course_image($course, $localpath = false) {
        global $CFG;
        // Get Course image if any
        $_course = new course_in_list($course);
        $imagename = '';
        $imagepath = '';
        foreach ($_course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $imagename = $file->get_filename();
                $contenthash = $file->get_contenthash();
                $imagepath = ($localpath)
                                ? $CFG->dataroot . '/filedir/' . substr($contenthash, 0, 2) . '/' . substr($contenthash, 2, 2) . '/' . $contenthash
                                : $CFG->wwwroot . '/pluginfile.php/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . '/' . $file->get_filename()
                                //: '' . moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename())
                            ;
                break;
            }
        }
        return (object) array('imagename' => $imagename, 'imagepath' => $imagepath);
    }
    /**
     * Returns a list of courses from a user
     * Note: teacher is identified by capability 'moodle/course:update'
     * @param user User-Object, if empty use $USER.
     * @param capability to search.
     */
    public static function get_courses($user = null, $capability = '') {
        if (empty($user)) {
            global $USER;
            $user = $USER;
        }
        $courses = enrol_get_all_users_courses($USER->id, true);
        if (empty($capability)) {
            return $courses;
        } else {
            $ids = array_keys($courses);
            foreach($ids AS $id) {
                $context = context_course::instance($id);
                $canedit = has_capability($capability, $context);
                if (!$canedit) unset($courses[$id]);
            }
            return $courses;
        }
    }

    /**
     * @return true if user is sysadmin
    **/
    public static function is_admin() {
        $sysctx = context_system::instance();
        return has_capability('moodle/site:config', $sysctx);
    }
    /**
     * Checks if a user can edit a package (has course:update-capability).
     * @param package to check.
     * @param userid to check, if not set use $USER->id
     * @return true if user is author of a package.
    **/
    public static function is_author_editing($package, $userid = 0) {
        global $USER;
        if (empty($package->course)) return false;
        if (empty($userid)) $userid = $USER->id;
        $ctx = context_course::instance($package->course, IGNORE_MISSING);
        if (empty($ctx->id)) return false;
        return has_capability('moodle/course:update', $ctx);
    }

    /**
     * @param (optional) array of channels we want to check
     * @return true if user is a maintainer
    **/
    public static function is_maintainer($channels = array()) {
        if (self::is_admin()) return true;

        $category = get_config('block_edupublisher', 'category');
        $context = context_coursecat::instance($category);
        $maintainer_default = has_capability('block/edupublisher:managedefault', $context);
        $maintainer_etapas = has_capability('block/edupublisher:manageetapas', $context);
        $maintainer_eduthek = has_capability('block/edupublisher:manageeduthek', $context);

        if (count($channels) == 0) {
            return $maintainer_default || $maintainer_etapas || $maintainer_eduthek;
        }
        if (in_array('default', $channels) && $maintainer_default) return true;
        if (in_array('etapas', $channels) && $maintainer_etapas) return true;
        if (in_array('eduthek', $channels) && $maintainer_eduthek) return true;
        return false;
    }
    /**
     * Indicates if the current user is acting as a publisher for commercial content.
     * @param publisherid (optional) if user is co-worker of a specific publisher.
     * @return true if is publisher or site-admin.
     */
    public static function is_publisher($publisherid = 0) {
        if (self::is_admin()) return true;
        global $DB, $USER;
        if (empty($publisherid)) {
            $chk = $DB->get_record('block_edupublisher_pub_user', array('userid' => $USER->id));
        } else {
            $chk = $DB->get_record('block_edupublisher_pub_user', array('userid' => $USER->id, 'publisherid' => $publisherid));
        }
        return (!empty($chk->id) && $chk->id > 0);
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
     * Loads originals of this package.
     * @param package package to use.
     * @return package object with  originals as __origins.
    **/
    public static function load_origins($package) {
        global $DB;
        $package->__origins = array();
        if (!empty($package->default_origins)) {
            foreach($package->default_origins AS $origin) {
                $package->__origins[] = self::get_package($origin, false);
            }
        }
        return $package;
    }
    /**
     * Loads possible originals based on the sourcecourse of this package.
     * @param package package to use.
     * @return package object with possible originals as __possible_origins.
    **/
    public static function load_possible_origins($package) {
        global $DB;
        $possible_origins = array();
        $origins = $DB->get_records_sql('SELECT DISTINCT(p.id) AS id FROM {block_edupublisher_packages} p, {block_edupublisher_uses} u WHERE p.id=u.package AND u.targetcourse=?', array($package->sourcecourse));
        foreach($origins AS $origin) {
            $possible_origins[] = self::get_package($origin->id, false);
        }
        return $possible_origins;
    }
    /**
     * Notifies maintainers of a specific channel about changes.
     * @param package package that the notifications relates to.
     * @param channel array of channels to select the maintainers for notification, if not set or empty use autodetection.
    **/
    public static function notify_maintainers($package, $channels = array()) {
        global $CFG, $OUTPUT;
        if (count($channels) == 0) {
            if (isset($package->etapas_publishas) && $package->etapas_publishas
                    &&
                    !empty($package->etapas_ltisecret)
                    &&
                    empty($package->etapas_active)
                ) {
                $channels[] = 'etapas';
            }
            if (isset($package->eduthek_publishas) && $package->eduthek_publishas
                    &&
                    !empty($package->eduthek_ltisecret)
                    &&
                    empty($package->eduthek_active)
                ) {
                $channels[] = 'eduthek';
            }
            // Nobody would be responsible for this item. Fall back to default maintainers.
            if (count($channels) == 0 && empty($package->default_active)) {
                $channels[] = 'default';
            }
        }

        // Prepare e-Mail
        $fromuser = core_user::get_support_user();
        $possiblechannels = array('default', 'eduthek', 'etapas');
        foreach($channels AS $channel) {
            if (!in_array($channel, $possiblechannels)) continue;

            $package->_wwwroot = $CFG->wwwroot;
            $messagehtml = $OUTPUT->render_from_template(
                'block_edupublisher/package_' . $channel . '_notify',
                $package
            );
            $subject = get_string($channel . '__mailsubject' , 'block_edupublisher');
            $messagehtml = enhance_mail_body($subject, $messagehtml);
            $messagetext = html_to_text($messagehtml);
            $category = get_config('block_edupublisher', 'category');
            $context = context_coursecat::instance($category);
            $recipients = get_users_by_capability($context, 'block/edupublisher:manage' . $channel, '', '', '', 10);
            foreach($recipients AS $recipient) {
                email_to_user($recipient, $fromuser, $subject, $messagetext, $messagehtml, "", true);
            }
        }

    }
    /**
     * Prepares a package to be shown in a form.
     * @param package to be prepared
     * @return prepared package
    **/
    public static function prepare_package_form($package) {
        global $CFG, $COURSE;
        /*
        $possible_origins = self::load_possible_origins($package);
        // Prepare possible_origins for use in form.
        $package->__possible_origins = json_encode($possible_origins);
        */
        if (empty($package->id) && !empty($package->sourcecourse)) {
            $context = context_course::instance($package->sourcecourse);
        } elseif (!empty($package->course)) {
            $context = context_course::instance($package->course);
        } else {
            $context = context_course::instance($COURSE->id);
        }
        $definition = self::get_channel_definition();
        $channels = array_keys($definition);
        foreach($channels AS $channel) {
            $fields = array_keys($definition[$channel]);
            foreach($fields AS $field) {
                $ofield = $definition[$channel][$field];
                // If this package is newly created and the field is default_image load course image.
                if (empty($package->id) && $channel == 'default' && $field == 'image') {
                    $draftitemid = file_get_submitted_draft_itemid($channel . '_' . $field);
                    file_prepare_draft_area($draftitemid, $context->id, 'course', 'overviewfiles', 0,
                        array(
                            'subdirs' => (!empty($ofield['subdirs']) ? $ofield['subdirs'] : package_create_form::$subdirs),
                            'maxbytes' => (!empty($ofield['maxbytes']) ? $ofield['maxbytes'] : package_create_form::$maxbytes),
                            'maxfiles' => (!empty($ofield['maxfiles']) ? $ofield['maxfiles'] : package_create_form::$maxfiles)
                        )
                    );
                    $package->{$channel . '_' . $field} = $draftitemid;
                    continue;
                }

                if (!isset($package->{$channel . '_' . $field})) continue;
                if ($ofield['type'] == 'editor') {
                    $package->{$channel . '_' . $field} = array('text' => $package->{$channel . '_' . $field});
                }
                if (isset($ofield['type']) && $ofield['type'] == 'filemanager') {
                    require_once($CFG->dirroot . '/blocks/edupublisher/classes/package_create_form.php');
                    $draftitemid = file_get_submitted_draft_itemid($channel . '_' . $field);
                    file_prepare_draft_area($draftitemid, $context->id, 'block_edupublisher', $channel . '_' . $field, $package->id,
                        array(
                            'subdirs' => (!empty($ofield['subdirs']) ? $ofield['subdirs'] : package_create_form::$subdirs),
                            'maxbytes' => (!empty($ofield['maxbytes']) ? $ofield['maxbytes'] : package_create_form::$maxbytes),
                            'maxfiles' => (!empty($ofield['maxfiles']) ? $ofield['maxfiles'] : package_create_form::$maxfiles)
                        )
                    );
                    $package->{$channel . '_' . $field} = $draftitemid;
                }
            }
        }

        $package->exportcourse = 1;
        return $package;
    }
    /**
     * Prints header and injects other sources that are required.
    **/
    public static function print_app_header() {
        global $OUTPUT;
        echo $OUTPUT->header();
    }
    /**
     * If eduvidual is used print eduvidual-footer, otherwise default footer
    **/
    public static function print_app_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
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
     * Stores a comment and sents info mails to target groups.
     * @param package
     * @param text
     * @param sendto-identifiers array of identifiers how should be notified
     * @param commentlocalize languageidentifier for sending the comment localized
     * @param channel whether this comment refers to a particular channel.
     */
    public static function store_comment($package, $text, $sendto = array(), $isautocomment = false, $ispublic = 0, $channel = "") {
        global $DB, $OUTPUT, $USER;
        if (isloggedin() && !isguestuser($USER)) {
            $comment = (object)array(
                'content' => $text,
                'created' => time(),
                'forchannel' => $channel,
                'isautocomment' => ($isautocomment) ? 1 : 0,
                'ispublic' => ($ispublic) ? 1 : 0,
                'package' => $package->id,
                'permahash' => md5(date('YmdHis') . time() . $USER->firstname),
                'userid' => $USER->id,
            );
            $comment->id = $DB->insert_record('block_edupublisher_comments', $comment);

            if (in_array('allmaintainers', $sendto)) {
                $possiblechannels = array('default', 'eduthek', 'etapas');
                foreach($possiblechannels AS $channel) {
                    if (empty($package->{$channel . '_publishas'}) || !$package->{$channel . '_publishas'}) continue;
                    if (!in_array('maintainers_' . $channel, $sendto)) {
                        $sendto[] = 'maintainers_' . $channel;
                    }
                }
            }
            $recipients = array();
            $category = get_config('block_edupublisher', 'category');
            $context = context_coursecat::instance($category);
            foreach ($sendto AS $identifier) {
                switch ($identifier) {
                    case 'author': $recipients[$package->userid] = true; break;
                    case 'commentors':
                        $commentors = $DB->get_records_sql('SELECT DISTINCT(userid) AS id FROM {block_edupublisher_comments} WHERE package=?', array($package->id));
                        foreach ($commentors AS $commentor) {
                            $recipients[$commentor->id] = true;
                        }
                    break;
                    case 'maintainers_default':
                        $maintainers = get_users_by_capability($context, 'block/edupublisher:managedefault', '', '', '', 100);
                        foreach ($maintainers AS $maintainer) {
                            $recipients[$maintainer->id] = true;
                        }
                    break;
                    case 'maintainers_eduthek':
                        $maintainers = get_users_by_capability($context, 'block/edupublisher:manageeduthek', '', '', '', 100);
                        foreach ($maintainers AS $maintainer) {
                            $recipients[$maintainer->id] = true;
                        }
                    break;
                    case 'maintainers_etapas':
                        $maintainers = get_users_by_capability($context, 'block/edupublisher:manageetapas', '', '', '', 100);
                        foreach ($maintainers AS $maintainer) {
                            $recipients[$maintainer->id] = true;
                        }
                    break;
                    case 'self': $recipients[$USER->id] = true; break;
                }
            }
            if (count($recipients) > 0) {
                $comment = self::load_comment($comment->id);
                $comment->userpicturebase64 = block_edupublisher::user_picture_base64($USER->id);
                $fromuser = $USER; // core_user::get_support_user(); //$USER;
                $comments = array();
                $subjects = array();
                $messagehtmls = array();
                $messagetexts = array();

                $recipients = array_keys($recipients);
                foreach($recipients AS $_recipient) {
                    $recipient = $DB->get_record('user', array('id' => $_recipient));
                    if (!isset($subjects[$recipient->lang])) {
                        if ($isautocomment) {
                            $comments[$recipient->lang] = get_string_manager()->get_string($text, 'block_edupublisher', $package, $recipient->lang);
                            $comments[$recipient->lang] .= get_string_manager()->get_string('comment:notify:autotext', 'block_edupublisher', $package, $recipient->lang);
                        } else {
                            $comments[$recipient->lang] = $text;
                        }
                        $subjects[$recipient->lang] = get_string_manager()->get_string('comment:mail:subject' , 'block_edupublisher', $package, $recipient->lang);
                        $tmpcomment = $comment;
                        $tmpcomment->content = $comments[$recipient->lang];
                        $messagehtmls[$recipient->lang] = $OUTPUT->render_from_template(
                            'block_edupublisher/package_comment_notify',
                            $tmpcomment
                        );
                        $messagehtmls[$recipient->lang] = self::enhance_mail_body($subjects[$recipient->lang], $messagehtmls[$recipient->lang]);
                        $messagetexts[$recipient->lang] = html_to_text($messagehtmls[$recipient->lang]);
                    }

                    try {
                        email_to_user($recipient, $fromuser, $subjects[$recipient->lang], $messagetexts[$recipient->lang], $messagehtmls[$recipient->lang], '', '', true);
                    } catch(Exception $e) {}
                }
            }
        }
    }
    /**
     * Stores a package and all of its meta-data based on the data of package_create_form.
     * @param package package data from form.
    **/
    public static function store_package($package) {
        global $CFG, $DB;
        // Every author must publish in  the default channel.
        $package->default_publishas = 1;

        $context = context_course::instance($package->course);

        // Flatten data
        $keys = array_keys((array) $package);
        foreach($keys AS $key) {
            if (isset($package->{$key}['text'])) {
                $package->{$key} = $package->{$key}['text'];
            }
        }

        $package->title = $package->default_title;

        // Retrieve all channels that we publish to.
        $definition = self::get_channel_definition();
        $channels = array_keys($definition);
        $package->_channels = array();
        foreach($channels AS $channel) {
            if (isset($package->{$channel . '_publishas'}) && $package->{$channel . '_publishas'}) {
                $package->_channels[] = $channel;
            }
        }
        $package->channels = ',' . implode(',', $package->_channels) . ',';

        if ($package->id > 0) {
            $original = self::get_package($package->id, true);
            // Save all keys from package to original
            $keys = array_keys((array) $package);
            // Prevent deactivating a channel after it was activated.
            $ignore = array('etapas_publishas', 'eduthek_publishas');
            foreach($keys AS $key) {
                if (in_array($key, $ignore) && !empty($original->{$key})) continue;
                $original->{$key} = $package->{$key};
            }

            $package = $original;
        } else {
            // Create the package to get a package-id for metadata
            $package->active = 0;
            $package->modified = time();
            $package->created = time();
            $package->deleted = 0;
            $package->id = $DB->insert_record('block_edupublisher_packages', $package, true);
        }

        // Get exacomp-Relations
        $competencies = $DB->get_records_sql('SELECT ecd.id id,ecd.title title, ecd.sourceid sourceid, ecd.source source FROM {block_exacompdescriptors} ecd, {block_exacompcompactiv_mm} ecca, {course_modules} cm WHERE cm.course=? AND cm.id=ecca.activityid AND ecca.compid=ecd.id ORDER BY ecd.title ASC', array($package->course));
        $package->default_exacompids = array();
        $package->default_exacomptitles = array();
        foreach($competencies AS $competence) {
            $source = $DB->get_record('block_exacompdatasources', array('id' => $competence->source));
            $package->default_exacompdatasources[] = $source->source;
            $package->default_exacompsourceids[] = $competence->sourceid;
            $package->default_exacomptitles[] = $competence->title;
        }

        // Now store all data.
        $definition = self::get_channel_definition();
        foreach($channels AS $channel) {
            $fields = array_keys($definition[$channel]);
            //echo 'Channel: "' . $channel . '_active" => ' . $package->{$channel . '_active'} . '<br />';
            foreach($fields AS $field) {
                if (!empty($definition[$channel][$field]['donotstore'])) continue;
                $dbfield = $channel . '_' . $field;

                // Remove all meta-objects with pattern channel_field_%, multiple items will be inserted anyway.
                // Attention: Needs to be done here. If an item has been multiple and is then updated to single it may keep deprecated metadata if executed anywhere else.
                $DB->execute('DELETE FROM {block_edupublisher_metadata} WHERE package=? AND `field` LIKE ? ESCAPE "+"', array($package->id, $channel . '+_' . $field . '+_%'));

                if($definition[$channel][$field]['type'] == 'filemanager' && !empty($draftitemid = file_get_submitted_draft_itemid($dbfield))) { // !empty($package->{$dbfield})) {
                    // We retrieve a file and set the value to the url.
                    // Store files and set value to url.
                    $fs = get_file_storage();
                    //self::clear_file_storage($context, 'block_edupublisher', $dbfield, $package->id, $fs);
                    require_once($CFG->dirroot . '/blocks/edupublisher/classes/package_create_form.php');
                    $options = (object)array(
                        'accepted_types' => (!empty($definition[$channel][$field]['accepted_types']) ? $definition[$channel][$field]['accepted_types'] : package_create_form::$accepted_types),
                        'areamaxbytes' => (!empty($definition[$channel][$field]['areamaxbytes']) ? $definition[$channel][$field]['areamaxbytes'] : package_create_form::$areamaxbytes),
                        'maxbytes' => (!empty($definition[$channel][$field]['maxbytes']) ? $definition[$channel][$field]['maxbytes'] : package_create_form::$maxbytes),
                        'maxfiles' => (!empty($definition[$channel][$field]['maxfiles']) ? $definition[$channel][$field]['maxfiles'] : package_create_form::$maxfiles),
                        'subdirs' => (!empty($definition[$channel][$field]['subdirs']) ? $definition[$channel][$field]['subdirs'] : package_create_form::$subdirs),
                    );
                    file_save_draft_area_files(
                        $draftitemid, $context->id, 'block_edupublisher', $dbfield, $package->id,
                        array('subdirs' => $options->subdirs, 'maxbytes' => $options->maxbytes, 'maxfiles' => $options->maxfiles)
                    );

                    $files = $fs->get_area_files($context->id, 'block_edupublisher', $dbfield, $package->id);
                    $urls = array();
                    foreach ($files as $file) {
                        if (in_array($file->get_filename(), array('.'))) continue;
                        $urls[] = '' . moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                    }
                    if (count($urls) == 0) {
                        unset($package->{$dbfield});
                    } elseif(count($urls) == 1) {
                        $package->{$dbfield} = $urls[0];
                    } else {
                        $package->{$dbfield} = $urls;
                        $definition[$channel][$field]['multiple'] = 1;
                    }
                }
                // We retrieve anything else.
                if (isset($package->{$dbfield}) && (is_array($package->{$dbfield}) || !empty($package->{$dbfield})  || is_numeric($package->{$dbfield}))) {
                    unset($allowedoptions);
                    unset($allowedkeys);
                    if (!empty($definition[$channel][$field]['options'])) {
                        $allowedoptions = $definition[$channel][$field]['options'];
                        $allowedkeys = array_keys($allowedoptions);
                    }
                    if (!empty($definition[$channel][$field]['multiple'])) {
                        //$options = array_keys($definition[$channel][$field]['options']);
                        //error_log($dbfield . ' => ' . $package->{$dbfield});
                        if (!is_array($package->{$dbfield})) {
                            $package->{$dbfield} = array($package->{$dbfield});
                        }
                        $options = array_keys($package->{$dbfield});
                        foreach ($options AS $option) {
                            $content = $package->{$dbfield}[$option];
                            if (!isset($allowedkeys) || in_array($content, $allowedkeys)) {
                                self::store_metadata($package, $channel, $channel . '_' . $field . '_' . $option, $content);
                            }
                            if (isset($allowedkeys)) {
                                // If the option text differs from the content store as separate value for search operations.
                                if ($allowedoptions[$content] != $content) {
                                    self::store_metadata($package, $channel, $channel . '_' . $field . '_' . $option . ':dummy', $allowedoptions[$content]);
                                }
                            }
                        }
                    } else {
                        self::store_metadata($package, $field, $dbfield);
                        // If the option text differs from the content store as separate value for search operations.
                        if (isset($allowedkeys) && $allowedoptions[$package->{$dbfield}] != $package->{$dbfield}) {
                            self::store_metadata($package, $field, $dbfield . ':dummy', $allowedoptions[$package->{$dbfield}]);
                        }
                    }
                }
            }
        }

        if (
            isset($package->etapas_publishas) && $package->etapas_publishas
            ||
            isset($package->eduthek_publishas) && $package->eduthek_publishas
        ) {
            // Publish as lti tools
            $targetcourse = get_course($package->course);
            $targetcontext = context_course::instance($package->course);
            //echo "<p>Publishing as LTI</p>";
            //print_r($package->_channels);
            require_once($CFG->dirroot . '/enrol/lti/lib.php');
            $elp = new enrol_lti_plugin();
            $ltichannels = array('etapas', 'eduthek');
            foreach($package->_channels AS $channel) {
                // Only some channels allow to be published as lti tool.
                //echo "<p>Publish for $channel</p>";
                if (!in_array($channel, $ltichannels)) continue;
                // Check if this channel is already published via LTI.
                //echo "<p>LTI Secret currently is " .$package->{$channel . '_ltisecret'} . "</p>";
                if (!empty($package->{$channel . '_ltisecret'})) continue;
                $package->{$channel . '_ltisecret'} = substr(md5(date("Y-m-d H:i:s") . rand(0,1000)),0,30);
                //echo "<p>Set secret to " . $package->{$channel . '_ltisecret'}  . "</p>";
                $lti = array(
                    'contextid' => $targetcontext->id,
                    'gradesync' => 1,
                    'gradesynccompletion' => 0,
                    'membersync' => 1,
                    'membersyncmode' => 1,
                    'name' => $package->title . ' [' . $channel . ']',
                    'roleinstructor' => get_config('block_edupublisher', 'defaultrolestudent'),
                    'rolelearner' => get_config('block_edupublisher', 'defaultrolestudent'),
                    'secret' => $package->{$channel . '_ltisecret'},
                );
                $elpinstanceid = $elp->add_instance($targetcourse, $lti);
                //echo "<p>ELPInstanceID $elpinstanceid</p>";
                if ($elpinstanceid) {
                    require_once($CFG->dirroot . '/enrol/lti/classes/helper.php');
                    $elpinstance = $DB->get_record('enrol_lti_tools', array('enrolid' => $elpinstanceid), 'id', MUST_EXIST);
                    $tool = enrol_lti\helper::get_lti_tool($elpinstance->id);
                    $package->{$channel . '_ltiurl'} = '' . enrol_lti\helper::get_launch_url($elpinstance->id);
                    $package->{$channel . '_lticartridge'} = '' . enrol_lti\helper::get_cartridge_url($tool);
                    //echo "<p>Lti-Data " . $package->{$channel . '_ltiurl'} . " and " . $package->{$channel . '_lticartridge'} . "</p>";
                    self::store_metadata($package, $channel, $channel . '_ltiurl');
                    self::store_metadata($package, $channel, $channel . '_lticartridge');
                    self::store_metadata($package, $channel, $channel . '_ltisecret');
                }
            }
        }

        // If there is a default_imageurl store the file as course image.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'block_edupublisher', 'default_image', $package->id);
        $courseimage = (object) array('imagepath' => '', 'imagename' => '');
        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                $courseimage->imagename = $file->get_filename();
                $contenthash = $file->get_contenthash();
                $courseimage->imagepath = $CFG->dataroot . '/filedir/' . substr($contenthash, 0, 2) . '/' . substr($contenthash, 2, 2) . '/' . $contenthash;
                $package->default_imageurl = '' . moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                break;
            }
        }
        if ($courseimage->imagepath != '') {
            $context = context_course::instance($package->course);
            self::clear_file_storage($context, 'course', 'overviewfiles', 0, $fs);

            // Load new image to file area of targetcourse
            $fs = get_file_storage();
            $file_record = array('contextid' => $context->id, 'component' => 'course', 'filearea' => 'overviewfiles',
                     'itemid' => 0, 'filepath'=>'/', 'filename' => $courseimage->imagename,
                     'timecreated' => time(), 'timemodified' => time());
            $fs->create_file_from_pathname($file_record, $courseimage->imagepath);
        }
        $course = get_course($package->course);
        $course->summary = $package->default_summary;
        $course->fullname = $package->default_title;
        $DB->update_record('course', $course);
        rebuild_course_cache($course->id, true);

        $package->modified = time();
        $DB->update_record('block_edupublisher_packages', $package);

        // Deactivated because of comment-system.
        //block_edupublisher::notify_maintainers($package);
        return $package;
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

        $metaobject = (object) array(
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
                //echo "Update " . print_r($metaobject, 1);
            }
        } else {
            //echo "Insert " . print_r($metaobject, 1);
            $DB->insert_record('block_edupublisher_metadata', $metaobject);
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
    /**
     * Checks whether or not local_eduvidual is installed
     * @return true or false
    **/
    public static function uses_eduvidual(){
        global $CFG;
        return file_exists($CFG->dirroot . '/local/eduvidual/version.php');
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

        $context = context_course::instance($COURSE->id);
        $isenrolled = is_enrolled($context, $USER->id, '', true);
        $canedit = has_capability('moodle/course:update', $context);

        $package = $DB->get_record('block_edupublisher_packages', array('course' => $COURSE->id), '*', IGNORE_MULTIPLE);
        $options = array();
        if (!empty($package->id)) {
            $package = self::get_package($package->id, true);
            if (!empty($package->default_authormailshow) && $package->default_authormailshow == 1) {
                $options[] = array(
                    "title" => $package->default_authorname,
                    "href" => 'mailto:' . $package->default_authormail,
                    //"icon" => '/pix/i/user.svg',
                );
            } else {
                $options[] = array(
                    "title" => $package->default_authorname,
                    //"href" => 'mailto:' . $package->default_authormail,
                    //"icon" => '/pix/i/user.svg',
                );
            }

            $options[] = array(
                "title" => $package->default_licence,
                //"href" => 'mailto:' . $package->default_authormail,
                "icon" => '/pix/i/publish.svg',
            );
            $options[] = array(
                "title" => get_string('details', 'block_edupublisher'),
                "href" => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $package->id,
                "icon" => '/pix/i/hide.svg',
            );
            $options[] = array(
                "title" => $OUTPUT->render_from_template('block_edupublisher/package_rating', $package),
                //"href" => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $package->id,
                "icon" => '/pix/i/scales.svg',
            );

            if (!empty($package->etapas_subtype) && $package->etapas_subtype == 'etapa' && has_capability('block/edupublisher:canseeevaluation', \context_system::instance())) {
                $options[] = array(
                    "title" => get_string('evaluations', 'block_edupublisher'),
                    "href" => $CFG->wwwroot . '/blocks/edupublisher/pages/evaluation.php?packageid=' . $package->id,
                    "icon" => '/pix/i/report.svg',
                );
            }
            // Show use package-button
            $courses = self::get_courses(null, 'moodle/course:update');
            if (count(array_keys($courses)) > 0) {
                $allowsubcourses = \get_config('block_edupublisher', 'allowsubcourses') ? 1 : 0;
                $options[] = array(
                    "title" => get_string('initialize_import', 'block_edupublisher'),
                    "href" => "#",
                    //"icon" => '/pix/i/import.svg',
                    "class" => 'btn btn-primary btn-block',
                    "onclick" => 'require([\'block_edupublisher/main\'], function(MAIN) { MAIN.initImportSelection(' . $package->id . ', 0, ' . $allowsubcourses . '); }); return false;',
                    "style" => 'margin-top: 10px;',
                );
            }
            // If we are enrolled let check if we can selfunenrol
            if (is_enrolled($context, null, 'block/edupublisher:canselfenrol')) {
                $options[] = array(
                    "title" => get_string('self_unenrol', 'block_edupublisher'),
                    "href" => $CFG->wwwroot . "/blocks/edupublisher/pages/self_enrol.php?id=" . $package->course . "&unenrol=1",
                    //"icon" => '/pix/i/import.svg',
                    "class" => 'btn btn-secondary btn-block',
                    "style" => 'margin-top: 10px;',
                );
            }
            if (!empty($package->etapas_active) && !empty($package->etapas_subtype)) {
                $options[] = array(
                    "title" => "<img src=\"" . $CFG->wwwroot . "/blocks/edupublisher/pix/channel/" . str_replace(array(' ', '.'), '', $package->etapas_subtype) . ".png\" style=\"width: 100%; max-width: 170px; margin-top: 20px;\" />",
                );
            }
        } elseif($canedit) {
            $options[] = array(
                "title" => get_string('publish_new_package', 'block_edupublisher'),
                "href" => $CFG->wwwroot . '/blocks/edupublisher/pages/publish.php?sourcecourse=' . $COURSE->id,
                "icon" => '/pix/i/publish.svg',
            );

            $packages = $DB->get_records_sql('SELECT * FROM {block_edupublisher_packages} WHERE sourcecourse=? AND (active=1 OR userid=?)', array($COURSE->id, $USER->id));
            $haspackages = false;
            foreach($packages AS $package) {
                if (!$haspackages) {
                    $options[] = array(
                        "title" => get_string('parts_published', 'block_edupublisher') . ':',
                    );
                    $haspackages = true;
                }
                $options[] = array(
                    "title" => (strlen($package->title) > 25) ? substr($package->title, 0, 23) . '...' : $package->title,
                    "href" => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $package->id,
                    "icon" => '/pix/i/edit.svg',
                );
            }
            $uses = $DB->get_records_sql('SELECT DISTINCT(package) FROM {block_edupublisher_uses} WHERE targetcourse=?', array($COURSE->id));
            $hasuses = false;
            foreach($uses AS $use) {
                if (!$hasuses) {
                    $options[] = array(
                        "title" => get_string('parts_based_upon', 'block_edupublisher') . ':',
                    );
                    $hasuses = true;
                }
                $package = self::get_package($use->package, true);
                if (!empty($package->id)) {
                    $options[] = array(
                        "href" => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $package->id,
                        "icon" => '/pix/i/withsubcat.svg',
                        "subtitle" => get_string('by', 'block_edupublisher') . ' ' . $package->default_authorname,
                        "title" => (strlen($package->title) > 25) ? substr($package->title, 0, 23) . '...' : $package->title,
                    );
                }
            }
        }
        foreach($options AS $option) {
            $tx = $option["title"];
            if (!empty($option["icon"])) $tx = "<img src='" . $CFG->wwwroot . $option["icon"] . "' class='icon'>" . $tx;
            if (!empty($option["href"])) $tx = "
                <a href='" . $option["href"] . "' " . ((!empty($option["onclick"])) ? " onclick=\"" . $option["onclick"] . "\"" : "") . "
                    " . ((!empty($option["class"])) ? " class=\"" . $option["class"] . "\"" : "") . "
                    " . ((!empty($option["style"])) ? " style=\"" . $option["style"] . "\"" : "") . "
                    " . ((!empty($option["target"])) ? " target=\"" . $option["target"] . "\"" : "") . "'>" . $tx . "</a>";
            else  $tx = "<a>" . $tx . "</a>";
            $this->content->text .= $tx . "<br />";
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
