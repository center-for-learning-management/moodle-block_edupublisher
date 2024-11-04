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

class lib {
    /**
     * Ensures that within a context an instance of block_edupublisher exists.
     * @param
     **/
    public static function add_to_context($context, $region = null) {
        global $DB;
        $blocks = $DB->get_records('block_instances', array('blockname' => 'edupublisher', 'parentcontextid' => $context->id));
        if (!$blocks) {
            // Create edupublisher-block in targetcourse.
            $blockdata = (object)array(
                'blockname' => 'edupublisher',
                'parentcontextid' => $context->id,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'course-view-*',
                'defaultregion' => $region ?: 'side-post',
                'defaultweight' => -10,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time(),
            );
            $DB->insert_record('block_instances', $blockdata);
        } elseif ($region) {
            // move block to correct region if none exists yet
            $hasBlockInRegion = array_filter($blocks, fn($block) => $block->defaultregion == $region);
            if (!$hasBlockInRegion) {
                $block = current($blocks);

                $DB->update_record('block_instances', (object)[
                    'id' => $block->id,
                    'defaultregion' => $region,
                    'showinsubcontexts' => $region == 'side-post' ? 1 : 0,
                ]);
            }
        }
    }

    /**
     * Ensures that within a context an instance of block_exacomp exists.
     * @param
     **/
    public static function add_exacomp_to_context($context, $region = null) {
        global $CFG, $DB;
        $blocks = $DB->get_records('block_instances', array('blockname' => 'exacomp', 'parentcontextid' => $context->id));
        if (!$blocks) {
            // Create edupublisher-block in targetcourse.
            $blockdata = (object)array(
                'blockname' => 'exacomp',
                'parentcontextid' => $context->id,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'course-view-*',
                'defaultregion' => $region ?: 'side-post',
                'defaultweight' => -10,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time(),
            );
            $DB->insert_record('block_instances', $blockdata);
        }

        // TODO: needs exacomp update
        /*
        require_once $CFG->dirroot . '/blocks/exacomp/lib/lib.php';
        $settings = block_exacomp_get_settings_by_course($context->instanceid);
        $settings->uses_activities = 1;
        block_exacomp_save_coursesettings($context->instanceid, $settings);
        */
    }

    /**
     * Determines whether or not the user can create groups.
     * @return boolean
     */
    public static function can_create_groups() {
        if (has_capability('block/edupublisher:cancreategroups', \context_system::instance())) {
            return true;
        }
        // Test if users e-mail domain matches.
        global $DB, $USER;
        $domains = get_config('block_edupublisher', 'groupsdomains');
        $usermail = explode('@', $USER->email);
        if (strpos($domains, '@' . $usermail[1]) > -1) {
            return true;
        }
        return false;
    }

    /**
     * Return a list of all channels.
     * @return array
     */
    public static function channels() {
        $definition = self::get_channel_definition();
        return array_keys($definition);
    }

