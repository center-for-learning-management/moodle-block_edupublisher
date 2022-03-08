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
 * @copyright  2020 Center for Learning Management (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;

defined('MOODLE_INTERNAL') || die;

/**
 * List all subjectareas in an localized, alphabetically sorted array.
 * @param selectedarea mark a subjectarea as selected.
 **/
function get_subjectareas_sorted($selectedarea = "") {
    global $CFG;
    $definition = \block_edupublisher\lib::get_channel_definition();
    $locs = array();
    $loc_key = array();

    foreach ($definition['default']['subjectarea']['options'] AS $key => $localized) {
        $locs[] = $localized;
        $loc_key[$localized] = $key;
    }
    sort($locs);
    $sorted = array();
    foreach ($locs AS $loc) {
        $sorted[] = array(
            'key' => $loc_key[$loc],
            'name' => $loc,
            'isselected' => ($loc_key[$loc] == $selectedarea)
        );
    }
    return $sorted;
}

/**
 * List all schoollevels in an localized, alphabetically sorted array.
 * @param selectedarea mark a schoollevel as selected.
 **/
function get_schoollevels_sorted($selectedarea = "") {
    global $CFG;

    $definition = \block_edupublisher\lib::get_channel_definition();
    $locs = array();
    $loc_key = array();

    foreach ($definition['default']['schoollevel']['options'] AS $key => $localized) {
        $locs[] = $localized;
        $loc_key[$localized] = $key;
    }
    sort($locs);
    $sorted = array();
    foreach ($locs AS $loc) {
        $sorted[] = array(
            'key' => $loc_key[$loc],
            'name' => $loc,
            'isselected' => ($loc_key[$loc] == $selectedarea)
        );
    }
    return $sorted;
}
