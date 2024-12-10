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

$user_token = required_param('token', PARAM_TEXT);
$modified = optional_param('modified', 0, PARAM_INT);

$_channels = \block_edupublisher\lib::channels();
$channel = array();
$channels = [];

$allow_access = false;
foreach ($_channels as $channel) {
    $token = get_config('block_edupublisher', 'channelkey_' . $channel);
    if (empty($token)) {
        continue;
    }
    if ($token == $user_token) {
        $allow_access = true;
        break;
    }
}

if (!$allow_access) {
    header("HTTP/1.0 401 Unauthorized");
    $data = [
        'type' => 'error',
        'error' => 'invalid token',
    ];

    echo json_encode($data, JSON_UNESCAPED_SLASHES
        | (optional_param('pretty', false, PARAM_BOOL) ? JSON_PRETTY_PRINT : 0));

    exit;
}

$sql = "SELECT id
                FROM {block_edupublisher_packages} p
                WHERE modified > ?";
$packageids = $DB->get_fieldset_sql($sql, array($modified));

// auch inaktivte und gelöschte mitübertragen, damit sie bei den anderen System gelöscht werden können

$items = [];
foreach ($packageids as $packageid) {
    $package = new \block_edupublisher\package($packageid, true);

    $base_data = [
        'id' => (int)$packageid,
        // 'created' => $package->get('created'),
        'modified' => (int)$package->get('modified'),
        'deleted' => (bool)$package->get('deleted'),
        'published' => (bool)$package->get('active'),
        'title' => $package->title,
        // 'title' => $package->get('title', 'default'),
        // ist das gleiche wie default_title
        // nur bei gelöschtne paketen gibts keine channels mehr!
    ];

    if ($package->get('deleted')) {
        $items[] = $base_data;
        continue;
    }

    $competency_groups = [];
    if ($competenciesByParent = $package->exacompetencies(true)) {
        foreach ($competenciesByParent as $parentName => $competencies) {
            $competency_groups[] = [
                'name' => $parentName,
                'competencies' => $competencies,
            ];
        }
    }

    $items[] = [
        ...$base_data,

        // courseid wird für die Verlinkung verwendet
        'courseid' => (int)$package->courseid,
        'is_etapa' => (bool)$package->get('published', 'etapas'),
        'summary' => $package->get('summary', 'default'),
        'image_url' => ($image_url = $package->get_preview_image_url()) ? $image_url->out(false) : null,

        'authorname' => $package->get('authorname', 'default'),
        'schoollevels' => $package->get('schoollevels', 'default'),
        'subjectareas' => $package->get('subjectareas', 'default'),
        'tags' => $package->get('tags', 'default'),

        // TODO: auf license refactoren!
        'license' => $package->get('licence', 'default'),

        'contenttypes' => $package->get('contenttypes', 'eduthekneu'),
        'purposes' => $package->get('purposes', 'eduthekneu'),

        // 'rating' => (int) $package->get('rating', 'default'),
        'ratingaverage' => (float)$package->get('ratingaverage', 'default'),
        'ratingcount' => (int)$package->get('ratingcount', 'default'),

        'competency_groups' => $competency_groups,
    ];
}

header("Content-Type: application/json");

$data = [
    'type' => 'success',
    'data' => [
        'items' => $items,
    ],
];

echo json_encode($data, JSON_UNESCAPED_SLASHES
    | (optional_param('pretty', false, PARAM_BOOL) ? JSON_PRETTY_PRINT : 0));
