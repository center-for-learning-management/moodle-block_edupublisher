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

require_once($CFG->dirroot . '/blocks/edupublisher/classes/channel_definition.php');
$_channels = array_keys($definition);
$channel = array();
foreach($_channels AS $channel) {
    $token = get_config('block_edupublisher', 'channelkey_' . $channel);
    if (empty($token)) continue;
    if (in_array($token, $tokens)) $channels[] = $channel;
}

if (count($channels) == 0) {
    block_edupublisher::print_app_header();
    echo 'No valid token';
    block_edupublisher::print_app_footer();
    die();
}
header('Content-type: application/xml');

$sql = 'SELECT p.id FROM {block_edupublisher_packages} p, {block_edupublisher_metadata} m WHERE p.id=m.package AND p.modified>? AND (1=0';
foreach($channels AS $channel) {
    $sql .= ' OR (p.channels LIKE "%,' . $channel. ',%" AND m.field = "' . $channel . '_active" AND m.content = "1")';
}
$sql .= ')';

// Default-Data is added to be included in output, but AFTER sql!
if (!in_array('default', $channels)) $channels[] = 'default';

//echo $sql;
$items = new SimpleXMLElement('<items />');
$packageids = $DB->get_records_sql($sql, array($modified));
foreach($packageids AS $packageid) {
    block_edupublisher::as_xml($packageid->id, $channels, $items);
}
echo $items->asXML();
?>
