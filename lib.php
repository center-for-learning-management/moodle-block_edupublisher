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

// Inject the self-enrol-button
function block_edupublisher_before_standard_html_head() {
    global $CFG, $DB, $PAGE, $USER;

    if (strpos($_SERVER["SCRIPT_FILENAME"], '/course/view.php') > 0) {
        // Determine if we are within an edupublisher-package
        $courseid = optional_param('id', 0, PARAM_INT);
        if (!empty($courseid)) {
            $package = $DB->get_record('block_edupublisher_packages', array('course' => $courseid));
            if (!empty($package->id)) {
                $context = context_course::instance($courseid);
                $allowguests = get_config('block_edupublihser', 'allowguests');
                if (!is_enrolled($context) && (empty($allowguests) || has_capability('block/edupublisher:canselfenrol', $context))) {
                    $PAGE->requires->js_call_amd('block_edupublisher/main', 'injectEnrolButton', array('courseid' => $courseid, 'isguestuser' => isguestuser($USER)));
                }

                require_once($CFG->dirroot . '/blocks/edupublisher/locallib.php');
                \block_edupublisher\lib::log_user_visit($package->id, 'viewed');
            }
        }
    }

    return "";
}

/**
 * Serve the files from the MYPLUGIN file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function block_edupublisher_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG;
    require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
    $definition = block_edupublisher::get_channel_definition();
    $areas = array('publisher_logo');
    $channels = array_keys($definition);
    foreach($channels AS $channel) {
        $fields = array_keys($definition[$channel]);
        foreach($fields AS $field) {
            if ($definition[$channel][$field]['type'] == 'filemanager') $areas[] = $channel . '_' . $field;
        }
    }

    if (in_array($filearea, $areas)) {
        $forcedownload = false;
        $options['embed'] = true;
    }
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    $contexts = array(CONTEXT_COURSE, CONTEXT_BLOCK, CONTEXT_SYSTEM);
    if (!in_array($context->contextlevel, $contexts)) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if (!in_array($filearea, $areas)) {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    // We do not need that (at least for backgrounds)
    // require_login($course, true, $cm);

    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    // We do not need that (at least for backgrounds)
    /*
    if (!has_capability('mod/MYPLUGIN:view', $context)) {
        return false;
    }
    */

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'block_edupublisher', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }
    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    // From Moodle 2.3, use send_stored_file instead.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
