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
 * @copyright  2017 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;

defined('MOODLE_INTERNAL') || die;

class externalsources {
    private static $debug = false;

    public static function do_sync($external) {
        global $CFG, $DB;

        self::$debug = true; //($CFG->debug == 32767); // Developer debugging

        $PUBLISHER = $DB->get_record('block_edupublisher_pub', array('id' => $external->pubid));
        if (empty($PUBLISHER->id)) {
            echo "<strong>Invalid publisher for external #$external->id</strong>\n";
            return;
        }

        $CATEGORY = intval(\get_config('block_edupublisher', 'category'));
        if (empty($CATEGORY)) {
            echo "<strong>eduPublisher has not configured a valid category</strong>\n";
            return;
        }
        $DEFAULT_FORMAT = \get_config('block_edupublisher', 'externalsources_courseformat');
        if (empty($DEFAULT_FORMAT)) $DEFAULT_FORMAT = 'topics';

        $parser = new \core_xml_parser();

        if (self::$debug) echo "=> Parsing $external->url\n";
        if (substr($external->url, 0, 4) == 'http') {
            $xmlstr = self::fetch_curl($external);
        } else {
            // load the local file.
            $xmlstr = file_get_contents($external->url);
        }
        if (empty($xmlstr)) {
            if (self::$debug) echo "=> Empty response\n";
            return;
        }

        $xml = new \SimpleXMLElement($xmlstr);
        $array = json_decode(json_encode($xml), TRUE);

        if (empty($array['packages'])) {
            if (self::$debug) echo "=> No packages in xml\n";
            return;
        }

        // Flatten sections according to id.
        $SECTIONS = array();
        $_sections = self::flattened_element($array, 'sections', 'section');
        foreach ($_sections as $section) {
            $sectionid = $section['@attributes']['id'];
            $SECTIONS[$sectionid] = $section;
        }
        // Flatten items according to id.
        $ITEMS = array();
        $_items = self::flattened_element($array, 'items', 'item');
        foreach ($_items as $item) {
            $itemid = $item['@attributes']['id'];
            $ITEMS[$itemid] = $item;
        }

        $_packages = self::flattened_element($array, 'packages', 'package');
        foreach ($_packages as $package) {
            $packageid = $package['@attributes']['uniqueid'];
            $timelastmodified = strtotime($package['@attributes']['changed']);
            // Fake empty timelastmodified
            $timelastmodified = 0;
            if (self::$debug) echo "===> Analyzing package $packageid\n";

            $courserec = $DB->get_record('block_edupublisher_extpack', array('extid' => $external->id, 'packageid' => $packageid));
            if (empty($courserec->courseid)) {
                $timelastmodified = 0; // force an update this time.
                // Create new course.
                $course = (object) array();
                $course->category = $CATEGORY;
                $course->fullname = self::shortenname($package['name']);
                $course->summary = $package['name'];
                $course->visible = 0;
                $course->shortname = '[p' . $external->pubid . '-' . $packageid . '-' . md5(date('Ymd')) . ']';
                $course->idnumber = '';
                $course->format = $DEFAULT_FORMAT;
                $course->enablecompletion = 1;
                $course->showgrades = 1;
                $course->showreports = 1;
                require_once($CFG->dirroot . '/course/lib.php');
                $course = \create_course($course);
                if (!empty($course->id)) {
                    // Make this course really empty.
                    require_once($CFG->dirroot . '/course/modlib.php');
                    $cms = $DB->get_records('course_modules', array('course' => $course->id));
                    foreach ($cms as $cm) {
                        \course_delete_module($cm->id);
                    }
                    $courserec = (object) array(
                        'extid' => $external->id,
                        'packageid' => $packageid,
                        'lasttimemodified' => time(),
                        'courseid' => $course->id,
                    );
                    $courserec->id = $DB->insert_record('block_edupublisher_extpack', $courserec);
                }
            } else {
                // Update course data.
                $course = \get_course($courserec->courseid);
                $course->fullname = self::shortenname($package['name']);
                $course->summary = $package['summary'] = $package['name'];
                $DB->update_record('course', $course);
                $DB->set_field('block_edupublisher_extpack', 'lasttimemodified', time(), array('id' => $courserec->id));
            }

            /*
            // Adjust grading.
            require_once($CFG->dirroot . '/lib/grade/grade_category.php');
            require_once($CFG->dirroot . '/lib/grade/grade_item.php');
            require_once($CFG->dirroot . '/lib/gradelib.php');
            $gc = \grade_category::fetch(array('courseid'=>$course->id));
            $gradeinfo = (object) array('id' => $gc->id, 'aggregation' => 11, 'aggregateonlygraded' => 1, 'courseid' => $course->id);
            \grade_category::set_properties($gc, $gradeinfo);
            \rebuild_course_cache($course->id);
            return;
            */

            // @todo fields licence and author are required when publishing the package.

            if (self::$debug) echo "=====> Course is #<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->id</a>\n";
            if (self::$debug) echo "=======> Timelastmodified $timelastmodified || $courserec->lasttimemodified < $timelastmodified\n";
            if (empty($timelastmodified) || $courserec->lasttimemodified < $timelastmodified) {
                $context = \context_course::instance($course->id);


                if (!empty($package['previewimage'])) {
                    $filerecord = (object) array(
                        'contextid' => $context->id,
                        'component' => 'course',
                        'filearea' => 'overviewfiles',
                        'itemid' => 0,
                    );

                    $curldata = $external;
                    $curldata->url = $package['previewimage'];
                    if (self::$debug) echo "=======> Loading course image from $curldata->url\n";

                    self::filearea_replace($curldata, $filerecord);
                }

                // Remove modules we do not reference anymore.
                if (self::$debug) echo "=======> Remove unreferenced and additional course modules\n";
                $knownitems = array_keys($ITEMS);
                list($insql, $inparams) = $DB->get_in_or_equal($knownitems);
                $inparams = array_merge(array($course->id, $packageid), $inparams);
                $sql = "SELECT id FROM {course_modules}
                            WHERE course=?
                                AND
                                (
                                    id NOT IN (
                                        SELECT cmid as id FROM {block_edupublisher_extitem}
                                            WHERE packageid=?
                                        )
                                    OR
                                    id $insql
                                )";

                $cms = $DB->get_records_sql($sql, $inparams);
                require_once($CFG->dirroot . '/course/modlib.php');
                foreach ($cms as $cm) {
                    if (self::$debug) echo "=========> Removing cmid $cm->id\n";
                    \course_delete_module($cm->id);
                }
                \rebuild_course_cache($course->id);


                // Ensure enough sections are in course.
                $xmlsections = self::flattened_element($package, 'usesections', 'usesection');
                $amount = count($xmlsections);
                if (self::$debug) echo "=======> Ensure that $amount sections are in course\n";
                \course_create_sections_if_missing($course, $amount);

                $sql = "SELECT s.section,s.* FROM {course_sections} s WHERE s.course=? ORDER BY s.section ASC";
                $dbsections = $DB->get_records_sql($sql, array($course->id));

                foreach ($xmlsections as $xmlnr => $xmlsection) {
                    $dbsectionnr = $xmlnr+1;
                    $externalsectionid = intval($xmlsection['@attributes']['reference']);

                    $XMLSECTION = $SECTIONS[$externalsectionid];
                    $DBSECTION = $dbsections[$dbsectionnr];

                    // Update section data.
                    $DBSECTION->name = $XMLSECTION['name'];

                    if (self::$debug) echo "=========> Update Section $DBSECTION->name (#$xmlnr) and id $DBSECTION->id\n";
                    \course_update_section($course, $DBSECTION, $DBSECTION);

                    if (!empty($XMLSECTION['previewimage'])) {
                        $filerecord = (object) array(
                            'contextid' => $context->id,
                            'component' => 'format_files',
                            'filearea' => 'tilephoto',
                            'filepath' => '/tilephoto',
                            'itemid' => $DBSECTION->id, // section id
                        );

                        $curldata = $external;
                        $curldata->url = $XMLSECTION['previewimage'];
                        if (self::$debug) echo "===========> Loading section image from $curldata->url\n";

                        self::filearea_replace($curldata, $filerecord);
                    }
                    $useitems = $XMLSECTION['useitem'];
                    if (empty($useitems)) {
                        continue;
                    } elseif(!is_array($useitems)) {
                        // only a single child.
                        $useitems = array($useitems);
                    }

                    $sql = "SELECT i.externalid,i.*
                                FROM {block_edupublisher_extitem} i
                                WHERE packageid=?";
                    $DBITEMS = $DB->get_records_sql($sql, array($packageid));
                    foreach ($useitems as $useitem) {
                        $reference = $useitem['@attributes']['reference'];
                        $XMLITEM = $ITEMS[$reference];
                        $DBITEM = @$DBITEMS[$reference];

                        $type = $XMLITEM['@attributes']['type'];


                        if (!empty($DBITEM->cmid)) {
                            // Item is known, but may have been deleted.
                            $extcm = \get_coursemodule_from_id($type, $DBITEM->cmid, 0, false, IGNORE_MISSING);
                            if (empty($extcm->id)) {
                                if (self::$debug) echo "=============> Item was known but is now removed\n";
                                $DB->delete_records('block_edupublisher_extitem', array('id' => $DBITEM->id));
                                unset($DBITEM);
                            }
                        }

                        $data = (object)$item;
                        $data->course = $course->id;
                        $data->section = $DBSECTION->id;
                        $data->name = $XMLITEM['name'];
                        switch ($type) {
                            case 'lti':
                                if (substr($XMLITEM['ltiurl'], 0, 8) == 'https://') {
                                    $data->securetoolurl = $XMLITEM['ltiurl'];
                                } else {
                                    $data->toolurl = $XMLITEM['ltiurl'];
                                }
                                $data->password = $XMLITEM['ltisecret'];
                            break;
                        }

                        $cmitem = \block_edupublisher\module_compiler::compile($type, $data, array());

                        if (empty($DBITEM->cmid)) {
                            // Item is actually new, we create it and store relation to DBITEM.
                            if (self::$debug) echo "===========> Create item $reference\n";

                            $module = \block_edupublisher\module_compiler::create($cmitem);
                            $extcm = \get_coursemodule_from_id($type, $module->coursemodule, 0, false, IGNORE_MISSING);
                            $DBITEM = (object) array(
                                'packageid' => $packageid,
                                'sectionid' => $DBSECTION->id,
                                'externalid' => $reference,
                                'cmid' => $extcm->id,
                            );
                            $DBITEM->id = $DB->insert_record('block_edupublisher_extitem', $DBITEM);
                        } else {
                            // Update item data.
                            if (self::$debug) echo "===========> Update item $reference as cmid $DBITEM->cmid\n";
                            $cmitem->id = $extcm->id;
                            $cmitem->coursemodule = $extcm->id;
                            require_once($CFG->dirroot . '/course/lib.php');
                            if ($cmitem->section != $extcm->section) {
                                // We have to set the old section here.
                                $cmitem->section = $extcm->section;
                                if (self::$debug) echo "=============> Move item $cmitem->id from $extcm->section to $cmitem->section\n";
                                \moveto_module($cmitem, $DBSECTION);
                            }
                            \update_module($cmitem);
                        }
                    }
                    // Rebuild cache after each section.
                    rebuild_course_cache($course->id);
                }

                // Remove unneeded sections.
                if (self::$debug) echo "=======> Remove unneeded sections\n";
                $sql = "SELECT section,id,name FROM {course_sections} WHERE course=? AND section>?";
                $removesections = $DB->get_records_sql($sql, array($course->id, count($SECTIONS)));
                foreach ($removesections as $removesection) {
                    if (self::$debug) echo "=========> Remove Section $removesection->name (#$removesection->section) and id $removesection->id\n";
                    \course_delete_section($course, $removesection, true);
                }
                // Rebuild cache after deleting sections.
                rebuild_course_cache($course->id);

                // Ensure package exists.
                require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
                include($CFG->dirroot . '/blocks/edupublisher/classes/channel_definition.php');
                $channels = array_keys($definition);
                $pubpackage = \block_edupublisher::get_package_by_courseid($course->id, IGNORE_MISSING);
                if (self::$debug) echo "=====> Loading pubpackage data for course $course->id\n";
                if (empty($pubpackage->id)) {
                    if (self::$debug) echo "=====> Nothing found - getting pubpackage data from course $course->id\n";
                    $pubpackage = \block_edupublisher::get_package_from_course($course->id);
                    // The course itself is the package!
                    $pubpackage->course = $pubpackage->sourcecourse;
                    $pubpackage = \block_edupublisher::store_package($pubpackage);
                }

                if (self::$debug) echo "=====> Loading pubpackage data from xml\n";
                // Translate certain xml fields.
                $pubpackage->default_publishas = 1;
                $pubpackage->commercial_publishas = 1;
                $pubpackage->commercial_publisher = $PUBLISHER->id;
                $pubpackage->commercial_published = $package['commercial_published'] = 1;
                $pubpackage->commercial_shoplink = $package['commercial_shoplink'] = '';
                $pubpackage->commercial_validation = $package['commercial_validation'] = 'external';

                $pubpackage->default_title = $package['name'];
                $pubpackage->default_summary = $package['summary'] = $package['name'];
                $pubpackage->default_authorname = $package['author'] = $PUBLISHER->name;
                $pubpackage->default_authormail = $package['mail'] = $PUBLISHER->mail;
                if (in_array($package['licence'], array_keys($definition['default']['licence']['options']))) {
                    $pubpackage->default_licence = $package['licence'];
                } else {
                    $pubpackage->default_licence = 'other';
                }
                if (!empty($package['previewimage'])) {
                    $filerecord = (object) array(
                        'contextid' => $context->id,
                        'component' => 'block_edupublisher',
                        'filearea' => 'default_image',
                        'itemid' => $pubpackage->id,
                    );

                    $curldata = $external;
                    $curldata->url = $package['previewimage'];
                    if (self::$debug) echo "=======> Loading course image from $curldata->url for pubpackage info\n";

                    $pubpackage->default_image = self::filearea_replace($curldata, $filerecord);
                }

                // Enter additional metadata provided from xml-file
                $required_missing = array();
                foreach ($channels as $channel) {
                    $fields = array_keys($definition[$channel]);
                    foreach ($fields as $field) {
                        if (!empty($package->{$channel . '_' . $field})) {
                            if (self::$debug) echo "=======> Set $channel_$field from xml\n";
                            $pubpackage->{$channel . '_' . $field} = $package[$channel . '_' . $field];
                        }
                        if ($channel == 'default' && !empty($package->{$field})) {
                            if (self::$debug) echo "=======> Set $channel_$field from xml\n";
                            $pubpackage->{$channel . '_' . $field} = $package[$field];
                        }
                        if (!empty($pubpackage->{$channel . '_publishas'}) && !empty($definition[$channel][$field]['required']) && empty($pubpackage->{$channel . '_' . $field})) {
                            $required_missing[] = $channel . '_' . $field;
                        }
                    }
                }

                \block_edupublisher::store_package($pubpackage);
                if (self::$debug) echo "=====> Store pubpackage\n";
                if (count($required_missing) > 0) {
                    if (self::$debug) echo "=====> WARNING: Missing data " . implode(", ", $required_missing) . "</strong>\n";
                }

            }

        }
    }

