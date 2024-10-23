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
 * @copyright  2020 Center for Learningmanagement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;

defined('MOODLE_INTERNAL') || die;

class api {
    // public static function get_course_summary(int $courseid): ?string {
    //     global $PAGE;
    //
    //     $package = \block_edupublisher\lib::get_package_by_courseid($courseid, IGNORE_MISSING, false);
    //
    //     if (!$package) {
    //         return null;
    //     }
    //
    //     // hack: also add the styles
    //     // $PAGE->requires->css does not work here, because header is already printed
    //     $styles = '<style> ' . file_get_contents(__DIR__ . '/../style/course_summary.css') . ' </style>';
    //
    //     return $styles . output::render_package_details($package);
    // }
}
