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

class channelexport extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('task:channelexport:title', 'block_edupublisher');
    }

    /**
     * Create an xml-file for each channel.
     */
    public function execute() {
        global $DB;

        $channels = \block_edupublisher\lib::channels();
        $filters = [
            'default' => ['default'],
            'eduthek' => ['default', 'eduthek', 'etapas'],
            'etapas' => ['default', 'etapas'],
            'eduthekneu' => ['default', 'etapas', 'eduthekneu'],
        ];
        $xmls = [];
        foreach ($filters as $filter => $chans) {
            $xmls[$filter] = new \SimpleXMLElement('<items />');
        }

        $sql = "SELECT id
                    FROM {block_edupublisher_packages} p
                    WHERE p.active=1
                    ORDER BY id ASC";

        $packageids = $DB->get_records_sql($sql, []);
        foreach ($packageids as $packageid) {
            $package = new \block_edupublisher\package($packageid->id, true);
            foreach ($filters as $filter => $chans) {
                if (!empty($package->get('publishas', $filter))) {
                    $package->as_xml($chans, $xmls[$filter]);
                }
            }
        }

        $fs = \get_file_storage();
        foreach ($xmls as $filter => $xml) {
            mtrace("== Create XML for $filter ==");
            $fileinfo = array(
                'contextid' => 1, // global context
                'component' => 'block_edupublisher',
                'filearea' => 'channelexport',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => "{$filter}.xml",
            );

            $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
            // Delete it if it exists
            if ($file) {
                $file->delete();
            }

            $fs->create_file_from_string($fileinfo, $xml->asXML());
            mtrace("===== [done]");
        }


    }
}
