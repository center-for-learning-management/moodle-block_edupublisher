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
 * @copyright  2020 Center for Learningmangement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;

use cache;

class hook_callbacks {
    public static function before_standard_head_html_generation($hook): void {
        global $CFG, $DB, $PAGE, $USER;

        // Inject the self-enrol-button
        if (strpos($_SERVER["SCRIPT_FILENAME"], '/course/view.php') > 0) {
            // Determine if we are within an edupublisher-package
            $courseid = optional_param('id', 0, PARAM_INT);
            if (!empty($courseid)) {
                $cache = cache::make('block_edupublisher', 'ispackage');
                $packageid = $cache->get('package-course-' . $courseid);
                if (true || empty($packageid)) {
                    $package = $DB->get_record('block_edupublisher_packages', array('course' => $courseid));
                    if (!empty($package->id)) {
                        $packageid = $package->id;
                        $cache->set('package-course-' . $courseid, $packageid);
                        $cache->set('package-active-' . $courseid, $package->active);
                    } else {
                        $cache->set('package-course-' . $courseid, -1);
                    }
                }
                if (!empty($packageid) && $packageid != -1) {
                    $packageactive = $cache->get('package-active-' . $courseid);
                    $context = \context_course::instance($courseid);
                    $allowguests = get_config('block_edupublisher', 'allowguests');
                    if (!empty($packageactive) && !is_enrolled($context) && (empty($allowguests) || isguestuser($USER) || has_capability('block/edupublisher:canselfenrol', $context))) {
                        $PAGE->requires->js_call_amd('block_edupublisher/main', 'injectEnrolButton', array('courseid' => $courseid, 'isguestuser' => isguestuser($USER)));
                    }

                    require_once($CFG->dirroot . '/blocks/edupublisher/locallib.php');
                    \block_edupublisher\lib::log_user_visit($packageid, 'viewed');
                }
            }
        }
    }
}