    /**
     * Determines if the plugin can be used.
     * @param die If true will show error and die if requirements are not fulfilled.
     * @param context Course context to test capabilities for.
     * @return true or false
     **/
    public static function check_requirements($die = true, $context = NULL) {
        global $CFG, $OUTPUT, $PAGE;
        $category = get_config('block_edupublisher', 'category');
        if (intval($category) == 0) {
            if ($die) {
                throw new \moodle_exception('No category was set by admin!');
            } else {
                return false;
            }
        }

        $context = $context || (isset($PAGE->context->id)) ? $PAGE->context : context_system::instance();
        $allowguests = get_config('block_edupublisher', 'allowguests');
        if (empty($allowguests) && !has_capability('block/edupublisher:canuse', $context)) {
            if ($die) {
                throw new \moodle_exception(!empty($allowguests) ? 'missing_capability' : 'guest_not_allowed', 'block_edupublisher');
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Removes any files in this filearea.
     **/
    public static function clear_file_storage($context, $component, $fieldname, $itemid, $fs = NULL) {
        if (!isset($fs)) {
            $fs = get_file_storage();
        }
        $files = $fs->get_area_files($context->id, $component, $fieldname, $itemid);
        foreach ($files as $f) {
            if (!$f)
                continue;
            $f->delete();
        }
    }

    /**
     * Enrols users to specific courses
     * @param courseids array containing courseid or a single courseid
     * @param userids array containing userids or a single userid
     * @param roleid roleid to assign, or -1 if wants to unenrol
     * @return true or false
     **/
    public static function course_manual_enrolments($courseids, $userids, $roleid, $remove = 0) {
        global $CFG, $DB, $reply;
        if (!isset($reply))
            $reply = array();
        //print_r($courseids); print_r($userids); echo $roleid;
        if (!is_array($courseids))
            $courseids = array($courseids);
        if (!is_array($userids))
            $userids = array($userids);
        // Retrieve the manual enrolment plugin.
        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            return false;
        }
        $failures = 0;
        foreach ($courseids as $courseid) {
            // Check if course exists.
            $course = $DB->get_record('course', array('id' => $courseid), '*', IGNORE_MISSING);
            $context = \context_course::instance($course->id);
            //$course = get_course($courseid);
            if (empty($course->id))
                continue;
            // Check manual enrolment plugin instance is enabled/exist.
            $instance = null;
            $enrolinstances = enrol_get_instances($courseid, false);
            $reply['enrolinstances'] = $enrolinstances;
            foreach ($enrolinstances as $courseenrolinstance) {
                if ($courseenrolinstance->enrol == "manual") {
                    $instance = $courseenrolinstance;
                    break;
                }
            }
            if (empty($instance)) {
                // We have to add a "manual-enrolment"-instance
                $fields = array(
                    'status' => 0,
                    'roleid' => get_config('local_eduvidual', 'defaultrolestudent'),
                    'enrolperiod' => 0,
                    'expirynotify' => 0,
                    'expirytreshold' => 0,
                    'notifyall' => 0,
                );
                require_once($CFG->dirroot . '/enrol/manual/lib.php');
                $emp = new enrol_manual_plugin();
                $reply['createinstance'] = true;
                $instance = $emp->add_instance($course, $fields);
            }
            $reply['enrolinstance'] = $instance;
            if (empty($instance)) {
                $failures++;
            } else {
                if ($instance->status == 1) {
                    // It is inactive - we have to activate it!
                    $data = (object)array('status' => 0);
                    require_once($CFG->dirroot . '/enrol/manual/lib.php');
                    $emp = new enrol_manual_plugin();
                    $reply['updateinstance'] = true;
                    $emp->update_instance($instance, $data);
                    $instance->status = $data->status;
                }
                foreach ($userids as $userid) {
                    if (!empty($remove)) {
                        role_unassign($roleid, $userid, $context->id);
                        // If this was the last role, we unenrol completely
                        $roles = get_user_roles($context, $userid);
                        $foundatleastone = false;
                        foreach ($roles as $role) {
                            if ($role->contextid == $context->id) {
                                $foundatleastone = true;
                                break;
                            }
                        }

                        if (!$foundatleastone) {
                            $enrol->unenrol_user($instance, $userid);
                        }
                    } else {
                        $enrol->enrol_user($instance, $userid, $roleid, 0, 0, ENROL_USER_ACTIVE);
                    }
                }
            }
        }
        return ($failures == 0);
    }

    /**
     * Generates a complete working html-body.
     * @param subject
     * @param content
     */
    public static function enhance_mail_body($subject, $content) {
        $mailtemplate = get_config('block_edupublisher', 'mail_template');
        return str_replace(array("{{{content}}}", "{{{subject}}}"), array($content, $subject), $mailtemplate);
    }

    /**
     * Return the channel definition.
     * @return array.
     */
    public static function get_channel_definition() {
        global $CFG, $USER;

        $required_value = empty($CFG->developermode) ? 1 : 0;

        $definition = array(
            'default' => array(
                // 'suppresscomment' => array('type' => 'select', 'datatype' => PARAM_INT, 'hidden_except_maintainer' => 1, 'options' => array(
                //     '0' => get_string('no'), '1' => get_string('yes'),
                // ), 'donotstore' => 1),
                'title' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => 1, 'searchable' => 1),
                'summary' => array('type' => 'editor', 'datatype' => PARAM_RAW, 'required' => 1, 'searchable' => 1),
                'licence' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'options' => array(
                    //'Public Domain' => 'Public Domain',
                    'cc-by' => 'cc-by',
                    'cc-0' => 'cc-0',
                    'cc-by-sa' => 'cc-by-sa',
                    'cc-by-nc' => 'cc-by-nc',
                    'cc-by-nc-sa' => 'cc-by-nc-sa',
                    'other' => get_string('default_licenceother', 'block_edupublisher'),
                ), 'required' => $required_value, 'searchable' => 1),
                'authorname' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => $required_value, 'searchable' => 1, 'default' => fullname($USER)),
                // 'authormail' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => $required_value, 'searchable' => 1),
                // 'authormailshow' => array('type' => 'select', 'datatype' => PARAM_INT, 'default' => 1, 'options' => array(
                //     '1' => get_string('yes'), '0' => get_string('no'),
                // ),
                // ),
                // 'origins' => array('type' => 'select', 'multiple' => 1, 'datatype' => PARAM_INT),
                'image' => array('type' => 'filemanager', 'accepted_types' => 'image', 'required' => $required_value),
                'schoollevels' => array('type' => 'select', 'multiple' => 1, 'datatype' => PARAM_TEXT,
                    'required' => $required_value, 'options' => array(
                        'elementary' => get_string('default_schoollevel_elementary', 'block_edupublisher'),
                        'primary' => get_string('default_schoollevel_primary', 'block_edupublisher'),
                        'secondary_1' => get_string('default_schoollevel_secondary_1', 'block_edupublisher'),
                        'secondary_2' => get_string('default_schoollevel_secondary_2', 'block_edupublisher'),
                        'tertiary' => get_string('default_schoollevel_tertiary', 'block_edupublisher'),
                        'adult' => get_string('default_schoollevel_adult', 'block_edupublisher'),
                    ),
                ),
                'subjectareas' => array('type' => 'select', 'multiple' => 1, 'datatype' => PARAM_TEXT,
                    'required' => $required_value, 'options' => array(
                        'arts' => get_string('default_subjectarea_arts', 'block_edupublisher'),
                        'economics' => get_string('default_subjectarea_economics', 'block_edupublisher'),
                        'geography' => get_string('default_subjectarea_geography', 'block_edupublisher'),
                        'history' => get_string('default_subjectarea_history', 'block_edupublisher'),
                        'informatics' => get_string('default_subjectarea_informatics', 'block_edupublisher'),
                        'languages' => get_string('default_subjectarea_languages', 'block_edupublisher'),
                        'mathematics' => get_string('default_subjectarea_mathematics', 'block_edupublisher'),
                        'naturalsciences' => get_string('default_subjectarea_naturalsciences', 'block_edupublisher'),
                        'philosophy' => get_string('default_subjectarea_philosophy', 'block_edupublisher'),
                        'physic leducation' => get_string('default_subjectarea_physicaleducation', 'block_edupublisher'),
                        'other' => get_string('default_subjectarea_other', 'block_edupublisher'),
                    ),
                ),
                // 'kompetenzen' => array('type' => 'static', 'datatype' => PARAM_RAW, 'required' => 0, 'searchable' => 1),
                'tags' => array('type' => 'tags', 'datatype' => PARAM_TEXT, 'multiple' => 1, 'tagparams' => array('itemtype' => 'packages', 'component' => 'block_edupublisher'), 'searchable' => 1),
                // Hidden elements
                'active' => array('type' => 'hidden', 'datatype' => PARAM_BOOL),
                'published' => array('type' => 'hidden', 'datatype' => PARAM_INT, 'default' => 0),
                // 'exacompsourceids' => array('type' => 'hidden', 'multiple' => 1, 'datatype' => PARAM_INT),
                // 'exacomptitles' => array('type' => 'hidden', 'multiple' => 1, 'datatype' => PARAM_TEXT),
                // 'exacompdatasources' => array('type' => 'hidden', 'multiple' => 1, 'datatype' => PARAM_TEXT),
                'imageurl' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                'publishas' => array('type' => 'hidden', 'datatype' => PARAM_BOOL, 'default' => 1),
                'filling_mode' => ['type' => 'radio', 'label' => 'Eingabemodus', 'datatype' => PARAM_INT, 'default' => package::FILLING_MODE_SIMPLE, 'options' => [
                    package::FILLING_MODE_SIMPLE => 'Einfacher Modus',
                    package::FILLING_MODE_EXPERT => 'Expert/innen Modus',
                ]],
            ),
            'eduthekneu' => array(
                'active' => array('type' => 'hidden', 'datatype' => PARAM_BOOL),
                'publishas' => array('type' => 'boolean', 'datatype' => PARAM_BOOL, 'default' => 1),
                'published' => array('type' => 'hidden', 'datatype' => PARAM_INT, 'default' => 0),
                'ltiurl' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                'lticartridge' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                'ltisecret' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                'contenttypes' => array('type' => 'select', 'datatype' => PARAM_TEXT,
                    'multiple' => 1, 'required' => $required_value, 'options' => array(
                        'assignment' => 'Aufgabe',
                        'exercise' => 'Interaktive Übung',
                        'learningtrack' => 'Lernstrecke',
                        'supportmaterial' => 'Begleitmaterial',

                    ),
                ),
                'purposes' => array(
                    'type' => 'select', 'datatype' => PARAM_TEXT,
                    'multiple' => 1, 'required' => $required_value, 'options' => array(
                        'preparation' => 'Unterrichtsvorbereitung',
                        'supervised' => 'Lernen mit Begleitung',
                        'selfpaced' => 'Lernen ohne Begleitung/Selbstlernen',
                    ),
                ),
            ),
            'etapas' => array(
                'active' => array('type' => 'hidden', 'datatype' => PARAM_BOOL),
                'publishas' => array('type' => 'boolean', 'datatype' => PARAM_BOOL, 'default' => 1),
                //'erprobungen' => array('type' => 'filemanager', 'multiple' => 1, 'hidden_on_init' => 1, 'maxfiles' => 20, 'accepted_types' => 'document'),
                // 'ltiurl' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                // 'lticartridge' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                // 'ltisecret' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                // 'published' => array('type' => 'hidden', 'datatype' => PARAM_INT, 'default' => 0),
                // 'status' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'default' => 'inspect',
                //     'hidden_on_init' => true, 'options' => array(
                //         'inspect' => get_string('etapas_status_inspect', 'block_edupublisher'),
                //         'eval' => get_string('etapas_status_eval', 'block_edupublisher'),
                //         'public' => get_string('etapas_status_public', 'block_edupublisher'),
                //     ),
                // ),
                // 'type' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'default' => 'lesson', 'required' => $required_value, 'searchable' => 1, 'options' => array(
                //     'lesson' => 'Unterricht', 'collection' => 'Beispielsammlung', 'learningroute' => 'Lernstrecke'),
                // ),
                // 'subtype' => array('type' => 'select', 'datatype' => PARAM_TEXT,
                //     'hidden_except_maintainer' => true, 'searchable' => 1, 'default' => 'etapa',
                //     'options' => array(
                //         'etapa' => 'eTapa', 'digi.komp 4' => 'digi.komp 4', 'digi.komp 8' => 'digi.komp 8',
                //         'digi.komp 12' => 'digi.komp 12',
                //     ),
                // ),
                // 'gegenstand' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => $required_value, 'searchable' => 1),
                //'vonschule' => array('type' => 'text', 'datatype' => PARAM_TEXT),
                // 'schulstufe' => array('type' => 'select', 'datatype' => PARAM_TEXT,
                //     'multiple' => 1, 'required' => $required_value, 'splitcols' => 1, 'options' => array(
                //         1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5,
                //         6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11,
                //         12 => 12, 13 => 13,
                //     ),
                // ),
                // 'kompetenzen' => array('type' => 'static', 'datatype' => PARAM_RAW, 'required' => 0, 'default' => get_string('etapas_kompetenzen_help', 'block_edupublisher'), 'searchable' => 1),
                // 'stundenablauf' => array('type' => 'editor', 'datatype' => PARAM_RAW, 'required' => $required_value, 'searchable' => 1),
                'zeitbedarf' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'options' => array(
                    '00:15' => '15 Minuten',
                    '00:30' => '30 Minuten',
                    '00:45' => '1 UE',
                    '01:30' => '2 UE',
                    '02:15' => 'mehr als 2 UE',
                    // '01:00' => '1 Std. (bitte nicht mehr verwenden)',
                    // '02:00' => '2 Std. (bitte nicht mehr verwenden)',
                    // '03:00' => '3 Std. (bitte nicht mehr verwenden)',
                )),
                'vorkenntnisse' => array('type' => 'editor', 'datatype' => PARAM_RAW, 'required' => $required_value, 'searchable' => 1),
                'voraussetzungen' => array('type' => 'editor', 'datatype' => PARAM_RAW, 'required' => $required_value, 'searchable' => 1),
            ),
            'eduthek' => array(
                'active' => array('type' => 'hidden', 'datatype' => PARAM_BOOL),
                'publishas' => array('type' => 'boolean', 'datatype' => PARAM_BOOL),
                'published' => array('type' => 'hidden', 'datatype' => PARAM_INT, 'default' => 0),
                'ltiurl' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                'lticartridge' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                'ltisecret' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                'curriculum' => array('type' => 'editor', 'datatype' => PARAM_RAW, 'required' => 1),
                'language' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => 1),
                'type' => array('type' => 'select', 'datatype' => PARAM_TEXT,
                    'multiple' => 1, 'required' => 1, 'options' => array(
                        'edu_tutorial' => 'Anleitung/Tutorial',
                        'edu_tool' => 'Tool/App',
                        'edu_worksheet' => 'Arbeitsblatt',
                        'edu_exercise' => 'Lernhilfe/Aufgabe/Übung',
                        'edu_tip' => 'Buch-/Webtipp',
                        'edu_template' => 'Vorlage/Checkliste',
                        'edu_publication' => 'Publikation/Handreichung',
                        'edu_audio' => 'Info-Audio',
                        'edu_presentation' => 'Info-Präsentation',
                        'edu_article' => 'Info-Artikel',
                        'edu_video' => 'Info-Video',
                        'edu_portal' => 'Info- und Themenportal',
                        'edu_module' => 'Lernmodul',
                        'edu_package' => 'Themenpaket',
                        'edu_diagnosis' => 'Pädagogisches Diagnosewerkzeug',
                        'edu_graphic' => 'Plakat/Folder/Grafik',
                        'edu_project' => 'Projekt',
                        'edu_examprep' => 'Prüfungsvorbereitung',
                        'edu_quiz' => 'Quiz',
                        'edu_collection' => 'Sammlung',
                        'edu_selflearning' => 'Selbstlernkurs',
                        'edu_game' => 'Lernspiel',
                        'edu_scenario' => 'Unterrichtsszenario',
                        'edu_animation' => 'Animation/Simulation/Impuls',
                        'edu_competence' => 'Kompetenzmodell',
                    ),
                ),
                'schooltype' => array('type' => 'select', 'datatype' => PARAM_TEXT,
                    'multiple' => 1, 'required' => 1, 'options' => array(
                        '0' => 'Sonstige Bildungseinrichtungen',
                        '100' => 'Volksschulen',
                        '200' => 'Sonderschulen',
                        '300' => 'Hauptschulen und Neue Mittelschulen',
                        '400' => 'Polytechnische Schulen',
                        '1000' => 'Allgemein bildende höhere Schulen, Unterstufe',
                        '1100' => 'Allgemein bildende höhere Schulen, Oberstufe',
                        '2000' => 'Berufsbildende Pflichtschulen',
                        '3100' => 'Mittlere technische, gewerbliche und kunstgewerbliche Lehranstalten',
                        '3600' => 'Mittlere kaufmännische Lehranstalten',
                        '3710' => 'Mittlere Lehranstalten für Humanberufe, ein- und zweijährig',
                        '3730' => 'Mittlere Lehranstalten für Humanberufe, drei- und mehrjährig',
                        '4100' => 'Höhere technische und gewerbliche Lehranstalten',
                        '4600' => 'Höhere kaufmännische Lehranstalten',
                        '4710' => 'Höhere Lehranstalten für wirtschaftliche Berufe',
                        '4720' => 'Höhere Lehranstalten für Mode und künstlerische Gestaltung',
                        '4730' => 'Höhere Lehranstalten für Tourismus',
                        '5120' => 'Bildungsanstalten für Kindergarten-Pädagogik',
                        '5130' => 'Bildungsanstalten für Sozialpädagogik',
                        '6000' => 'Land- und forstwirtschaftliche Berufsschulen',
                        '6100' => 'Land- und forstwirtschaftliche Fachschulen',
                        '6200' => 'Höhere Lehranstalten für Land- und Forstwirtschaft',
                    ),
                ),
                'topic' => array('type' => 'select', 'datatype' => PARAM_TEXT,
                    'multiple' => 1, 'required' => 1, 'options' => array(
                        'T010' => 'Bautechnik',
                        'T020' => 'Berufsorientierung',
                        'T030' => 'Betriebswirtschaft',
                        'T040' => 'Bewegung und Sport',
                        'T050' => 'Bildnerische Erziehung',
                        'T060' => 'Bildung für nachhaltige Entwicklung',
                        'T070' => 'Biologie',
                        'T080' => 'Biologie und Umweltkunde',
                        'T090' => 'Bosnisch, Kroatisch, Serbisch',
                        'T100' => 'Chemie',
                        'T110' => 'Darstellende Geometrie',
                        'T120' => 'Darstellendes Spiel',
                        'T130' => 'Design',
                        'T140' => 'Deutsch',
                        'T150' => 'Didaktik und Pädagogik',
                        'T160' => 'Elektrotechnik',
                        'T170' => 'Englisch',
                        'T180' => 'Ernährung',
                        'T190' => 'Ernährung & Haushalt',
                        'T200' => 'Europapolitische Bildung',
                        'T210' => 'Fachkunde, Fachtheorie, Fachpraxis',
                        'T220' => 'Französisch',
                        'T230' => 'Gender und Diversität',
                        'T240' => 'Geografie',
                        'T250' => 'Geografie und Wirtschaftskunde',
                        'T260' => 'Geometrisches Zeichen',
                        'T270' => 'Geschichte & Sozialkunde, Politische Bildung',
                        'T280' => 'Geschichte, Politische Bildung',
                        'T290' => 'Gesundheitserziehung',
                        'T300' => 'Globales Lernen',
                        'T310' => 'Griechisch',
                        'T320' => 'Informatik',
                        'T330' => 'Interkulturelles Lernen',
                        'T340' => 'Italienisch',
                        'T350' => 'Kommunikation',
                        'T360' => 'Kroatisch',
                        'T370' => 'Kunst & Kreativität',
                        'T380' => 'Latein',
                        'T390' => 'Leseerziehung',
                        'T400' => 'Marketing',
                        'T410' => 'Maschinenbau',
                        'T420' => 'Mathematik',
                        'T430' => 'Mechanik, Mechatronik',
                        'T440' => 'Digitale Grundbildung, Medienbildung',
                        'T450' => 'Musik',
                        'T460' => 'Naturwissenschaften',
                        'T470' => 'Office- & Informationsmanagement',
                        'T480' => 'Physik',
                        'T490' => 'Politische Bildung',
                        'T500' => 'Polnisch',
                        'T520' => 'Projektunterricht',
                        'T510' => 'Projektmanagement',
                        'T530' => 'Psychologie & Philosophie',
                        'T540' => 'Qualitätsmanagement',
                        'T550' => 'Rechnungswesen',
                        'T560' => 'Religion',
                        'T570' => 'Russisch',
                        'T580' => 'Sachunterricht',
                        'T590' => 'Schach',
                        'T600' => 'Serbisch',
                        'T610' => 'Sexualerziehung',
                        'T620' => 'Slowakisch',
                        'T630' => 'Slowenisch',
                        'T640' => 'Soziales Lernen',
                        'T650' => 'Spanisch',
                        'T660' => 'Technisches und Textiles Werken',
                        'T670' => 'Tourismus',
                        'T680' => 'Tschechisch',
                        'T690' => 'Umweltbildung',
                        'T700' => 'Ungarisch',
                        'T710' => 'Verkehrserziehung',
                        'T720' => 'Volkswirtschaft',
                        'T730' => 'Vorschulstufe',
                        'T740' => 'Werkstätte',
                        'T750' => 'Wirtschaft und Recht',
                        'T760' => 'Wirtschafts-& Verbraucherbildung',
                        'T999' => 'Sonstiges',
                    ),
                ),
                'educationallevel' => array('type' => 'select', 'datatype' => PARAM_TEXT,
                    'multiple' => 1, 'required' => 1, 'options' => array(
                        1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5,
                        6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11,
                    ),
                ),
            ),
            'commercial' => array(
                'active' => array('type' => 'hidden', 'datatype' => PARAM_BOOL),
                'publishas' => array('type' => 'boolean', 'datatype' => PARAM_BOOL, 'default' => 1),
                'published' => array('type' => 'hidden', 'datatype' => PARAM_INT, 'default' => 0),
                'publisher' => array('type' => 'select', 'datatype' => PARAM_INT),
                'shoplink' => array('type' => 'url', 'datatype' => PARAM_TEXT),
                'validation' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'options' => array(
                    'external' => get_string('commercial_validateexternal', 'block_edupublisher'),
                    'internal' => get_string('commercial_validateinternal', 'block_edupublisher')),
                ),
            ),
        );
        global $CFG, $package, $MODE_SHOW_FORM;
        if (isset($package)) {
            // $package->set(1, 'publishas', 'default');
            // Customize definition to package.
            $channels = array_keys($definition);
            foreach ($channels as $channel) {
                // If not set in package check for POST.
                if (empty($package->get('publishas', $channel)) && !empty(optional_param($channel . '_publishas', 0, PARAM_INT))) {
                    $package->set(1, 'publishas', $channel);
                } elseif (empty($package->get('publishas', $channel))) {
                    $package->set(0, 'publishas', $channel);
                }
                $fields = array_keys($definition[$channel]);
                foreach ($fields as $field) {
                    $ofield = &$definition[$channel][$field];
                    if (!empty($ofield['required'])) {
                        //echo $channel . '_' . $field . " is " . (!empty($ofield['required']) ? 'required' : 'not required') . " and is " . $package->{$channel . '_' . $field} ."\n";
                    }
                    if (isset($ofield['hidden_on_init']) && $ofield['hidden_on_init']) {
                        if (empty($package->id)) {
                            $ofield['type'] = 'hidden';
                        }
                    }
                    if ($channel == 'default' && $field == 'origins') {
                        $possible_origins = $package->load_possible_origins();
                        $options = array();
                        foreach ($possible_origins as $po) {
                            if (empty($po->id))
                                continue;
                            $options[$po->id] = $po->title;
                        }
                        if (count($options) > 0) {
                            $ofield['options'] = $options;
                        } else {
                            $ofield['type'] = 'hidden';
                        }
                    }
                    if (isset($ofield['required']) && $ofield['required']
                        && (
                            !empty($package->get('publishas', $channel))
                            ||
                            !empty($MODE_SHOW_FORM)
                        )) {
                        // Keep it required
                    } else {
                        //$ofield['required'] = 0;
                    }
                }
            }
        }

        return $definition;
    }

    /**
     * Gets a course image if exists.
     * @param course course object
     * @param localpath true if we want local path, false for wwwpath
     * @return object containing fields 'imagename' and 'imagepath'
     **/
    public static function get_course_image($course, $localpath = false) {
        global $CFG;
        // Get Course image if any
        $_course = new course_in_list($course);
        $imagename = '';
        $imagepath = '';
        foreach ($_course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $imagename = $file->get_filename();
                $contenthash = $file->get_contenthash();
                $imagepath = ($localpath)
                    ? $CFG->dataroot . '/filedir/' . substr($contenthash, 0, 2) . '/' . substr($contenthash, 2, 2) . '/' . $contenthash
                    : $CFG->wwwroot . '/pluginfile.php/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . '/' . $file->get_filename()//: '' . moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename())
                ;
                break;
            }
        }
        return (object)array('imagename' => $imagename, 'imagepath' => $imagepath);
    }

    /**
     * Create a temporary directory and return its path.
     * @return path to tempdir.
     */
    public static function get_tempdir() {
        global $CFG;
        $dir = $CFG->tempdir . '/edupublisher-coursefiles';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        return $dir;
    }

    /**
     * Returns a list of courses from a user
     * Note: teacher is identified by capability 'moodle/course:update'
     * @param user User-Object, if empty use $USER.
     * @param capability to search.
     */
    public static function get_courses($user = null, $capability = '') {
        if (empty($user)) {
            global $USER;
            $user = $USER;
        }
        $courses = enrol_get_all_users_courses($user->id, true);
        if (empty($capability)) {
            return $courses;
        } else {
            $ids = array_keys($courses);
            foreach ($ids as $id) {
                $context = \context_course::instance($id);
                $canedit = has_capability($capability, $context);
                if (!$canedit)
                    unset($courses[$id]);
            }
            return $courses;
        }
    }

    /**
     * Gets an existing package by its courseid.
     * @param courseid the courseid.
     * @param strictness by default MUST_EXIST
     * @param createifmissing if no package is found for this course, create package?
     * @return object of type \block_edupublisher\package or null
     */
    public static function get_package_by_courseid($courseid, $strictness = MUST_EXIST, $createifmissing = false): ?package {
        global $DB;
        $item = $DB->get_record('block_edupublisher_packages', array('course' => $courseid), '*', $strictness);

        if (!empty($item->id)) {
            return new \block_edupublisher\package($item->id, true) ?: null;
        } else if ($createifmissing) {
            $package = self::get_package_from_course($courseid);
            $package->store_package((object)[]);
            return $package;
        } else {
            return null;
        }
    }

    /**
     * Creates an empty package and fills with data from course.
     * This is used when we create a new package.
     **/
    public static function get_package_from_course($courseid) {
        global $DB, $USER;
        $package = new \block_edupublisher\package(0);
        $course = \get_course($courseid);
        $package->set(0, 'active');
        $package->set($course->id, 'course');
        $package->set($course->id, 'sourcecourse');
        $package->set($course->fullname, 'title', 'default');
        $package->set($USER->firstname . ' ' . $USER->lastname, 'authorname', 'default');
        $package->set($USER->email, 'authormail', 'default');
        $package->set($course->summary, 'summary', 'default');

        return $package;
    }

    /**
     * Log that a user visited a course-page of a package.
     * @param packageid that is visited.
     * @param action String, either 'viewed', 'enrolled', 'unenrolled' or 'cloned'
     */
    public static function log_user_visit($packageid, $action) {
        if (empty($packageid))
            return;
        // Ensure the action is a valid value.
        if (!in_array($action, array('viewed', 'enrolled', 'unenrolled', 'cloned')))
            return;

        global $DB, $USER;
        // The viewed action is only logged if it does not double the last entry.
        if ($action == 'viewed') {
            // If we use danube.ai use a cache to track the visited packages.
            // Disable danube.ai
            // self::get_danubeai_recommendations($packageid);
            $sql = "SELECT *
                        FROM {block_edupublisher_log}
                        WHERE userid=?
                            AND viewed=1
                        ORDER BY id DESC
                        LIMIT 0,1";
            $lastrecord = $DB->get_record_sql($sql, array($USER->id));
            if (!empty($lastrecord->packageid) && $lastrecord->packageid == $packageid)
                return;
        }

        // Log this event now.
        $data = array(
            'packageid' => $packageid,
            'userid' => $USER->id,
            'timeentered' => time(),
            $action => 1,
        );
        $DB->insert_record('block_edupublisher_log', $data);
    }

    /**
     * Notifies maintainers of a specific channel about changes.
     * @param channel array of channels to select the maintainers for notification, if not set or empty use autodetection.
     **/
    public function notify_maintainers($channels = array()) {
        global $CFG, $OUTPUT;
        if (count($channels) == 0) {
            if (!empty($this->get('publishas', 'etapas'))
                &&
                !empty($this->get('ltisecret', 'etapas'))
                &&
                empty($this->get('active', 'etapas'))
            ) {
                $channels[] = 'etapas';
            }
            if (!empty($this->get('publishas', 'eduthek'))
                &&
                !empty($this->get('ltisecret', 'eduthek'))
                &&
                empty($this->get('active', 'eduthek'))
            ) {
                $channels[] = 'eduthek';
            }
            // Nobody would be responsible for this item. Fall back to default maintainers.
            if (count($channels) == 0 && empty($this->get('active', 'default'))) {
                $channels[] = 'default';
            }
        }

        // Prepare e-Mail
        $fromuser = \core_user::get_support_user();
        $possiblechannels = array('default', 'eduthek', 'etapas');
        foreach ($channels as $channel) {
            if (!in_array($channel, $possiblechannels))
                continue;

            $this->_wwwroot = $CFG->wwwroot;
            $messagehtml = $OUTPUT->render_from_template(
                'block_edupublisher/package_' . $channel . '_notify',
                $this->metadata
            );
            $subject = get_string($channel . '__mailsubject', 'block_edupublisher');
            $messagehtml = enhance_mail_body($subject, $messagehtml);
            $messagetext = html_to_text($messagehtml);
            $category = get_config('block_edupublisher', 'category');
            $context = \context_coursecat::instance($category);
            $recipients = get_users_by_capability($context, 'block/edupublisher:manage' . $channel, '', '', '', 10);
            foreach ($recipients as $recipient) {
                email_to_user($recipient, $fromuser, $subject, $messagetext, $messagehtml, "", true);
            }
        }
    }

    /**
     * Sets the capabilities of a course to allow course imports.
     * @param courseid.
     * @param trigger true if we enable the package, false if we disable it.
     **/
    public static function package_setcaps($courseid, $trigger) {
        global $DB, $USER;

        $ctxcourse = \context_course::instance($courseid);
        $capabilities = array(
            'moodle/backup:backupcourse',
            'moodle/backup:backuptargetimport',
        );
        $roles = array(
            7,
            7,
        );
        $contexts = array(
            $ctxcourse,
            $ctxcourse,
        );
        $permission = ($trigger) ? CAP_ALLOW : CAP_INHERIT;
        for ($a = 0; $a < count($capabilities); $a++) {
            \role_change_permission($roles[$a], $contexts[$a], $capabilities[$a], $permission);
        }
    }

    /**
     * Checks if a package has a coursebackup and extracts to backuptempdir for restore.
     * @param package
     */
    public static function prepare_restore($package) {
        global $CFG, $DB, $OUTPUT;
        if ($package->get('backuped') == 0) {
            $alert = array(
                'content' => \get_string('coursebackup:missing', 'block_edupublisher'),
                'type' => 'danger',
                'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->courseid,
            );
            echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
            return;
        }

        $course = $DB->get_record('course', array('id' => $package->courseid), '*', MUST_EXIST);
        $ctx = \context_course::instance($course->id);

        $fs = \get_file_storage();
        $file = $fs->get_file($ctx->id, 'block_edupublisher', 'coursebackup', 0, '/', 'coursebackup.mbz');

        if (!$file) {
            $alert = array(
                'content' => \get_string('coursebackup:notfound', 'block_edupublisher'),
                'type' => 'danger',
                'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->courseid,
            );
            echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
            return;
        }

        $fp = \get_file_packer('application/vnd.moodle.backup');
        $backuptempdir = \make_backup_temp_directory('edupublisher' . $package->id);
        if (!is_dir($backuptempdir) || !file_exists($backuptempdir . '/moodle_backup.xml')) {
            $file->extract_to_pathname($fp, $backuptempdir);
        }

        return 'edupublisher' . $package->id;
    }

    /**
     * Enables or disables guest access to a course.
     * @param courseid the course id
     * @param setto 1 (default) to enable, 0 to disable access.
     */
    public static function toggle_guest_access($courseid, $setto = 1) {
        global $CFG;

        require_once($CFG->dirroot . '/enrol/guest/lib.php');
        $course = \get_course($courseid);
        $fields = array(
            'status' => (empty($setto) ? 1 : 0), // status in database reversed
            'password' => '',
        );
        $gp = new \enrol_guest_plugin();
        if (!empty($setto)) {
            $gp->add_instance($course, $fields);
        } else {
            require_once($CFG->dirroot . '/lib/enrollib.php');
            $instances = \enrol_get_instances($courseid, false);
            foreach ($instances as $instance) {
                if ($instance->enrol != 'guest')
                    continue;
                $gp->delete_instance($instance);
            }
        }
    }

    /**
     * Gets the user picture and returns it as base64 encoded string.
     * @param userid
     * @return picture base64 encoded
     */
    public static function user_picture_base64($userid) {
        $context = \context_user::instance($userid);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'user', 'icon');
        $find = array('f1.jpg', 'f1.png');
        foreach ($files as $f) {
            if (in_array($f->get_filename(), $find)) {
                $extension = explode(".", $f->get_filename());
                $extension = $extension[count($extension) - 1];
                return 'data:image/' . $extension . ';base64,' . base64_encode($f->get_content());
            }
        }
        return '';
    }

    /**
     * Checks whether or not local_eduvidual is installed
     * @return true or false
     **/
    public static function uses_eduvidual() {
        global $CFG;
        return file_exists($CFG->dirroot . '/local/eduvidual/version.php');
    }

    public static function show_star_rating() {
        if (!static::uses_eduvidual()) {
            return true;
        }

        $highestrole = \local_eduvidual\locallib::get_highest_role();
        // only show star rating, when user is manager or teacher
        if ($highestrole == \local_eduvidual\locallib::ROLE_MANAGER || $highestrole == \local_eduvidual\locallib::ROLE_TEACHER) {
            return true;
        } else {
            return false;
        }
    }


    public static function sync_package_to_course(package $package) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/modlib.php');

        // block einfügen
        static::add_to_context($package->get_context(), 'content-upper');
        static::add_exacomp_to_context($package->get_context());

        if ($package->get('filling_mode', 'default') == package::FILLING_MODE_EXPERT) {
            // bei expert modus nicht syncen
            return;
        }

        $fs = get_file_storage();

        $content_items = array_values($DB->get_records('block_edupublisher_pkg_items', ['packageid' => $package->id], 'sorting'));
        $course = get_course($package->courseid);


        $course_sections = $DB->get_records('course_sections', ['course' => $package->courseid], 'section');
        $course_modules =$DB->get_records('course_modules', ['course' => $package->courseid]);
        $course_modules_to_delete = $course_modules;

        $make_section = function($data) use ($course, &$course_sections, $package) {
            $section = array_shift($course_sections);
            if (!$section) {
                $section = course_create_section($package->courseid);
            } else {
                // clean section
                // $modinfo = get_fast_modinfo($course->id);
                //
                // // Step 3: Get all course modules (activities/resources) in the specified section
                // $sections = $modinfo->get_sections();
                // if (isset($sections[$section->section])) {
                //     $moduleIds = $sections[$section->section]; // Get module IDs in the section
                //
                //     // Step 4: Loop through all module IDs in the section and delete each one
                //     foreach ($moduleIds as $cmid) {
                //         $cm = $modinfo->get_cm($cmid); // Get the course module object
                //         if ($cm && !$cm->deletioninprogress) {
                //             // Delete the module
                //             course_delete_module($cm->id);
                //         }
                //     }
                // }
            }

            course_update_section($package->courseid, $section, array_merge(['availability' => null], $data));

            return $section;
        };

        $summary = '';
        // $summary = $package->get_course_summary();
        // if ($preview_image = $package->get_preview_image()) {
        //     var_dump($preview_image);
        //
        //     $fileinfo = array(
        //         'contextid' => $package->get_context()->id,
        //         'component' => 'course',
        //         'filearea' => 'section',
        //         'itemid' => $section->id,
        //         'filepath' => '/',
        //         'filename' => $preview_image->get_filename(),
        //     );
        //     $fs->delete_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid']);
        //     $fs->create_file_from_storedfile($fileinfo, $preview_image);
        //
        //     $summary = '<p><img src="@@PLUGINFILE@@/' . $preview_image->get_filename() . '" alt="" role="presentation" class="img-fluid"></p>' . $summary;
        // }

        $context = $package->get_context();

        // the first section is the course summary
        // $section = $make_section();

        // course_update_section($package->courseid, $section, [
        //     'name' => 'Zusammenfassung',
        //     'summary' => $summary,
        // ]);
        //
        //
        // if ($exameta = $DB->get_field('modules', 'id', array('name' => 'exameta'))) {
        //     $moduleinfo = new \stdClass();
        //     $moduleinfo->module = $exameta;
        //     $moduleinfo->modulename = 'exameta';
        //     $moduleinfo->section = $section->section;
        //     $moduleinfo->display = 1;
        //     $moduleinfo->visible = 1;
        //     $moduleinfo->name = 'Zusammenfassung';
        //     $moduleinfo->intro = '';
        //     $moduleinfo->introformat = FORMAT_HTML;
        //     $moduleinfo = \add_moduleinfo($moduleinfo, $course);
        // }

        foreach ($content_items as $i => $content_item) {
            $section = $make_section([
                'name' => ($i + 1) . '. Aktivität/Ressource',
                'summary' => nl2br(clean_param($content_item->description, PARAM_TEXT)),
            ]);

            $create_or_update_module = function($moduleinfo) use ($course, $content_item, &$section, &$course_modules_to_delete) {
                global $DB;
                $idnumber = "block_edupublisher-content_item-{$content_item->id}-{$moduleinfo->modulename}";

                $moduleinfo->module ??= $DB->get_field('modules', 'id', array('name' => $moduleinfo->modulename));
                $moduleinfo->section = $section->section;

                // does not work, set it anyway:
                $moduleinfo->idnumber = $idnumber;
                // does work:
                $moduleinfo->cmidnumber = $idnumber;

                $moduleinfo->display = 1;
                $moduleinfo->visible = 1;
                $moduleinfo->intro ??= '';
                $moduleinfo->introformat ??= FORMAT_HTML;

                // check if exists
                $existingCmRecord = $DB->get_record('course_modules', ['module' => $moduleinfo->module, 'idnumber' => $idnumber]);
                if (!$existingCmRecord) {
                    $moduleinfo = \add_moduleinfo($moduleinfo, $course);
                } else {
                    // needed for update, and also for return value
                    $moduleinfo = (object)array_merge((array)$existingCmRecord, (array)$moduleinfo);
                    $moduleinfo->coursemodule = $existingCmRecord->id;

                    // needed for update
                    $moduleinfo->introeditor = [
                        'itemid' => 999999999,
                        'text' => $moduleinfo->intro,
                        'format' => $moduleinfo->introformat,
                    ];
                    $moduleinfo->files = 999999; // for mod_folder
                    $moduleinfo->revision = 0; // for mod_folder

                    $moduleinfo = \update_module($moduleinfo);

                    // section is not updated, so update it here:
                    $cm = get_coursemodule_from_id('', $moduleinfo->coursemodule, 0, false, MUST_EXIST);
                    // moveto_module already checks if the section is different
                    moveto_module($cm, $section);

                    unset($course_modules_to_delete[$moduleinfo->coursemodule]);
                }

                $competencyIds = $content_item->competencies ? explode(',', $content_item->competencies) : [];
                static::update_module_competencies($course->id, $moduleinfo->coursemodule, $competencyIds);

                return $moduleinfo;
            };

            if ($link = trim($content_item->link)) {
                $moduleinfo = new \stdClass();
                $moduleinfo->modulename = 'url';
                $moduleinfo->name = $link;
                $moduleinfo->externalurl = $link;
                $create_or_update_module($moduleinfo);
            }

            $files = $fs->get_area_files($context->id, 'block_edupublisher', 'pkg_item_files', $content_item->id, 'itemid, filepath, filename', false);

            $h5ps = [];
            foreach ($files as $key => $file) {
                if (str_ends_with(strtolower($file->get_filename()), '.h5p')) {
                    $h5ps[] = $file;
                    unset($files[$key]);
                }
            }

            if ($files) {
                $moduleinfo = new \stdClass();
                $moduleinfo->modulename = 'folder';
                $moduleinfo->name = 'Dateien';
                $moduleinfo->files = []; // $files; // geht so nicht
                $moduleinfo = $create_or_update_module($moduleinfo);

                $mod_context = \context_module::instance($moduleinfo->coursemodule);
                // delete old files
                $fs->delete_area_files($mod_context->id, 'mod_folder', 'content');
                // readd them
                foreach ($files as $file) {
                    $fileinfo = array(
                        'contextid' => $mod_context->id,
                        'component' => 'mod_folder',
                        'filearea' => 'content',
                        'itemid' => 0,
                        'filepath' => '/',
                        'filename' => $file->get_filename(),
                    );

                    $fs->create_file_from_storedfile($fileinfo, $file);
                }

                // not working:
                // $resource = new \stdClass();
                // $resource->course = $course->id;
                // $resource->name = 'Dateien';
                // $resource->intro = '';
                // $resource->introformat = FORMAT_HTML;
                // $resource->tobemigrated = 0;
                // $resource->section = $section->section;
                // $resource->visible = 1;
                // $resource->modulename = 'resource';
                // $resource->module = $DB->get_field('modules', 'id', array('name' => 'resource'));
                // $resource->instance = 0;
                // $resource->add = 'resource';
                // $resource->coursemodule = \add_course_module($resource);
                // $sectionreturn = 0;
                // \course_add_cm_to_section($course, $resource->coursemodule, $section->section, $sectionreturn);
                //
                // $mod_context = \context_module::instance($resource->coursemodule);
                // $fileinfo = array(
                //     'contextid' => $mod_context->id,
                //     'component' => 'mod_resource',
                //     'filearea'  => 'content',
                //     'itemid'    => 0,
                //     'filepath'  => '/',
                //     'filename'  => $file->get_filename()
                // );
                //
                // $fs->create_file_from_storedfile($fileinfo, $file);
                //
                // // Set display options
                // $displayoptions = array(
                //     'printintro' => 1,
                //     'showsize' => 1,
                //     'showtype' => 1,
                //     'popupwidth' => 620,
                //     'popupheight' => 450
                // );
                //
                // // Update the resource
                // $info = new \stdClass();
                // $info->display = RESOURCELIB_DISPLAY_AUTO;
                // $info->displayoptions = serialize($displayoptions);
                // $info->id = $resource->coursemodule;
                // var_dump($info);
                // $DB->update_record('resource', $info);


                // working:
                // $moduleinfo = new \stdClass();
                // $moduleinfo->modulename = 'resource';
                // $moduleinfo->name = 'Dateien';
                // $moduleinfo = $create_or_update_module($moduleinfo);
                //
                // $mod_context = \context_module::instance($moduleinfo->coursemodule);
                // foreach ($files as $file) {
                //     $fileinfo = array(
                //         'contextid' => $mod_context->id,
                //         'component' => 'mod_resource',
                //         'filearea' => 'content',
                //         'itemid' => 0,
                //         'filepath' => '/',
                //         'filename' => $file->get_filename(),
                //     );
                //
                //     $fs->create_file_from_storedfile($fileinfo, $file);
                // }
                //
                // // Set display options
                // $displayoptions = array(
                //     'printintro' => 1,
                //     'showsize' => 1,
                //     'showtype' => 1,
                //     'popupwidth' => 620,
                //     'popupheight' => 450,
                // );
                //
                // // Update the resource
                // $info = new \stdClass();
                // $info->display = RESOURCELIB_DISPLAY_AUTO;
                // $info->displayoptions = serialize($displayoptions);
                // $info->id = $moduleinfo->id;
                // $DB->update_record('resource', $info);
                //
                // rebuild_course_cache($course->id, true);
                // die('done');
            }

            if ($h5ps) {
                // $create_hvp_activity = function($courseid, $name, \stored_file $file, $section = 0) {
                //     global $DB, $USER;
                //
                //     // Verify the course exists
                //     $course = $DB->get_record('course', array('id' => $courseid));
                //     if (!$course) {
                //         throw new moodle_exception('courseidnotfound');
                //     }
                //
                //     // Prepare module data
                //     $moduleinfo = new \stdClass();
                //     $moduleinfo->course = $courseid;
                //     $moduleinfo->name = $name;
                //     $moduleinfo->intro = ''; // Optional introduction text
                //     $moduleinfo->introformat = FORMAT_HTML;
                //     $moduleinfo->section = $section;
                //     $moduleinfo->visible = true;
                //     $moduleinfo->modulename = 'hvp';
                //     $moduleinfo->module = $DB->get_field('modules', 'id', array('name' => 'hvp'));
                //     $moduleinfo->cmidnumber = '';
                //     $moduleinfo->instance = 0;
                //
                //     // Create course module
                //     $moduleinfo->coursemodule = add_course_module($moduleinfo);
                //
                //     // Create instance record
                //     $hvp = new \stdClass();
                //     $hvp->course = $courseid;
                //     $hvp->name = $name;
                //     $hvp->intro = '';
                //     $hvp->introformat = FORMAT_HTML;
                //     $hvp->timecreated = time();
                //     $hvp->timemodified = time();
                //     $hvp->json_content = '';
                //     $hvp->main_library_id= 0;
                //
                //     $hvp->id = $DB->insert_record('hvp', $hvp);
                //     $moduleinfo->instance = $hvp->id;
                //
                //     // Add to course_sections
                //     course_add_cm_to_section($courseid, $moduleinfo->coursemodule, $section);
                //
                //     // Process H5P file
                //         $fs = get_file_storage();
                //         $context = \context_module::instance($moduleinfo->coursemodule);
                //
                //         // Prepare file record
                //         $fileinfo = array(
                //             'contextid' => $context->id,
                //             'component' => 'mod_hvp',
                //             'filearea' => 'package',
                //             'itemid' => 0,
                //             'filepath' => '/',
                //             'filename' => $file->get_filename(),
                //         );
                //
                //         // Create file from the H5P package
                //         $fs->create_file_from_storedfile($fileinfo, $file);
                //
                //         // Process the H5P content
                //         $core = \mod_hvp\framework::instance('storage');
                //         $content_id = $core->savePackage(null, null, true);
                //
                //         // Update the HVP record with content ID
                //         $DB->set_field('hvp', 'main_library_id', $content_id, array('id' => $hvp->id));
                //
                //     return $moduleinfo->coursemodule;
                // };
                //
                // // Example usage:
                // try {
                //     $course_id = $package->courseid; // Replace with your course ID
                //     $activity_name = "My H5P Activity";
                //     $h5p_file_path = '/path/to/your/content.h5p';
                //     $section_number = 1; // Optional section number
                //
                //     $cm_id = $create_hvp_activity($course_id, $activity_name, $h5ps[0], $section_number);
                //     if ($cm_id) {
                //         echo "H5P activity created successfully with course module ID: " . $cm_id;
                //     }
                // } catch (Exception $e) {
                //     echo "Error creating H5P activity: " . $e->getMessage();
                // }

                foreach ($h5ps as $file) {
                    $moduleinfo = new \stdClass();
                    $moduleinfo->modulename = 'label';
                    $moduleinfo->name = 'H5P';
                    $moduleinfo->intro = '<div class="h5p-placeholder" contenteditable="false">@@PLUGINFILE@@/' . $file->get_filename() . '</div>';
                    $moduleinfo->introformat = FORMAT_HTML;
                    $moduleinfo->files = []; // $files; // geht so nicht
                    $moduleinfo = $create_or_update_module($moduleinfo);

                    $mod_context = \context_module::instance($moduleinfo->coursemodule);

                    // delete old files
                    $fs->delete_area_files($mod_context->id, 'mod_label', 'intro');
                    // readd them
                    $fileinfo = array(
                        'contextid' => $mod_context->id,
                        'component' => 'mod_label',
                        'filearea' => 'intro',
                        'itemid' => 0,
                        'filepath' => '/',
                        'filename' => $file->get_filename(),
                    );
                    $fs->create_file_from_storedfile($fileinfo, $file);

                    // $cm_id = create_hvp_activity($package->courseid, $activity_name, $h5p_file_path, $section_number);
                    // exit;

                    // $moduleinfo = new \stdClass();
                    // $moduleinfo->modulename = 'h5pactivity';
                    // $moduleinfo->name = $file->get_filename();
                    // $moduleinfo->grade = 0;
                    // $moduleinfo = $create_or_update_module($moduleinfo);
                    //
                    // $mod_context = \context_module::instance($moduleinfo->coursemodule);
                    // $fileinfo = array(
                    //     'contextid' => $mod_context->id,
                    //     'component' => 'mod_h5pactivity',
                    //     'filearea' => 'package',
                    //     'itemid' => 0,
                    //     'filepath' => '/',
                    //     'filename' => $file->get_filename(),
                    // );
                    //
                    // $fs->create_file_from_storedfile($fileinfo, $file);
                }
            }

            $files = $fs->get_area_files($context->id, 'block_edupublisher', 'pkg_item_dh_files', $content_item->id, 'itemid, filepath, filename', false);
            if ($files || trim($content_item->didaktische_hinweise)) {
                // hat didaktische hinweise

                $availability = json_encode((object)array('op' => '&', 'c' => array((object)array('type' => 'role', 'typeid' => 1, 'id' => (int)get_config('local_eduvidual', 'defaultorgroleteacher'))), 'showc' => array(false)));
                $section = $make_section([
                    'name' => ($i + 1) . '. Aktivität/Ressource: Didaktische Hinweise',
                    'summary' => nl2br(clean_param($content_item->didaktische_hinweise, PARAM_TEXT)),
                    'availability' => $availability,
                ]);

                if ($files) {
                    $moduleinfo = new \stdClass();
                    $moduleinfo->modulename = 'folder';
                    $moduleinfo->name = 'Dateien';
                    $moduleinfo = $create_or_update_module($moduleinfo);

                    $mod_context = \context_module::instance($moduleinfo->coursemodule);
                    // delete old files
                    $fs->delete_area_files($mod_context->id, 'mod_folder', 'content');
                    // readd them
                    foreach ($files as $file) {
                        $fileinfo = array(
                            'contextid' => $mod_context->id,
                            'component' => 'mod_folder',
                            'filearea' => 'content',
                            'itemid' => 0,
                            'filepath' => '/',
                            'filename' => $file->get_filename(),
                        );

                        $fs->create_file_from_storedfile($fileinfo, $file);
                    }
                }
            }
        }

        // delete old sections
        while ($section = array_shift($course_sections)) {
            course_delete_section($package->courseid, $section);
        }

        // delete old modules
        foreach ($course_modules_to_delete as $cm) {
            try {
                // schlägt im dev manchmal fehl, weil durch das testen die Aktivitäten nicht richtig angelegt wurden
                course_delete_module($cm->id);
            } catch (\moodle_exception $e) {
                // echo $e->getMessage();
            }
        }

        static::update_course_competencies($course->id);

        // TODO: sync to exacomp
        /*
        // coursetopics
        $courseCompetencies = \core_competency\api::list_course_competencies($course->id);
        $currentCompetencyIds = array_map(fn($entry) => $entry['competency']->get('id'), $courseCompetencies);
        $topicids = [];
        foreach ($currentCompetencyIds as $id) {
            while ($id && ($parent = $DB->get_record('competency', array('id' => $id)))) {
                $mapping = \local_komettranslator\api::get_copmetency_mapping($parent->id);
                if ($mapping && $mapping->type == 'topic') {
                    $source = $DB->get_field('block_exacompdatasources', 'id', array('source' => $mapping->sourceid));
                    if ($source) {
                        $topicid = $DB->get_field("block_exacomptopics", "id", array("source" => $source, "sourceid" => $mapping->itemid));
                        if ($topicid) {
                            $topicids[] = $topicid;
                        }
                    }
                    break;
                }
                $id = $parent->parentid;
            }
        }

        $topicids = array_unique($topicids);

        require_once $CFG->dirroot . '/blocks/exacomp/lib/lib.php';
        block_exacomp_set_coursetopics($course->id, $topicids, true);

        // activities

        $topicCompetencyIds = [];
        */

        rebuild_course_cache($course->id, true);
    }

    /**
     * Updates the competencies for a specific module.
     *
     * @param int $courseId
     * @param int $moduleId The module ID.
     * @param array $competencyIds Array of competency IDs to associate with the module.
     */
    static function update_module_competencies($courseId, $moduleId, array $competencyIds) {
        // Get current list of competencies linked to the module
        $currentCompetencies = \core_competency\api::list_course_module_competencies($moduleId);
        $currentCompetencyIds = array_map(fn($entry) => $entry['competency']->get('id'), $currentCompetencies);

        // Unlink competencies not in the provided list
        foreach ($currentCompetencyIds as $currentCompetencyId) {
            if (!in_array($currentCompetencyId, $competencyIds)) {
                \core_competency\api::remove_competency_from_course_module($moduleId, $currentCompetencyId);
            }
        }

        // Link each competency to the module if it's not already linked
        foreach ($competencyIds as $competencyId) {
            if (!in_array($competencyId, $currentCompetencyIds)) {
                // competency also needs to be added to course
                // this function makes sure, the competency is only added once
                \core_competency\api::add_competency_to_course($courseId, $competencyId);
                \core_competency\api::add_competency_to_course_module($moduleId, $competencyId);
            }
        }
    }

    /**
     * Sets the competencies for a course based on associated modules.
     *
     * @param int $courseId The course ID.
     */
    static function update_course_competencies($courseId) {
        global $DB;

        // Get all module IDs for the specified course
        $moduleIds = $DB->get_fieldset_select('course_modules', 'id', 'course = ?', [$courseId]);

        // Get all competencies linked to the provided modules
        $usedCompetencyIds = [];
        foreach ($moduleIds as $moduleId) {
            $moduleCompetencies = \core_competency\api::list_course_module_competencies($moduleId);
            $moduleCompetencyIds = array_map(fn($entry) => $entry['competency']->get('id'), $moduleCompetencies);
            $usedCompetencyIds = array_merge($usedCompetencyIds, $moduleCompetencyIds);
        }
        $usedCompetencyIds = array_unique($usedCompetencyIds);

        // Get current list of competencies linked to the course
        $courseCompetencies = \core_competency\api::list_course_competencies($courseId);
        $currentCompetencyIds = array_map(fn($entry) => $entry['competency']->get('id'), $courseCompetencies);

        // Unlink competencies not used in the course
        foreach ($currentCompetencyIds as $currentCompetencyId) {
            if (!in_array($currentCompetencyId, $usedCompetencyIds)) {
                \core_competency\api::remove_competency_from_course($courseId, $currentCompetencyId);
            }
        }

        // not needed: each module competency has to be in the course in the first place!
        // Link each competency to the course if it's used but not already linked
        // foreach ($usedCompetencyIds as $competencyId) {
        //     if (!in_array($competencyId, $currentCompetencyIds)) {
        //         \core_competency\api::add_co($courseId, $competencyId);
        //     }
        // }
    }
}
