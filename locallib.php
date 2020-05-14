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
    require_once($CFG->dirroot . '/blocks/edupublisher/classes/channel_definition.php');
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

function get_danubeai_recommendations($addid = 0) {
    $danubeai_apikey = get_config('block_edupublisher', 'danubeai_apikey');
    if (!empty($danubeai_apikey)) {
        $cache = \cache::make('block_edupublisher', 'packagepath');
        $path = explode(',', $cache->get('path'));
        if (!empty($addid)) {
            $path[] = $addid;
            $cache->set('path', implode(',', $path));
        }

        $pathdata = array();
        foreach ($path AS $p) {
            $pathdata[] = array('page' => $p);
        }
        $data = array(
            'query' => 'mutation ($data: RecommendationInputData!) { danubeRecommendation(data: $data) { correlatedData } }',
            'variables' => array(
                'data' => array('data' => json_encode($pathdata, JSON_NUMERIC_CHECK)),
                'n' => 3,
            ),
        );

        $url = "https://api.danube.ai/graphql";
        $content = json_encode($data);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json", "apitoken: Bearer $danubeai_apikey"));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $response = json_decode($json_response, true);
    }
}
