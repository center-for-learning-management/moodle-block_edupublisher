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
**/

defined('MOODLE_INTERNAL') || die;

/**
 * Ordering will be the same like in form.
 * fields are deifined as follows
 * definition > channel > field > type == type of form used by moodleform
 * definition > channel > field > datatype == expected datatype as used in moodleform
 * definition > channel > field > multiple == 1 if multiple element, 0 if single element
 * definition > channel > field > options == list of options if is type select
 * definition > channel > field > [filemanageroptions] == each option of filemanager as field, maxfiles, maxbytes ...
 * definition > channel > field > requireschannel
**/

$definition = array(
    'default' => array(
        'suppresscomment' => array('type' => 'select', 'datatype' => PARAM_INT, 'hidden_except_maintainer' => 1, 'options' => array(
            '0' => get_string('no'), '1' => get_string('yes')
        ), 'donotstore' => 1),
        'title' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => 1),
        'licence' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'options' => array(
            'Public Domain' => 'Public Domain',
            'cc-by' => 'cc-by', 'cc-by-sa' => 'cc-by-sa',
            'cc-by-nc' => 'cc-by-nc', 'cc-by-nc-sa' => 'cc-by-nc-sa',
            'other' => get_string('default_licenceother', 'block_edupublisher'),
        ), 'required' => 1),
        'authorname' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => 1),
        'authormail' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => 1),
        'authormailshow' => array('type' => 'select', 'datatype' => PARAM_INT, 'default' => 1, 'options' => array(
            '1' => get_string('yes'), '0' => get_string('no')
            )
        ),
        'origins' => array('type' => 'select', 'multiple' => 1, 'datatype' => PARAM_INT),
        'sourcecourse' => array('type' => 'hidden', 'datatype' => PARAM_INT),
        'summary' => array('type' => 'editor', 'datatype' => PARAM_RAW, 'required' => 1),
        'image' => array('type' => 'filemanager', 'accepted_types' => 'image', 'required' => 1),
        'tags' => array('type' => 'text', 'datatype' => PARAM_TEXT),
        // Hidden elements
        'active' => array('type' => 'hidden', 'datatype' => PARAM_BOOL),
        'published' => array('type' => 'hidden', 'datatype' => PARAM_INT, 'default' => 0),
        'exacompsourceids' => array('type' => 'hidden', 'multiple' => 1, 'datatype' => PARAM_INT),
        'exacomptitles' => array('type' => 'hidden', 'multiple' => 1, 'datatype' => PARAM_TEXT),
        'exacompdatasources' => array('type' => 'hidden', 'multiple' => 1, 'datatype' => PARAM_TEXT),
        'imageurl' => array('type' => 'hidden', 'datatype' => PARAM_TEXT),
        'publishas' => array('type' => 'boolean', 'datatype' => PARAM_BOOL, 'default' => 1),
    ),
    'etapas' => array(
        'active' => array('type' => 'hidden', 'datatype' => PARAM_BOOL),
        'erprobungen' => array('type' => 'filemanager', 'multiple' => 1, 'hidden_on_init' => 1, 'maxfiles' => 20, 'accepted_types' => 'document'),
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
        'type' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'default' => 'lesson', 'required' => 1, 'options' => array(
            'lesson' => 'Unterricht', 'collection' => 'Beispielsammlung', 'learningroute' => 'Lernstrecke')
        ),
        'subtype' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'hidden_except_maintainer' => true, 'default' => 'etapa', 'options' => array(
            'etapa' => 'eTapa', 'digi.komp 4' => 'digi.komp 4', 'digi.komp 8' => 'digi.komp 8', 'digi.komp 12' => 'digi.komp 12')
        ),
        'gegenstand' => array('type' => 'text', 'datatype' => PARAM_TEXT, 'required' => 1),
        'vonschule' => array('type' => 'text', 'datatype' => PARAM_TEXT),
        'schulstufe' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'multiple' => 1, 'required' => 1, 'options' => array(
            1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5,
            6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11,
            12 => 12, 13 => 13)
        ),
        'kompetenzen' => array('type' => 'editor', 'datatype' => PARAM_RAW, 'required' => 1),
        'stundenablauf' => array('type' => 'editor', 'datatype' => PARAM_RAW, 'required' => 1),
        'vorkenntnisse' => array('type' => 'editor', 'datatype' => PARAM_RAW),
        'voraussetzungen' => array('type' => 'editor', 'datatype' => PARAM_RAW),
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
        'type' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'multiple' => 1, 'required' => 1, 'options' => array(
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
        )),
        'schooltype' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'multiple' => 1, 'required' => 1, 'options' => array(
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
        )),
        'topic' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'multiple' => 1, 'required' => 1, 'options' => array(
            'T010' => 'Bautechnik',
            'T020' => ' Berufsorientierung',
            'T030' => ' Betriebswirtschaft',
            'T040' => ' Bewegung und Sport',
            'T050' => ' Bildnerische Erziehung',
            'T060' => ' Bildung für nachhaltige Entwicklung',
            'T070' => ' Biologie',
            'T080' => ' Biologie und Umweltkunde',
            'T090' => ' Bosnisch, Kroatisch, Serbisch',
            'T100' => ' Chemie',
            'T110' => ' Darstellende Geometrie',
            'T120' => ' Darstellendes Spiel',
            'T130' => ' Design',
            'T140' => ' Deutsch',
            'T150' => ' Didaktik und Pädagogik',
            'T160' => ' Elektrotechnik',
            'T170' => ' Englisch',
            'T180' => ' Ernährung',
            'T190' => ' Ernährung & Haushalt',
            'T200' => ' Europapolitische Bildung',
            'T210' => ' Fachkunde, Fachtheorie, Fachpraxis',
            'T220' => ' Französisch',
            'T230' => ' Gender und Diversität',
            'T240' => ' Geografie',
            'T250' => ' Geografie und Wirtschaftskunde',
            'T260' => ' Geometrisches Zeichen',
            'T270' => ' Geschichte & Sozialkunde, Politische Bildung',
            'T280' => ' Geschichte, Politische Bildung',
            'T290' => ' Gesundheitserziehung',
            'T300' => ' Globales Lernen',
            'T310' => ' Griechisch',
            'T320' => ' Informatik',
            'T330' => ' Interkulturelles Lernen',
            'T340' => ' Italienisch',
            'T350' => ' Kommunikation',
            'T360' => ' Kroatisch',
            'T370' => ' Kunst & Kreativität',
            'T380' => ' Latein',
            'T390' => ' Leseerziehung',
            'T400' => ' Marketing',
            'T410' => ' Maschinenbau',
            'T420' => ' Mathematik',
            'T430' => ' Mechanik, Mechatronik',
            'T440' => ' Digitale Grundbildung, Medienbildung',
            'T450' => ' Musik',
            'T460' => ' Naturwissenschaften',
            'T470' => ' Office- & Informationsmanagement',
            'T480' => ' Physik',
            'T490' => ' Politische Bildung',
            'T500' => ' Polnisch',
            'T520' => ' Projektunterricht',
            'T510' => ' Projektmanagement',
            'T530' => ' Psychologie & Philosophie',
            'T540' => ' Qualitätsmanagement',
            'T550' => ' Rechnungswesen',
            'T560' => ' Religion',
            'T570' => ' Russisch',
            'T580' => ' Sachunterricht',
            'T590' => ' Schach',
            'T600' => ' Serbisch',
            'T610' => ' Sexualerziehung',
            'T620' => ' Slowakisch',
            'T630' => ' Slowenisch',
            'T640' => ' Soziales Lernen',
            'T650' => ' Spanisch',
            'T660' => ' Technisches und Textiles Werken',
            'T670' => ' Tourismus',
            'T680' => ' Tschechisch',
            'T690' => ' Umweltbildung',
            'T700' => ' Ungarisch',
            'T710' => ' Verkehrserziehung',
            'T720' => ' Volkswirtschaft',
            'T730' => ' Vorschulstufe',
            'T740' => ' Werkstätte',
            'T750' => ' Wirtschaft und Recht',
            'T760' => ' Wirtschafts-& Verbraucherbildung',
            'T999' => ' Sonstiges',
        )),
        'educationallevel' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'multiple' => 1, 'required' => 1, 'options' => array(
            1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5,
            6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11)
        ),
    ),
    'commercial' => array(
        'active' => array('type' => 'hidden', 'datatype' => PARAM_BOOL),
        'publishas' => array('type' => 'boolean', 'datatype' => PARAM_BOOL, 'default' => 1),
        'published' => array('type' => 'hidden', 'datatype' => PARAM_INT, 'default' => 0),
        'publisher' => array('type' => 'select', 'datatype' => PARAM_INT, 'options' => array()),
        'shoplink' => array('type' => 'url', 'datatype' => PARAM_TEXT),
        'validation' => array('type' => 'select', 'datatype' => PARAM_TEXT, 'options' => array(
            'external' => get_string('commercial_validateexternal', 'block_edupublisher'),
            'internal' => get_string('commercial_validateinternal', 'block_edupublisher'))
        ),
    ),
);
