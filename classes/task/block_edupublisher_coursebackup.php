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

class block_edupublisher_coursebackup extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('task:coursebackup:title', 'block_edupublisher');
    }

    public function execute() {
        global $CFG, $DB;

        $admin = \get_admin();
        if (!$admin) {
            \mtrace("Error: No admin account was found!");
            die;
        }
        $dir = \block_edupublisher\lib::get_tempdir();
        if (!file_exists($dir) || !is_dir($dir) || !is_writable($dir)) {
            \mtrace("Destination directory does not exists or not writable.");
            die;
        }

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');

        $sql = "SELECT course
                    FROM {block_edupublisher_packages}
                    WHERE backuped<modified
                        AND deleted=0";
        $packages = $DB->get_records_sql($sql, array());
        foreach ($packages as $package) {
            $courseid = $package->course;
            $course = $DB->get_record('course', array('id' => $courseid), '*', IGNORE_MISSING);
            if (empty($course->id)) {
                \mtrace("ERROR: COURSE #$courseid DOES NOT EXIST");
                continue;
            }
            $targetfilename = "coursebackup.mbz";
            \mtrace("Backing up #$course->id ($course->fullname) to $targetfilename");

            $bc = new \backup_controller(
                \backup::TYPE_1COURSE, $course->id, \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_YES, \backup::MODE_GENERAL, $admin->id
            );
            $format = $bc->get_format();
            $type = $bc->get_type();
            $id = $bc->get_id();

            $bc->get_plan()->get_setting('users')->set_value(0);
            $users = $bc->get_plan()->get_setting('users')->get_value();
            $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
            //$filename = \backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
            //echo "Filename $filename";die();
            //$bc->get_plan()->get_setting('filename')->set_value($filename);
            $bc->get_plan()->get_setting('filename')->set_value($targetfilename);

            $bc->finish_ui();
            $bc->execute_plan();
            $results = $bc->get_results();
            $file = $results['backup_destination'];

            $ctx = \context_course::instance($course->id);
            $fs = \get_file_storage();
            $fr = array('contextid' => $ctx->id, 'component' => 'block_edupublisher', 'filearea' => 'coursebackup',
                'itemid' => 0, 'filepath' => '/', 'filename' => $targetfilename,
                'timecreated' => time(), 'timemodified' => time(),
            );

            $testfile = $fs->get_file($fr['contextid'], $fr['component'], $fr['filearea'], $fr['itemid'], $fr['filepath'], $fr['filename']);
            if ($testfile) {
                $testfile->delete();
            }
            $fs->create_file_from_storedfile($fr, $file);

            $file->delete();
            $DB->set_field('block_edupublisher_packages', 'backuped', time(), array('course' => $course->id));
            \mtrace("Stored file successfully.");
        }
    }
}
