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

class locallib {
    /**
     * Used to split the metadata table into separate tables.
     */
    public static function atomize_database(): bool {
        global $CFG, $DB;

        $metadatas = $DB->get_records('block_edupublisher_metadata', null, 'package ASC');
        $curpackage = (object) [
            'commercial' => (object) [],
            'default' => (object) [],
            'eduthek' => (object) [],
            'etapas' => (object) [],
            'exacomp' => []
        ];
        foreach ($metadatas as $md) {
            if (!empty($curpackage->package) && $md->package != $curpackage->package) {
                // default_schoollevel
                if (!empty($curpackage->default->schoollevel)) {
                    foreach ($curpackage->default->schoollevel as $schoollevel) {
                        $curpackage->default->{"schoollevel_$schoollevel"} = 1;
                    }
                    unset($curpackage->default->schoollevel);
                }

                // default_subjectarea
                if (!empty($curpackage->default->subjectarea)) {
                    foreach ($curpackage->default->subjectarea as $subjectarea) {
                        $curpackage->default->{"subjectarea_$subjectarea"} = 1;
                    }
                    unset($curpackage->default->subjectarea);
                }
                // default_tags
                if (!empty($curpackage->default->tags)) {
                    $curpackage->default->tags = implode("\n", $curpackage->default->tags);
                }

                // default_exacomp
                if (!empty($curpackage->default->exacompsourceids)) {
                    for ($i = 0; $i < count($curpackage->default->exacompsourceids); $i++) {
                        $curpackage->exacomp[] = (object) [
                            'datasource' => $curpackage->default->exacompdatasources[$i],
                            'sourceid' => $curpackage->default->exacompsourceids[$i],
                            'title' => $curpackage->default->exacomptitles[$i]
                        ];
                    }
                }
                unset($curpackage->default->exacompdatasources);
                unset($curpackage->default->exacompsourceids);
                unset($curpackage->default->exacomptitles);

                // eduthek
                if (!empty($curpackage->eduthek->educationallevel)) {
                    $curpackage->eduthek->educationallevel = implode("\n", $curpackage->eduthek->educationallevel);
                }
                if (!empty($curpackage->eduthek->schooltype)) {
                    $curpackage->eduthek->schooltype = implode("\n", $curpackage->eduthek->schooltype);
                }
                if (!empty($curpackage->eduthek->topic)) {
                    $curpackage->eduthek->topic = implode("\n", $curpackage->eduthek->topic);
                }
                if (!empty($curpackage->eduthek->type)) {
                    $curpackage->eduthek->type = implode("\n", $curpackage->eduthek->type);
                }

                // etapas_status_eval
                if (!empty($curpackage->etapas->schulstufe)) {
                    foreach ($curpackage->etapas->schulstufe as $schulstufe) {
                        $curpackage->etapas->{"schulstufe_$schulstufe"} = 1;
                    }
                    unset($curpackage->etapas->schulstufe);
                }

                if (!empty($curpackage->default->image)) {
                    $curpackage->default->image = str_replace($CFG->wwwroot, '', $curpackage->default->image);
                }

                $channels = [ 'commercial', 'default', 'eduthek', 'etapas' ];
                $tables = [ 'com', 'def', 'edu', 'eta' ];

                for ($i = 0; $i < count($channels); $i++) {
                    $chan = $channels[$i]; $tab = $tables[$i];
                    $chk = $DB->get_record("block_edupublisher_md_$tab", [ 'package' => $curpackage->package ]);
                    $curpackage->{$chan}->package = $curpackage->package;

                    if (!empty($chk->id)) {
                        $curpackage->{$chan}->id = $chk->id;
                        $DB->update_record("block_edupublisher_md_$tab", $curpackage->{$chan});
                    } else {
                        $DB->insert_record("block_edupublisher_md_$tab", $curpackage->{$chan});
                    }
                }

                foreach ($curpackage->exacomp as $exa) {
                    $para = [
                        'package' => $curpackage->package,
                        'datasource' => $exa->datasource,
                        'sourceid' => $exa->sourceid
                    ];
                    $chk = $DB->get_record('block_edupublisher_md_exa', $para);
                    if (!empty($chk->id)) {
                        $chk->title = $exa->title;
                        $DB->update_record('block_edupublisher_md_exa', $chk);
                    } else {
                        $DB->insert_record('block_edupublisher_md_exa', $para);
                    }
                }

                $curpackage = (object) [
                    'commercial' => (object) [],
                    'default' => (object) [],
                    'eduthek' => (object) [],
                    'etapas' => (object) [],
                    'exacomp' => []
                ];
            }

            $curpackage->package = $md->package;
            $field = explode('_', $md->field);
            $channel = $field[0];
            $param = $field[1];
            if (empty($curpackage->{$channel})) {
                $curpackage->{$channel} = (object) [ 'package' => $curpackage->packageid ];
            }
            if (count($field) == 3) {
                // This is a multiple-field.
                if (strpos($field[2], ':') > 0) {
                    continue;
                } else {
                    if (empty($curpackage->{$channel}->{$param})) {
                        $curpackage->{$channel}->{$param} = [];
                    }
                    $curpackage->{$channel}->{$param}[] = $md->content;
                }
            } else {
                $curpackage->{$channel}->{$param} = $md->content;
            }
        }
        return true;
    }
}