    /**
     * Fetch an url.
     * @param data object having url, authuser and authpwd.
     * @return the results.
     */
    public static function fetch_curl($data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $data->url);
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:84.0) Gecko/20100101 Firefox/84.0');
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        //curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        if (!empty($data->authuser) && !empty($data->authpwd)) {
            curl_setopt($ch, CURLOPT_USERPWD, $data->authuser . ":" . $data->authpwd);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close($ch);
        return $body;
    }
    /**
     * Clear a file area.
     * @params filerecord.
     */
    public static function filearea_clear($filerecord) {
        $fs = \get_file_storage();
        $files = $fs->get_area_files(
            $filerecord->contextid, $filerecord->component,
            $filerecord->filearea, $filerecord->itemid
        );
        foreach ($files as $f) {
            $f->delete();
        }
    }
    /**
     * Clear a file area.
     * @params curlinfo.
     * @params filerecord.
     */
    public static function filearea_replace($curlinfo, $filerecord) {
        $tmpfile = tmpfile();
        $md = stream_get_meta_data($tmpfile);
        $tmppath = $md['uri'];
        file_put_contents($tmppath, self::fetch_curl($curlinfo)); // fetch_curl supports basic auth!
        if (self::$debug) echo "=========> Loaded filesize " . filesize($tmppath) . " to $tmppath\n";

        if (filesize($tmppath) > 0) {
            // Clear file area.
            self::filearea_clear($filerecord);

            // Add new file.
            if (empty($filerecord->filepath)) {
                $filerecord->filepath = '/';
            }
            if (empty($filerecord->filename)) {
                $filename = basename($tmppath);
                $filesuffix = substr(str_replace('/', '.', $curlinfo->url), -4);
                if (substr($filesuffix, 0, 1) != '.') $filesuffix = '.' . $filesuffix;
                $filerecord->filename = $filename.$filesuffix;
            }
            $filerecord->timecreated = time();
            $filerecord->timemodified = time();
            echo "=========> Store image as $filename$filesuffix\n";
            $fs = get_file_storage();
            $fs->create_file_from_pathname($filerecord, $tmppath);
            $file = $fs->get_file($filerecord->contextid, $filerecord->component, $filerecord->filearea, $filerecord->itemid, $filerecord->filepath, $filerecord->filename);
            return \moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false)->__toString();
        }
    }
    /**
     * Get the children of an element.
     * SimpleXML does not make subchilds when only 1 child is present.
     */
    public static function flattened_element($element, $multi, $single) {
        if (!empty($element[$multi]) && !empty($element[$multi][$single]) && !empty($element[$multi][$single][0])) return $element[$multi][$single];
        if (!empty($element[$multi])) return $element[$multi];
        return $element;
    }
    /**
     * Shorten the courses fullname.
     */
    public static function shortenname($name) {
        return substr($name, 0, 254);
    }
}
