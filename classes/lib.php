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
    public static function add_to_context($context) {
        global $DB;
        $count = $DB->count_records('block_instances', array('blockname' => 'edupublisher', 'parentcontextid' => $context->id));
        if ($count == 0) {
            // Create edupublisher-block in targetcourse.
            $blockdata = (object) array(
                'blockname' => 'edupublisher',
                'parentcontextid' => $context->id,
                'showinsubcontexts' => 1,
                'requiredbytheme' => 0,
                'pagetypepattern' => 'course-view-*',
                'defaultregion' => 'side-post',
                'defaultweight' => -10,
                'configdata' => '',
                'timecreated' => time(),
                'timemodified' => time(),
            );
            $DB->insert_record('block_instances', $blockdata);
        }
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
            if (!$f) continue;
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
        if (!isset($reply)) $reply = array();
        //print_r($courseids); print_r($userids); echo $roleid;
        if (!is_array($courseids)) $courseids = array($courseids);
        if (!is_array($userids)) $userids = array($userids);
        // Retrieve the manual enrolment plugin.
        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            return false;
        }
        $failures = 0;
        foreach ($courseids AS $courseid) {
            // Check if course exists.
            $course = $DB->get_record('course', array('id' => $courseid), '*', IGNORE_MISSING);
            $context = \context_course::instance($course->id);
            //$course = get_course($courseid);
            if (empty($course->id)) continue;
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
                    'notifyall' => 0
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
                foreach ($userids AS $userid) {
                    if (!empty($remove)) {
                        role_unassign($roleid, $userid, $context->id);
                        // If this was the last role, we unenrol completely
                        $roles = get_user_roles($context, $userid);
                        $foundatleastone = false;
                        foreach($roles AS $role) {
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
        $definition = array(
            'default' => array(
                'suppresscomment' => array('type' => 'select', 'datatype' => PARAM_INT, 'hidden_except_maintainer' => 1, 'options' => array(
                    '0' => get_string('no'), '1' => get_string('yes')
                ), 'donotstore' => 1),
                'title' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => 1, 'searchable' => 1),
                'licence' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'options' => array(
                    //'Public Domain' => 'Public Domain',
                    'cc-0' => 'cc-0',
                    'cc-by' => 'cc-by', 'cc-by-sa' => 'cc-by-sa',
                    'cc-by-nc' => 'cc-by-nc', 'cc-by-nc-sa' => 'cc-by-nc-sa',
                    'other' => get_string('default_licenceother', 'block_edupublisher'),
                ), 'required' => 1, 'searchable' => 1),
                'authorname' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => 1, 'searchable' => 1),
                'authormail' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => 1, 'searchable' => 1),
                'authormailshow' => array('type' => 'select', 'datatype' => PARAM_INT, 'default' => 1, 'options' => array(
                    '1' => get_string('yes'), '0' => get_string('no')
                    )
                ),
                'origins' => array('type' => 'select', 'multiple' => 1, 'datatype' => PARAM_INT),
                'summary' => array('type' => 'editor', 'datatype' => PARAM_RAW, 'required' => 1, 'searchable' => 1),
                'image' => array('type' => 'filemanager', 'accepted_types' => 'image', 'required' => 1),
                'subjectarea' => array('type' => 'select', 'multiple' => 1, 'datatype' => PARAM_TEXT,
                    'required' => 1, 'splitcols' => 1, 'options' => array(
                        'arts' => get_string('default_subjectarea_arts', 'block_edupublisher'),
                        'economics' => get_string('default_subjectarea_economics', 'block_edupublisher'),
                        'geography' => get_string('default_subjectarea_geography', 'block_edupublisher'),
                        'history' => get_string('default_subjectarea_history', 'block_edupublisher'),
                        'informatics' => get_string('default_subjectarea_informatics', 'block_edupublisher'),
                        'languages' => get_string('default_subjectarea_languages', 'block_edupublisher'),
                        'mathematics' => get_string('default_subjectarea_mathematics', 'block_edupublisher'),
                        'naturalsciences' => get_string('default_subjectarea_naturalsciences', 'block_edupublisher'),
                        'philosophy' => get_string('default_subjectarea_philosophy', 'block_edupublisher'),
                        'physicaleducation' => get_string('default_subjectarea_physicaleducation', 'block_edupublisher'),
                        'other' => get_string('default_subjectarea_other', 'block_edupublisher'),
                    )
                ),
                'schoollevel' => array('type' => 'select', 'multiple' => 1, 'datatype' => PARAM_TEXT,
                    'required' => 1, 'splitcols' => 1, 'options' => array(
                        'primary' => get_string('default_schoollevel_primary', 'block_edupublisher'),
                        'secondary_1' => get_string('default_schoollevel_secondary_1', 'block_edupublisher'),
                        'secondary_2' => get_string('default_schoollevel_secondary_2', 'block_edupublisher'),
                        'tertiary' => get_string('default_schoollevel_tertiary', 'block_edupublisher'),
                    )
                ),
                'tags' => array('type' => 'tags', 'datatype' => PARAM_TEXT, 'multiple' => 1, 'tagparams' => array('itemtype' => 'packages', 'component' => 'block_edupublisher'), 'searchable' => 1),
                // Hidden elements
                'active' => array('type' => 'hidden', 'datatype' => PARAM_BOOL),
                'published' => array('type' => 'hidden', 'datatype' => PARAM_INT, 'default' => 0),
                'exacompsourceids' => array('type' => 'hidden', 'multiple' => 1, 'datatype' => PARAM_INT),
                'exacomptitles' => array('type' => 'hidden', 'multiple' => 1, 'datatype' => PARAM_TEXT),
                'exacompdatasources' => array('type' => 'hidden', 'multiple' => 1, 'datatype' => PARAM_TEXT),
                'imageurl' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                'publishas' => array('type' => 'hidden', 'datatype' => PARAM_BOOL, 'default' => 1),
            ),
            'etapas' => array(
                'active' => array('type' => 'hidden', 'datatype' => PARAM_BOOL),
                //'erprobungen' => array('type' => 'filemanager', 'multiple' => 1, 'hidden_on_init' => 1, 'maxfiles' => 20, 'accepted_types' => 'document'),
                'ltiurl' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                'lticartridge' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                'ltisecret' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
                'publishas' => array('type' => 'boolean', 'datatype' => PARAM_BOOL),
                'published' => array('type' => 'hidden', 'datatype' => PARAM_INT, 'default' => 0),
                'status' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'default' => 'inspect',
                    'hidden_on_init' => true, 'options' => array(
                        'inspect' => get_string('etapas_status_inspect', 'block_edupublisher'),
                        'eval' => get_string('etapas_status_eval', 'block_edupublisher'),
                        'public' => get_string('etapas_status_public', 'block_edupublisher'),
                    )
                ),
                'type' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'default' => 'lesson', 'required' => 1, 'searchable' => 1, 'options' => array(
                    'lesson' => 'Unterricht', 'collection' => 'Beispielsammlung', 'learningroute' => 'Lernstrecke')
                ),
                'subtype' => array('type' => 'select', 'datatype' => PARAM_TEXT,
                    'hidden_except_maintainer' => true, 'searchable' => 1, 'default' => 'etapa',
                    'options' => array(
                        'etapa' => 'eTapa', 'digi.komp 4' => 'digi.komp 4', 'digi.komp 8' => 'digi.komp 8',
                        'digi.komp 12' => 'digi.komp 12'
                    )
                ),
                'gegenstand' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => 1, 'searchable' => 1),
                //'vonschule' => array('type' => 'text', 'datatype' => PARAM_TEXT),
                'schulstufe' => array('type' => 'select', 'datatype' => PARAM_TEXT,
                    'multiple' => 1, 'required' => 1, 'splitcols' => 1, 'options' => array(
                        1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5,
                        6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11,
                        12 => 12, 13 => 13
                    )
                ),
                'kompetenzen' => array('type' => 'static', 'datatype' => PARAM_RAW, 'required' => 0, 'default' => get_string('etapas_kompetenzen_help', 'block_edupublisher'), 'searchable' => 1),
                'stundenablauf' => array('type' => 'editor', 'datatype' => PARAM_RAW, 'required' => 1, 'searchable' => 1),
                'vorkenntnisse' => array('type' => 'editor', 'datatype' => PARAM_RAW, 'required' => 1, 'searchable' => 1),
                'voraussetzungen' => array('type' => 'editor', 'datatype' => PARAM_RAW, 'required' => 1, 'searchable' => 1),
                'zeitbedarf' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'options' => array(
                    '01:00' => '01:00', '02:00' => '02:00', '03:00' => '03:00'
                )),
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
                    )
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
                    )
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
                    )
                ),
                'educationallevel' => array('type' => 'select', 'datatype' => PARAM_TEXT,
                    'multiple' => 1, 'required' => 1, 'options' => array(
                        1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5,
                        6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11
                    )
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
                    'internal' => get_string('commercial_validateinternal', 'block_edupublisher'))
                ),
            ),
        );
        global $CFG, $package, $MODE_SHOW_FORM;
        if (isset($package)) {
            $package->set(1, 'publishash', 'default');
            // Customize definition to package.
            $channels = array_keys($definition);
            foreach($channels AS $channel) {
                // If not set in package check for POST.
                if (empty($package->get('publishas', $channel)) && !empty(optional_param($channel . '_publishas', 0, PARAM_INT))) {
                    $package->set(1, 'publishas', $channel);
                } elseif(empty($package->get('publishas', $channel))) {
                    $package->set(0, 'publishas', $channel);
                }
                $fields = array_keys($definition[$channel]);
                foreach($fields AS $field) {
                    $ofield = &$definition[$channel][$field];
                    if (!empty($ofield['required'])) {
                        //echo $channel . '_' . $field . " is " . (!empty($ofield['required']) ? 'required' : 'not required') . " and is " . $package->{$channel . '_' . $field} ."\n";
                    }
                    if (isset($ofield['hidden_on_init']) && $ofield['hidden_on_init']) {
                        if (empty($package->get('id'))) {
                            $ofield['type'] = 'hidden';
                        }
                    }
                    if ($channel == 'default' && $field == 'origins') {
                        $possible_origins = $package->load_possible_origins();
                        $options = array();
                        foreach($possible_origins AS $po) {
                            if (empty($po->id)) continue;
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
                                : $CFG->wwwroot . '/pluginfile.php/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . '/' . $file->get_filename()
                                //: '' . moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename())
                            ;
                break;
            }
        }
        return (object) array('imagename' => $imagename, 'imagepath' => $imagepath);
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
            foreach($ids AS $id) {
                $context = \context_course::instance($id);
                $canedit = has_capability($capability, $context);
                if (!$canedit) unset($courses[$id]);
            }
            return $courses;
        }
    }
    /**
     * Hold the path of visited packages in cache and
     * receive danube.ai-recommendations.
     * @param packageid that is visited.
     */
    public static function get_danubeai_recommendations($packageid = 0) {
        $danubeai_apikey = get_config('block_edupublisher', 'danubeai_apikey');
        if (!empty($danubeai_apikey)) {
            $cache = \cache::make('block_edupublisher', 'packagepath');
            $path = explode(',', $cache->get('path'));
            if (!empty($packageid)) {
                $path[] = $packageid;
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
    /**
     * Gets an existing package by its courseid.
     * @param courseid the courseid.
     * @param strictness by default MUST_EXIST
     * @param createifmissing if no package is found for this course, create package?
     * @return object of type \block_edupublisher\package or null
     */
    public static function get_package_by_courseid($courseid, $strictness = MUST_EXIST, $createifmissing = false) {
        global $DB;
        $item = $DB->get_record('block_edupublisher_packages', array('course' => $courseid), '*', $strictness);
        if (!empty($item->id)) {
            return new \block_edupublisher\package($item->id);
        } else if($createifmissing) {
            $package = self::get_package_from_course($courseid);
            $package->store_package((object)[]);
            return $package;
        }
    }
    /**
     * Creates an empty package and fills with data from course.
     * This is used when we create a new package.
    **/
    public static function get_package_from_course($courseid){
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
     * Load all roles of a user in a context and check if it contains a given roleid.
     * @param context the context to check.
     * @param roleid the roleid to search for.
     * @param userorid the user as integer or object. If non given, use $USER->id.
     */
    public static function has_role($context, $roleid, $userorid = null) {
        global $USER;
        if (is_object($userorid)) $userid = $userorid->id;
        elseif (is_numeric($userorid)) $userid = $userorid;
        else $userid = $USER->id;
        $roles = \get_user_roles($context, $userid);
        foreach ($roles as $role) {
            if ($role->roleid == $roleid) return true;
        }
        return false;
    }
    /**
     * @return true if user is sysadmin
    **/
    public static function is_admin() {
        $sysctx = \context_system::instance();
        return has_capability('moodle/site:config', $sysctx);
    }
    /**
     * @param (optional) array of channels we want to check
     * @return true if user is a maintainer
    **/
    public static function is_maintainer($channels = array()) {
        if (\block_edupublisher\lib::is_admin()) return true;

        $category = get_config('block_edupublisher', 'category');
        $context = \context_coursecat::instance($category);
        $maintainer_default = has_capability('block/edupublisher:managedefault', $context);
        $maintainer_etapas = has_capability('block/edupublisher:manageetapas', $context);
        $maintainer_eduthek = has_capability('block/edupublisher:manageeduthek', $context);

        if (count($channels) == 0) {
            return $maintainer_default || $maintainer_etapas || $maintainer_eduthek;
        }
        if (in_array('default', $channels) && $maintainer_default) return true;
        if (in_array('etapas', $channels) && $maintainer_etapas) return true;
        if (in_array('eduthek', $channels) && $maintainer_eduthek) return true;
        return false;
    }
    /**
     * Indicates if the current user is acting as a publisher for commercial content.
     * @param publisherid (optional) if user is co-worker of a specific publisher.
     * @return true if is publisher or site-admin.
     */
    public static function is_publisher($publisherid = 0) {
        if (\block_edupublisher\lib::is_admin()) return true;
        global $DB, $USER;
        if (empty($publisherid)) {
            $chk = $DB->get_record('block_edupublisher_pub_user', array('userid' => $USER->id));
        } else {
            $chk = $DB->get_record('block_edupublisher_pub_user', array('userid' => $USER->id, 'publisherid' => $publisherid));
        }
        return (!empty($chk->id) && $chk->id > 0);
    }
    /**
     * Log that a user visited a course-page of a package.
     * @param packageid that is visited.
     * @param action String, either 'viewed', 'enrolled', 'unenrolled' or 'cloned'
     */
    public static function log_user_visit($packageid, $action) {
        if (empty($packageid)) return;
        // Ensure the action is a valid value.
        if (!in_array($action, array('viewed', 'enrolled', 'unenrolled', 'cloned'))) return;

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
            if (!empty($lastrecord->packageid) && $lastrecord->packageid == $packageid) return;
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
            if (count($channels) == 0 && empty($this->get('active','default'))) {
                $channels[] = 'default';
            }
        }

        // Prepare e-Mail
        $fromuser = \core_user::get_support_user();
        $possiblechannels = array('default', 'eduthek', 'etapas');
        foreach($channels AS $channel) {
            if (!in_array($channel, $possiblechannels)) continue;

            $this->_wwwroot = $CFG->wwwroot;
            $messagehtml = $OUTPUT->render_from_template(
                'block_edupublisher/package_' . $channel . '_notify',
                $this->metadata
            );
            $subject = get_string($channel . '__mailsubject' , 'block_edupublisher');
            $messagehtml = enhance_mail_body($subject, $messagehtml);
            $messagetext = html_to_text($messagehtml);
            $category = get_config('block_edupublisher', 'category');
            $context = \context_coursecat::instance($category);
            $recipients = get_users_by_capability($context, 'block/edupublisher:manage' . $channel, '', '', '', 10);
            foreach($recipients AS $recipient) {
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
                'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->get('course'),
            );
            echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
            return;
        }

        $course = $DB->get_record('course', array('id' => $package->get('course')), '*', MUST_EXIST);
        $ctx = \context_course::instance($course->id);

        $fs = \get_file_storage();
        $file = $fs->get_file($ctx->id, 'block_edupublisher', 'coursebackup', 0, '/', 'coursebackup.mbz');

        if (!$file) {
            $alert = array(
                'content' => \get_string('coursebackup:notfound', 'block_edupublisher'),
                'type' => 'danger',
                'url' => $CFG->wwwroot . '/course/view.php?id=' . $package->get('course'),
            );
            echo $OUTPUT->render_from_template('block_edupublisher/alert', $alert);
            return;
        }

        $fp = \get_file_packer('application/vnd.moodle.backup');
        $backuptempdir = \make_backup_temp_directory('edupublisher' . $package->get('id'));
        if (!is_dir($backuptempdir) || !file_exists($backuptempdir . '/moodle_backup.xml')) {
            $file->extract_to_pathname($fp, $backuptempdir);
        }

        return 'edupublisher' . $package->get('id');
    }
    /**
     * Grants or revokes a role from a course.
     * @param courseids array with courseids
     * @param userids array with userids
     * @param role -1 to remove user, number of role or known identifier (defaultroleteacher, defaultrolestudent) to assign role.
     */
    public static function role_set($courseids, $userids, $role) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/enrol/manual/lib.php");
        if ($role == 'defaultroleteacher') $role = get_config('block_edupublisher', 'defaultroleteacher');
        if ($role == 'defaultrolestudent') $role = get_config('block_edupublisher', 'defaultrolestudent');
        if (empty($role)) return;

        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            return false;
        }
        foreach ($courseids AS $courseid) {
            // Check if course exists.
            $course = get_course($courseid);
            if (empty($course->id)) continue;
            // Check manual enrolment plugin instance is enabled/exist.
            $instance = null;
            $enrolinstances = enrol_get_instances($courseid, false);
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
                    'roleid' => get_config('block_edupublisher', 'defaultrolestudent'),
                    'enrolperiod' => 0,
                    'expirynotify' => 0,
                    'expirytreshold' => 0,
                    'notifyall' => 0
                );
                $emp = new enrol_manual_plugin();
                $instance = $emp->add_instance($course, $fields);
            }
            if ($instance->status == 1) {
                // It is inactive - we have to activate it!
                $data = (object)array('status' => 0);
                $emp = new enrol_manual_plugin();
                $emp->update_instance($instance, $data);
                $instance->status = $data->status;
            }
            foreach ($userids AS $userid) {
                if ($role == -1) {
                    $enrol->unenrol_user($instance, $userid);
                } else {
                    $enrol->enrol_user($instance, $userid, $role, 0, 0, ENROL_USER_ACTIVE);
                }
            }
        }
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
                if ($instance->enrol != 'guest') continue;
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
    public static function uses_eduvidual(){
        global $CFG;
        return file_exists($CFG->dirroot . '/local/eduvidual/version.php');
    }
}
