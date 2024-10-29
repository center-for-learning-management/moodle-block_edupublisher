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

use block_edupublisher\output;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

class block_edupublisher extends block_base {
    /**
     * Gets a publisher from database.
     * @param publisherid
     */
    public static function get_publisher($publisherid) {
        global $DB, $USER;
        $publisher = $DB->get_record('block_edupublisher_pub', array('id' => $publisherid), '*', IGNORE_MISSING);
        if (empty($publisher->id))
            return null;
        $is_coworker = $DB->get_record('block_edupublisher_pub_user', array('publisherid' => $publisherid, 'userid' => $USER->id));
        $publisher->is_coworker = (!empty($is_coworker->userid) && $is_coworker->userid == $USER->id);
        // Load Logo of publisher.
        $fs = get_file_storage();
        $context = context_system::instance();
        $files = $fs->get_area_files($context->id, 'block_edupublisher', 'publisher_logo', $publisherid);
        foreach ($files as $f) {
            if (empty(str_replace('.', '', $f->get_filename())))
                continue;
            $publisher->publisher_logo = '' . moodle_url::make_pluginfile_url($f->get_contextid(), $f->get_component(), $f->get_filearea(), $f->get_itemid(), $f->get_filepath(), $f->get_filename(), false);
            break;
        }
        return $publisher;
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
        $this->content = (object)array(
            'text' => '',
            'footer' => '',
        );

        // 1. in a package-course: show author
        // 2. in a course + trainer permission: show publish link and list packages
        // 3. show nothing

        $context = \context_course::instance($COURSE->id);
        $canedit = has_capability('moodle/course:update', $context);

        $package = $DB->get_record('block_edupublisher_packages', array('course' => $COURSE->id), '*', IGNORE_MULTIPLE);

        if ($package) {
            $package = new \block_edupublisher\package($package->id, true);

            if ($package->get('licence', 'default') == 'other') {
                $package->set(get_string('default_licenceother', 'block_edupublisher'), 'licence', 'default');
            }
            if ($package->get('subtype', 'etapas') == 'etapa' && has_capability('block/edupublisher:canseeevaluation', \context_system::instance())) {
                $package->set_v2('can_see_evaluation', true);
            }

            // Show use package-button
            $courses = \block_edupublisher\lib::get_courses(null, 'moodle/course:update');
            if (count(array_keys($courses)) > 0) {
                $package->set(true, 'can_import');
                $package->set($allowsubcourses = \get_config('block_edupublisher', 'allowsubcourses') ? 1 : 0, 'allow_subcourses');
            }
            $package->set((is_enrolled($context, null, 'block/edupublisher:canselfenrol')) ? 1 : 0, 'can_unenrol');

            if (!empty($package->get('active', 'etapas')) && !empty($package->get('subtype', 'etapas'))) {
                $package->set(str_replace(array(' ', '.'), '', $package->etapas_subtype), 'graphic', 'etapas');
            }

            if ($this->is_displayed_in_course_content()) {
                // inside a course

                // hack: also add the styles
                // $PAGE->requires->css does not work here, because header is already printed
                $this->content->text .= '<div class="ui-edupublisher">' .
                    output::render_package_details($package, 'course_content') .
                    '</div>';
                $this->title = ''; // get_string('summary', 'block_edupublisher');
            } else {

                $package->set(\block_edupublisher\lib::show_star_rating(), 'show_star_rating');

                $this->content->text .= $OUTPUT->render_from_template('block_edupublisher/block_inpackage', $package->get_flattened());
            }
        } elseif ($canedit) {
            // $cache = \cache::make('block_edupublisher', 'publish');
            // $pendingpublication = $cache->get("pending_publication_$COURSE->id");
            // if (empty($pendingpublication)) {
            //     $cache->set("pending_publication_$COURSE->id", -1);
            //     $sql = "SELECT *
            //                 FROM {block_edupublisher_publish}
            //                 WHERE sourcecourseid = ?
            //                     OR targetcourseid = ?";
            //     $pendingpublications = $DB->get_records_sql($sql, [$COURSE->id, $COURSE->id]);
            //     foreach ($pendingpublications as $pendingpublication) {
            //         $pendingpublication = $pendingpublication->sourcecourseid;
            //         $cache->set("pending_publication_$COURSE->id", $pendingpublication);
            //         break;
            //     }
            // }

            $params = (object)[
                'wwwroot' => $CFG->wwwroot,
                'courseid' => $COURSE->id,
                'packages' => array_values($DB->get_records_sql('SELECT * FROM {block_edupublisher_packages} WHERE sourcecourse=? AND (active=1 OR userid=?)', array($COURSE->id, $USER->id))),
                // 'pendingpublication' => (intval($pendingpublication) == -1) ? 0 : $pendingpublication,
                'uses' => array_values($DB->get_records_sql('SELECT DISTINCT package.id, package.title FROM {block_edupublisher_packages} package JOIN {block_edupublisher_uses} uses ON package.id=uses.package AND uses.targetcourse=?', array($COURSE->id))),
            ];
            $params->haspackages = !!count($params->packages);
            $params->hasuses = !!count($params->uses);

            $this->content->text .= $OUTPUT->render_from_template('block_edupublisher/block_canedit', $params);
        }

        return $this->content;
    }

    public function is_displayed_in_course_content() {
        global $COURSE;

        return isset($this->instance->region) && $this->instance->region == 'content-upper' && $this->context->get_parent_context() instanceof \context_course && $COURSE->id > 1;
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
