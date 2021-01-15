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
 * @copyright  2020 Center for Learningmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher\task;

defined('MOODLE_INTERNAL') || die;

class block_edupublisher_externalsources extends \core\task\scheduled_task {
    private static $debug = false;

    public function get_name() {
        // Shown in admin screens.
        return get_string('task:externalsources:title', 'block_edupublisher');
    }

    public function execute() {
        return;
        global $CFG,$DB;

        self::$debug = true; //($CFG->debug == 32767); // Developer debugging

        $CATEGORY = intval(\get_config('block_edupublisher', 'category'));
        if (empty($CATEGORY)) {
            echo "<strong>eduPublisher has not configured a valid category</strong>\n";
            return;
        }

        $externals = $DB->get_records('block_edupublisher_externals');
        $parser = new \core_xml_parser();
        foreach ($externals as $external) {
            if (self::$debug) echo "=> Parsing $external->url\n";
            if (substr($external->url, 0, 4) == 'http') {
                $xmlstr = self::fetch_curl($external);
            } else {
                // load the local file.
                $xmlstr = file_get_contents($external->url);
            }
            if (empty($xmlstr)) {
                if (self::$debug) echo "=> Empty response\n";
                continue;
            }

            $xml = new \SimpleXMLElement($xmlstr);
            $array = json_decode(json_encode($xml), TRUE);

            if (empty($array['packages'])) {
                if (self::$debug) echo "=> No packages in xml\n";
                continue;
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
                $timelastmodified = date_parse($package['@attributes']['changed']);
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
                    $course->summary = $package['name'];
                    $DB->update_record('course', $course);
                    $DB->set_field('block_edupublisher_extpack', 'lasttimemodified', time(), array('id' => $courserec->id));
                }

                // @todo fields licence and author are required when publishing the package.

                if (self::$debug) echo "=====> Course is #<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->id</a>\n";
                if (empty($timelastmodified) || $courserec->lasttimemodified < $timelastmodified) {
                    $context = \context_course::instance($course->id);
                    if (!empty($package->previewimage)) {
                        $tmpfile = tmpfile();
                        file_put_contents($tmpfile, file_get_contents($package->previewimage));
                        if (filesize($tmpfile) > 0) {
                            $filename = basename($tmpfile);
                            $fs = get_file_storage();
                            $file_record = array('contextid' => $context->id, 'component' => 'course', 'filearea' => 'overviewfiles',
                                     'itemid' => 0, 'filepath'=>'/', 'filename' => $filename,
                                     'timecreated' => time(), 'timemodified' => time());
                            $fs->create_file_from_pathname($file_record, $tmpfile);
                        }
                    }
                    // Check if all sections exists
                    //print_r($package);

                    $_usesections =self::flattened_element($package, 'usesections', 'usesection');
                    if (count($_usesections) > 0) {
                        $amount = count($_usesections);
                        // Remove all unmanaged sections (except the first one)
                        if (self::$debug) echo "=======> Remove unmanaged sections\n";
                        $sql = "SELECT id,section FROM {course_sections}
                                    WHERE course=?
                                        AND section > 0
                                        AND id NOT IN (
                                            SELECT coursesection as id FROM {block_edupublisher_extsect}
                                                WHERE packageid=?
                                        )";
                        $removesections = $DB->get_records_sql($sql, array($course->id, $packageid));
                        require_once($CFG->dirroot . '/course/lib.php');
                        foreach ($removesections as $removesection) {
                            \course_delete_section($course, $removesection);
                        }
                        // Re-number sections
                        $secs = array_values($DB->get_records('course_sections', array('course' => $course->id)));
                        foreach ($secs as $a => $sec) {
                            $DB->set_field('course_sections', 'section', $a, array('id' => $sec->id));
                        }
                        rebuild_course_cache($course->id);
                        for ($a = count($secs); $a <= $amount; $a++) {
                            if (self::$debug) echo "=======> Create section at position $a\n";
                            \course_create_section($course, $a);
                        }

                        rebuild_course_cache($course->id);

                        $DB->execute('UPDATE {course_sections} SET section=section+? WHERE course=?', array($amount+2, $course->id));

                        $nr = 0;
                        foreach ($_usesections as $usesection) {
                            $nr++;
                            $usesectionid = intval($usesection['@attributes']['reference']);
                            $extsection = $DB->get_record('block_edupublisher_extsect', array('packageid' => $packageid, 'externalid' => $usesectionid));
                            if (empty($extsection->id)) {
                                // get next unused section
                                $sql = "SELECT id FROM {course_sections}
                                            WHERE course=?
                                                AND id NOT IN(
                                                    SELECT coursesection as id FROM {block_edupublisher_extsect}
                                                        WHERE packageid=?
                                                )
                                            LIMIT 0,1";
                                $nextsec = $DB->get_record_sql($sql, array($course->id, $packageid));
                                if (empty($nextsec->id)) {
                                    throw new \Exception('Not enough sections to handle this');
                                }
                                $extsection = (object) array(
                                    'packageid' => $packageid,
                                    'externalid' => $usesectionid,
                                    'coursesection' => $nextsec->id,
                                );
                                $extsection->id = $DB->insert_record('block_edupublisher_extsect', $extsection);
                            }
                            echo ">>> SET SECTION TO $nr FOR $extsection->coursesection\n";
                            $DB->set_field('course_sections', 'section', $nr, array('course' => $course->id, 'id' => $extsection->coursesection));
                            if (!empty($SECTIONS[$usesectionid]) && !empty($SECTIONS[$usesectionid]['name'])) {
                                $DB->set_field('course_sections', 'name', $SECTIONS[$usesectionid]['name'], array('course' => $course->id, 'id' => $extsection->coursesection));
                            }
                        }
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

                    // Create missing items in first section or update existing items.
                    foreach ($ITEMS as &$item) {
                        $itemid = $item['@attributes']['id'];
                        $type = $item['@attributes']['type'];
                        $extitem = $DB->get_record('block_edupublisher_extitem', array('packageid' => $packageid, 'externalid' => $itemid));
                        if (empty($extitem->id)) {
                            $extitem = (object)array(
                                'packageid' => $packageid,
                                'sectionid' => 0,
                                'externalid' => $itemid,
                                'cmid' => 0,
                            );
                            $extitem->id = $DB->insert_record('block_edupublisher_extitem', $extitem);
                        }
                        if ($extitem->cmid > 0) {
                            $extcm = $DB->get_record('course_modules', array('id' => $extitem->cmid), '*', IGNORE_MISSING);
                            if (empty($extcm->id)) {
                                // The course module has been deleted - we have to create it again.
                                $extcm = (object) array();
                            }
                        } else {
                            $extcm = (object) array();
                        }

                        if (empty($extcm->id)) {
                            $data = (object)$item;
                            $data->course = $course->id;
                            $data->section = 0;
                            switch ($type) {
                                case 'lti':
                                    if (substr($item['ltiurl'], 0, 8) == 'https://') {
                                        $data->securetoolurl = $item['ltiurl'];
                                    } else {
                                        $data->toolurl = $item['ltiurl'];
                                    }
                                    $data->password = $item['ltisecret'];
                                break;
                            }

                            $cmitem = \block_edupublisher\module_compiler::compile($type, $data, array());
                            if (self::$debug) echo "=========> Creating a $type for externalid $itemid\n";
                            $module = \block_edupublisher\module_compiler::create($cmitem);
                            $extcm = $DB->get_record('course_modules', array('id' => $module->coursemodule), '*', IGNORE_MISSING);
                            $DB->set_field('block_edupublisher_extitem', 'cmid', $extcm->id, array('id' => $extitem->id));
                        } else {
                            // Update the course module.
                            if (self::$debug) echo "=========> Update as $itemid a $type with cmid $extcm->id\n";
                            require_once($CFG->dirroot . '/course/lib.php');
                            //\update_module($cm);
                        }
                        $item['cmid'] = $extcm->id;
                    }

                    // Make ordering of items according to xml.
                    if (self::$debug) echo "=======> Order modules in sections\n";
                    $nr = 0;
                    $allcmids = array();
                    foreach ($SECTIONS as $section) {
                        $nr++;
                        if (empty($section['useitem']) || count($section['useitem']) == 0) continue;
                        if (self::$debug) echo "=========> Setting sequence for course $course->id for section $nr\n";
                        $sequence = array();
                        foreach ($section['useitem'] as $useitem) {
                            $reference = $useitem['@attributes']['reference'];
                            $item = $ITEMS[$reference];
                            if (!empty($item['cmid'])) {
                                \course_add_cm_to_section($course->id, $item['cmid'], $nr);

                                //$sequence[] = $item['cmid'];
                                //$allcmids = $item['cmid'];
                            }
                        }
                        //$sequence = implode(',', $sequence);
                        //if (self::$debug) echo "===========> Sequence is $sequence\n";
                        //$DB->set_field('course_sections', 'sequence', $sequence, array('course' => $course->id, 'section' => $nr));
                    }

                    rebuild_course_cache($course->id);
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
        curl_setopt($ch,CURLOPT_URL,urlencode($data->url));
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:84.0) Gecko/20100101 Firefox/84.0');
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        if (!empty($data->authuser) && !empty($data->authpwd)) {
            curl_setopt($ch, CURLOPT_USERPWD, $data->authuser . ":" . $data->authpwd);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $str = curl_exec($ch);
        curl_close($ch);
        return $str;
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
