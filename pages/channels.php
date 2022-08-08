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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');

$tokens = explode(',', optional_param('tokens', '',PARAM_TEXT));
$modified = optional_param('modified', 0, PARAM_INT);

$_channels = \block_edupublisher\lib::channels();
$channel = array();
foreach($_channels AS $channel) {
    $token = get_config('block_edupublisher', 'channelkey_' . $channel);
    if (empty($token)) continue;
    if (in_array($token, $tokens)) {
        $channels[] = $channel;
    }
}

if (count($channels) == 0) {
    echo $OUTPUT->header();
    echo 'No valid token';
    echo $OUTPUT->footer();
    die();
}


if (empty($modified)) {
    if (count($channels) == 1) {
        $channel = $channels[0];
        $fs = get_file_storage();
        $fileinfo = array(
            'contextid' => 1, // global context
            'component' => 'block_edupublisher',
            'filearea' => 'channelexport',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => "{$channel}.xml"
        );
        $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                              $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
        if ($file) {
            header('Content-type: application/xml');
            echo $file->get_content();
        } else {
            throw new \moodle_exception('exception:channelexport:notyetexported', 'block_edupublisher');
        }
    } else {
        throw new \moodle_exception('exception:channelexport:multiplechannels', 'block_edupublisher');
    }
} else {
    header('Content-type: application/xml');
    $sql = "SELECT id
                FROM {block_edupublisher_packages}
                WHERE modified > ?";

    $items = new SimpleXMLElement('<items />');
    $packageids = $DB->get_records_sql($sql, array($modified));
    foreach($packageids AS $packageid) {
        $package = new \block_edupublisher\package($packageid->id, true);
        $package->as_xml($channels, $items);
    }

    echo $items->asXML();
}
