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

namespace block_edupublisher;

defined('MOODLE_INTERNAL') || die;

class output {

    public static function render_package_details(package $package, string $type) {
        global $OUTPUT;

        // $data = $package->get_flattened();

        // return $OUTPUT->render_from_template('block_edupublisher/package_details', $data);
        return $OUTPUT->render_from_template('block_edupublisher/package_details', new template_resolver($package, [
            'competencylist' => static::render_competencylist($package, false),
            'returnurl' => urlencode((new \moodle_url(qualified_me()))->out_as_local_url(false)),
            // 'display_package_detail_page' => $type === 'package_detail_page',
            // 'display_course_content' => $type === 'course_content',
            // 'can_see_evaluation' => $package->get('can_see_evaluation') ?: false,
            // 'can_import' => $package->get('can_import') ?: false,
            // 'allow_subcourses' => $package->get('allow_subcourses') ?: false,
            // 'can_unenrol' => $package->get('can_unenrol') ?: false,
        ]));
    }


    // public static function get_course_summary(package $package, bool $for_package_detail_page = false): string {
    //     $schoollevels = $package->get('schoollevels', 'default');
    //     if ($schoollevels) {
    //         $schoollevels = array_map(fn($schoollevel) => $channels['default']['schoollevels']['options'][$schoollevel],
    //             is_array($schoollevels) ? $schoollevels : explode(package::ARRAY_DELIMITER, $schoollevels));
    //         $schoollevels = implode(', ', $schoollevels);
    //         $summary .= '<p><b>Schulstufen:</b> ' . $schoollevels . '</p>';
    //     }
    //
    //     $subjectareas = $package->get('subjectareas', 'default');
    //     if ($subjectareas) {
    //         $subjectareas = array_map(fn($subjectarea) => $channels['default']['subjectareas']['options'][$subjectarea],
    //             is_array($subjectareas) ? $subjectareas : explode(package::ARRAY_DELIMITER, $subjectareas));
    //         $subjectareas = implode(', ', $subjectareas);
    //         $summary .= '<p><b>Gegenstandsbereiche:</b> ' . $subjectareas . '</p>';
    //     }
    //
    //     $summary .= static::render_competencylist($package, false);
    //
    //     return $summary;
    // }

    public static function render_competencylist(package $package, bool $from_edit_form): string {
        $competenciesByParent = $package->exacompetencies();

        ob_start();

        if ($competenciesByParent) {
            if (!$from_edit_form) {
                echo '<h3 style="font-size: 130%; margin-top: 20px;">Kompetenzen:</h3>';
            }

            foreach ($competenciesByParent as $parentName => $competencies) {
                ?>
                <div style="font-weight: bold; margin: 12px 0 4px 0"><?= $parentName ?>:</div>
                <ul>
                    <?= join('', array_map(fn($k) => "<li>{$k}</li>", $competencies)) ?>
                </ul>
                <?php
            }
        } elseif ($from_edit_form) {
            echo get_string('eduthekneu_kompetenzen_help', 'block_edupublisher');
        }

        return ob_get_clean();
    }
}
