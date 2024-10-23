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

require_once($CFG->libdir . "/formslib.php");

class package_edit_form extends \moodleform {
    static $accepted_types = '';
    static $areamaxbytes = 10485760;
    static $maxbytes = 1024 * 1024;
    static $maxfiles = 1;
    static $subdirs = 0;

    function get_channel_definition() {
        $channels = \block_edupublisher\lib::get_channel_definition();

        $ALLOW_COMMERCIAL = \block_edupublisher\lib::is_publisher();
        if (!$ALLOW_COMMERCIAL) {
            unset($channels['commercial']);
        }

        $package = $this->_customdata['package'];
        if (!$package || !$package->get('publishas', 'commercial')) {
            unset($channels['commercial']);
        }
        if (!$package || !$package->get('publishas', 'eduthek')) {
            unset($channels['eduthek']);
        }

        return $channels;
    }

    private function get_package(): ?\block_edupublisher\package {
        return $this->_customdata['package'] ?? null;
    }

    function definition() {
        global $DB, $USER;

        $editoroptions = array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 0,
            'changeformat' => 0, 'context' => null, 'noclean' => 0,
            'trusttext' => 0, 'enable_filemanagement' => false);

        $mform = $this->_form;
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'active', 0);
        $mform->setType('active', PARAM_INT);
        // $mform->addElement('hidden', 'cantriggeractiveetapas', 0);
        // $mform->setType('cantriggeractiveetapas', PARAM_INT);
        $mform->addElement('hidden', 'course', 0);
        $mform->setType('course', PARAM_INT);
        $mform->addElement('hidden', 'sourcecourse', 0);
        $mform->setType('sourcecourse', PARAM_INT);
        $mform->addElement('hidden', 'userid', 0);
        $mform->setType('userid', PARAM_INT);
        /*
        $mform->addElement('hidden', '__possible_origins', '');
        $mform->setType('__possible_origins', PARAM_RAW);
        */

        $channels = $this->get_channel_definition();

        $stringman = get_string_manager();
        foreach ($channels as $channel => $fields) {
            if ($channel != 'etapas' && $channel != 'eduthekneu') {
                $label = $this->get_label($channels, $channel, 'publish_as', ucfirst($channel), $stringman);
                $mform->addElement('header', $channel . '_publish_as', $label);
                $mform->setExpanded($channel . '_publish_as');
                if ($this->has_channel_description($channel, $stringman)) {
                    $mform->addElement('html', '<p>' . get_string($channel . '__description', 'block_edupublisher') . '</p>');
                }
            }

            foreach ($fields as $_field => $field) {
                if ($_field == 'publishas') {
                    continue;
                }

                $required = isset($field['required']) && $field['required'];
                $hiddenexceptmaintainer = isset($field['hidden_except_maintainer']) && $field['hidden_except_maintainer'];
                if ($hiddenexceptmaintainer && !\block_edupublisher\lib::is_maintainer()) {
                    continue;
                }

                $label = $field['label'] ?? $this->get_label($channels, $channel, $_field, $_field, $stringman, $required);

                if ($_field == 'kompetenzen') {
                    $package = $this->get_package();

                    if (!$package) {
                        // $value = 'Bitte speichern Sie zuerst, bevor Kompetenzen ausgewählt werden können';
                        $value = \local_displace\competencylib::render_competency_selector(0, 0);
                    } else {
                        $frameworkid = 0;


                        ob_start();
                        echo output::render_competencylist($package, true);

                        ?>
                        <a class="btn btn-secondary btn-select-competencies" target="_blank"
                           href="<?= (new \moodle_url('/local/displace/competency/coursecompetenciesadd.php?courseid=' . $package->courseid . '&frameworkid=' . $frameworkid, ['courseid' => $package->courseid]))->out() ?>">
                            Kompetenzen ändern
                        </a>
                        <?php
                        $value = ob_get_clean();

                        $value .= \local_displace\competencylib::render_competency_selector($package->courseid, 0, true);
                    }
                    $mform->addElement($field['type'], 'kompetenzen_output', $label, $value);
                    continue;
                }

                $addedfield = null;
                switch ($field['type']) {
                    case 'editor':
                        $addedfield = $mform->addElement($field['type'], $channel . '_' . $_field, $label, $editoroptions);
                        break;
                    case 'filemanager':
                        /* Drag & Drop did not work properly.
                        if ($channel == 'default' && $_field == 'image') {
                            $course = get_course($COURSE->id);
                            $courseimage = \block_edupublisher\lib::get_course_image($course);
                            if (!empty($courseimage->imagepath)) {
                                $label .= '<center><img src="' . $courseimage->imagepath . '" style="width: 50%;" /></center>';
                            }
                        }
                        */
                        $addedfield = $mform->addElement($field['type'], $channel . '_' . $_field, $label, null,
                            array(
                                'accepted_types' => (!empty($field['accepted_types']) ? $field['accepted_types'] : ''),
                                'areamaxbytes' => (!empty($field['areamaxbytes']) ? $field['areamaxbytes'] : self::$areamaxbytes),
                                'maxbytes' => (!empty($field['maxbytes']) ? $field['maxbytes'] : self::$maxbytes),
                                'maxfiles' => (!empty($field['maxfiles']) ? $field['maxfiles'] : self::$maxfiles),
                                'subdirs' => (!empty($field['subdirs']) ? $field['subdirs'] : self::$subdirs),
                                //, 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL
                            )
                        );
                        break;
                    case 'hidden':
                        // We do not need hidden fields!!
                        $fieldname = $channel . '_' . $_field;
                        if (!empty($field['multiple']))
                            $fieldname .= '[]';
                        //$addedfield = $mform->addElement($field['type'], $fieldname, isset($field['default']) ? $field['default'] : NULL);
                        break;
                    case 'select':
                        $options = (!empty($field['options'])) ? $field['options'] : array();
                        if ($channel == 'commercial' && $_field == 'publisher') {
                            if (\block_edupublisher\lib::is_admin()) {
                                $allpublishers = $DB->get_records_sql('SELECT * FROM {block_edupublisher_pub} ORDER BY name ASC', array());
                            } else {
                                $allpublishers = $DB->get_records_sql('SELECT ep.* FROM {block_edupublisher_pub} ep, {block_edupublisher_pub_user} epu WHERE ep.id=epu.publisherid AND epu.userid=? ORDER BY name ASC', array($USER->id));
                            }
                            foreach ($allpublishers as $publisher) {
                                if (\block_edupublisher\lib::is_admin()) {
                                    $chk = $DB->get_record('block_edupublisher_pub_user', array('publisherid' => $publisher->id, 'userid' => $USER->id));
                                    if (!$chk)
                                        $publisher->name = '! ' . $publisher->name;
                                }
                                $options[$publisher->id] = $publisher->name;
                            }
                        }
                        if ($channel == 'default' && $_field == 'licence' && !\block_edupublisher\lib::is_publisher()) {
                            unset($options['other']);
                        }
                        $addedfield = $mform->addElement('select', $channel . '_' . $_field, $label, $options);
                        if (!empty($field['multiple'])) {
                            $addedfield->setMultiple(true);
                            /*
                            // In case of multiple fields we need to set the values from package.
                            global $package;
                            $selected = [];
                            foreach ($options as $option => $optionlabel) {
                                if (!empty($package->get("{$_field}_{$option}", $channel))) {
                                    $selected[] = $option;
                                }
                            }
                            print_r($options);
                            print_r($selected);
                            $addedfield->setSelected($channel . '_' . $_field, $selected);
                            */
                        }
                        break;
                    case 'static':
                        $default = !empty($field['default']) ? $field['default'] : '';
                        $addedfield = $mform->addElement($field['type'], $channel . '_' . $_field, $label, $default);
                        break;
                    case 'tags':
                        if (empty($field['tagparams'])) {
                            $field['tagparams'] = array();
                        }
                        if (empty($field['tagparams']['itemtype'])) {
                            $field['tagparams']['itemtype'] = 'packages';
                        }
                        if (empty($field['tagparams']['component'])) {
                            $field['tagparams']['component'] = 'block_edupublisher';
                        }
                        $addedfield = $mform->addElement($field['type'], $channel . '_' . $_field, $label, $field['tagparams']);
                        break;
                    case 'text':
                    case 'url':
                        $addedfield = $mform->addElement($field['type'], $channel . '_' . $_field, $label, array('type' => $field['type']));
                        break;
                    case 'radio':
                        $inputs = [];
                        foreach ($field['options'] as $value => $option) {
                            $inputs[] = $mform->createElement('radio', $channel . '_' . $_field, '', $option, $value);
                        }

                        // add empty default value, notwendig, wenn das formular erneut angezeigt wird, dass der Wert ungesetzt ist
                        $mform->addElement('html', '<input type="hidden" name="' . $channel . '_' . $_field . '" value=""/>');

                        $addedfield = $mform->addGroup($inputs, $channel . '_' . $_field, $label, '&nbsp;&nbsp;&nbsp;', false);
                        break;

                    default:
                        throw new \moodle_exception("Unknown field type: {$field['type']}, for field: {$_field}");
                }
                if (isset($addedfield) && isset($field['default'])) {
                    $mform->setDefault($channel . '_' . $_field, $field['default']);
                }
                if (isset($addedfield) && isset($field['datatype'])) {
                    $mform->setType($channel . '_' . $_field, $field['datatype']);
                }
                if (isset($addedfield) && $this->has_help_button($channel, $_field, $stringman)) {
                    $mform->addHelpButton($channel . '_' . $_field, $channel . '_' . $_field, 'block_edupublisher');
                }
                if (isset($field['hidden_on_init']) && $field['hidden_on_init']) {
                    $mform->hideIf($channel . '_' . $_field, 'id', 'eq', '0');
                }
                if (isset($addedfield) && $channel != 'default' && $channel != 'eduthekneu' && $channel != 'etapas') {
                    $mform->disabledIf($channel . '_' . $_field, $channel . '_publishas', 'notchecked');
                }
            }

            if ($channel == 'etapas') {
                // nach etapas die Channelauswahl einfügen
                $mform->addElement('hidden', 'clonecourse', get_string('clonecourse_attention', 'block_edupublisher'));
                $mform->setType('clonecourse', PARAM_BOOL);
                $mform->setDefault('clonecourse', 1);

                $boxes = array();
                foreach ($channels as $_channel => $fields) {
                    // if ($_channel == 'default')
                    //     continue;
                    $label = $this->get_label($channels, $_channel, 'publish_as', ucfirst($_channel), $stringman);
                    $boxes[] = $mform->createElement('advcheckbox', $_channel . '_publishas', $label, NULL, array(
                        // disable onclick:
                        // 'onclick' => 'var inp = this; require(["jquery"], function($) { $("#id_' . $_channel . '_publish_as").css("display", $(inp).is(":checked") ? "block" : "none"); });'
                    ), array(0, 1));
                    //$mform->setType($_channel . '_publishas', PARAM_INT);
                }
                $mform->addGroup($boxes, 'publishings', get_string('channels', 'block_edupublisher'), array(' '), false);
            }
        }

        // $mform->addElement('header', 'content_items_header', 'Aktivitäten/Ressourcen');
        // $mform->setExpanded('content_items_header');

        $cnt = (isset($this->_customdata['content_items_old']) ? count($this->_customdata['content_items_old']) : 0) + 10;
        for ($content_i = 0; $content_i < $cnt; $content_i++) {
            $mform->addElement('header', "content_item_$content_i", '<span class="num"></span>. Aktivität/Ressource');
            $mform->setExpanded("content_item_$content_i");

            $txtrequired = get_string('required');
            $required_info = '<span class="float-sm-right text-nowrap"><abbr class="initialism text-danger" title="' . $txtrequired . '"><i class="icon fa fa-exclamation-circle text-danger fa-fw " aria-hidden="true" title="' . $txtrequired . '" aria-label="' . $txtrequired . '"></i></abbr></span>';

            $element = $mform->addElement('text', "content_items[$content_i][id]", 'id');
            $element->updateAttributes(array('class' => 'hidden'));

            $mform->setType("content_items[$content_i][id]", PARAM_INT);

            $element = $mform->addElement('text', "content_items[$content_i][delete]", 'delete');
            $element->updateAttributes(array('class' => 'hidden'));
            $mform->setType("content_items[$content_i][delete]", PARAM_INT);
            // das geht nicht?!?
            // $mform->setDefault("content_items[$content_i][delete]", 1);

            $mform->addElement('textarea', "content_items[$content_i][description]", 'Beschreibung (Anweisung für Schüler:innen)' . $required_info);
            $mform->setType("content_items[$content_i][description]", PARAM_TEXT);

            $mform->addElement('text', "content_items[$content_i][link]", 'Link');
            $mform->setType("content_items[$content_i][link]", PARAM_TEXT);

            $mform->addElement('filemanager', "content_items[$content_i][files]", 'Dateien (Bilder, Dokumente oder H5p Inhalte)', null, [
                'subdirs' => 0,
                'maxfiles' => 10,
                'accepted_types' => ['image', 'document', '.h5p'],
            ]);

            $mform->addElement('textarea', "content_items[$content_i][didaktische_hinweise]", 'Didaktische Hinweise');
            $mform->setType("content_items[$content_i][didaktische_hinweise]", PARAM_TEXT);

            $mform->addElement('filemanager', "content_items[$content_i][dh_files]", 'Dateien (für Lehrkraft)', null, [
                'subdirs' => 0,
                'maxfiles' => 10,
                'accepted_types' => ['image', 'document', '.h5p'],
            ]);

            ob_start();
            ?>
            <div style="text-align: right; padding-bottom: 20px;">
                <button class="list-manager-delete-button btn btn-secondary" type="button">Aktivität/Ressource löschen</button>
            </div>
            <?php
            $mform->addElement('html', ob_get_clean());
        }

        $mform->addElement('header', 'content_item_list_buttons', '');
        $mform->setExpanded('content_item_list_buttons');

        ob_start();
        ?>
        <div style="margin-bottom: 25px">
            <button class="list-manager-add-button btn btn-secondary" type="button">
                Aktivität/Ressource hinzufügen
            </button>
        </div>
        <?php
        $mform->addElement('html', ob_get_clean());

        // $mform->hideIf('etapas_status', 'cantriggeractiveetapas', 'neq', '1');

        // $mform->addElement('html', '<script type="text/javascript"> document.addEventListener("DOMContentLoaded", function(event) { require(["block_edupublisher/main"], function(MAIN) { MAIN.preparePackageForm("' . implode(',', array_keys((array)$channels)) . '"); }); });</script>');

        // Manually disable OER-Channels if set to commercial and vice versa
        // if ($ALLOW_COMMERCIAL) {
        $mform->disabledIf('commercial_publishas', 'etapas_publishas', 'checked');
        $mform->disabledIf('commercial_publishas', 'eduthek_publishas', 'checked');
        $mform->disabledIf('commercial_publishas', 'eduthekneu_publishas', 'checked');
        $mform->disabledIf('etapas_publishas', 'commercial_publishas', 'checked');
        $mform->disabledIf('eduthek_publishas', 'commercial_publishas', 'checked');
        $mform->disabledIf('eduthekneu_publishas', 'commercial_publishas', 'checked');
        // }

        $this->add_action_buttons(true, get_string('save'));
    }

    /**
     * Checks if there is a localized string for a label.
     * @param channel channel of this field.
     * @param field field from channel_definition.
     * @param label default label text.
     * @param stringman (optional)
     * @return localized label or default label.
     **/
    function get_label($definition, $channel, $field, $label, &$stringman = NULL, $required = false) {
        if (!isset($stringman))
            $stringman = get_string_manager();
        if ($stringman->string_exists($channel . '_' . $field, 'block_edupublisher')) {
            $label = get_string($channel . '_' . $field, 'block_edupublisher');
        } elseif (isset($definition['channel'][$field]['label'])) {
            $label = $definition['channel'][$field]['label'];
        } else {
            $label = ucfirst($label);
        }
        if ($required) {
            $txtrequired = get_string('required');
            $label .= '<span class="float-sm-right text-nowrap"><abbr class="initialism text-danger" title="' . $txtrequired . '"><i class="icon fa fa-exclamation-circle text-danger fa-fw " aria-hidden="true" title="' . $txtrequired . '" aria-label="' . $txtrequired . '"></i></abbr></span>';
        }
        if ($stringman->string_exists($channel . '_' . $field . '_label', 'block_edupublisher')) {
            $label .= '<br/>' . get_string($channel . '_' . $field . '_label', 'block_edupublisher');
        }
        return $label;
    }

    /**
     * Checks if there is a localized string for a help button.
     * @param channel channel of this field.
     * @param field field from channel_definition.
     * @param stringman (optional)
     * @return true or false
     **/
    function has_help_button($channel, $field, &$stringman = NULL) {
        if (!isset($stringman))
            $stringman = get_string_manager();
        return $stringman->string_exists($channel . '_' . $field . '_help', 'block_edupublisher');
    }

    /**
     * Checks if there is a localized string for a required text.
     * @param channel channel of this field.
     * @param field field from channel_definition.
     * @param stringman (optional)
     * @return true or false
     **/
    function has_required_text($channel, $field, &$stringman = NULL) {
        if (!isset($stringman))
            $stringman = get_string_manager();
        return $stringman->string_exists($channel . '_' . $field . '_missing', 'block_edupublisher');
    }

    /**
     * Checks if there is a localized string for a channel description
     * @param channel channel of this field.
     * @param stringman (optional)
     * @return true or false
     **/
    function has_channel_description($channel, &$stringman = NULL) {
        if (!isset($stringman))
            $stringman = get_string_manager();
        return $stringman->string_exists($channel . '__description', 'block_edupublisher');
    }

    //Custom validation should be added here
    function validation($data, $files) {
        $stringman = get_string_manager();
        $errors = array();
        $definition = $this->get_channel_definition();
        $channels = array_keys($definition);
        foreach ($channels as $channel) {
            if ($channel != 'default' && $channel != 'etapas' && $channel != 'eduthekneu' && $channel != 'default' && (!isset($data[$channel . '_publishas']) || !$data[$channel . '_publishas'])) {
                continue;
            }

            $fields = array_keys($definition[$channel]);
            foreach ($fields as $field) {
                $ofield = &$definition[$channel][$field];
                if (in_array($channel, ['etapas', 'eduthekneu']) && $field == 'kompetenzen') {
                    $package = $this->get_package();
                    if ($package) {
                        $package->exacompetencies();
                        $data["{$channel}_kompetenzen"] = $package->get('kompetenzen', $channel);
                    }
                }
                if (!isset($ofield['type']) || empty($ofield['required']))
                    continue;
                $haserror = false;
                if (isset($ofield['multiple']) && $ofield['multiple']) {
                    if (!isset($data[$channel . '_' . $field]) || count($data[$channel . '_' . $field]) == 0) {
                        $haserror = true;
                    }
                } elseif ($ofield['type'] == 'filemanager') {
                    $fileareaid = $data[$channel . '_' . $field];
                    $info = file_get_draft_area_info($fileareaid);
                    if (empty($info) || empty($info['filecount'])) {
                        $haserror = true;
                    }
                } else {
                    $content = (isset($ofield['type']) && $ofield['type'] == 'editor') ? $data[$channel . '_' . $field]['text'] : $data[$channel . '_' . $field];
                    if (empty(strip_tags($content))) {
                        $haserror = true;
                    }
                }
                if ($haserror) {
                    $errlbl = $this->get_label($definition, $channel, $field, ucfirst($field), $stringman);
                    $errors[$channel . '_' . $field] = ($this->has_required_text($channel, $field, $stringman)) ? get_string($channel . '_' . $field . '_missing', 'block_edupublisher') : $errlbl . ': ' . get_string('required');
                }
            }
        }

        if ($data['default_filling_mode'] == package::FILLING_MODE_SIMPLE) {
            foreach ($data['content_items'] as $key => $content_item_data) {
                if ($content_item_data['delete']) {
                    // gelöschte content_items nicht prüfen
                    continue;
                }

                if (!trim($content_item_data['description'])) {
                    $errors["content_items[$key][description]"] = get_string('required');
                }
            }
        }

        // if (!empty($data['etapas_publishas']) && empty($data['etapas_kompetenzen'])) {
        //     $errors['etapas_kompetenzen'] = get_string('etapas_kompetenzen_missing', 'block_edupublisher');
        // }

        // TODO: wieder aktivieren
        // if (!empty($data['eduthekneu_publishas']) && empty($data['eduthekneu_kompetenzen'])) {
        //     $errors['eduthekneu_kompetenzen'] = get_string('eduthekneu_kompetenzen_missing', 'block_edupublisher');
        // }

        // var_dump($errors); exit;

        return $errors;
    }

    function display() {
        global $PAGE;

        parent::display();

        $package = $this->get_package();

        ?>
        <style>
            .list-group.selected-items {
                margin-right: 20px;
            }

            @media (min-width: 768px) {
                form .fitem .col-md-3 {
                    flex: 0 0 40% !important;
                    max-width: 40% !important;
                }

                form .fitem .col-md-9 {
                    flex: 0 0 60% !important;
                    max-width: 60% !important;
                }
            }

            .content_item {
                display: none;
            }

            fieldset#id_content_item_list_buttons > div:first-of-type {
                /* hide the header line of group */
                display: none !important;
            }

            fieldset[id^="id_content_item_"] {
                display: none; /* hide content_items by default */
            }

            .mform .form-inline input[type="text"].form-control {
                width: 100%;
            }

            #fitem_id_kompetenzen_output .form-control-static {
                width: 100%;
            }

            /*#treeCompetenciesContainer {*/
            /*    margin-left: 3rem;*/
            /*}*/

            /*#treeCompetenciesContainer .list-group-item {*/
            /*    border: none;*/
            /*    padding: 0.25rem 2rem;*/
            /*}*/

            /*#treeCompetenciesContainer .list-group-item a:not(.collapsed) i.fa-chevron-right {*/
            /*    transform: rotate(90deg);*/
            /*}*/
        </style>
        <?php

        ob_start();
        ?>
        <script>
            require(['jquery'], function ($) {

                function rerender() {
                    if ($(':radio[name="default_filling_mode"][value="<?=package::FILLING_MODE_EXPERT?>"]').prop('checked')) {
                        // hide all content_items
                        $('.content_item').hide();
                        $('.fieldset#id_content_item_list_buttons').hide();
                    } else {
                        $('.content_item').each(function () {
                            $(this).toggle($(this).find(':input[name$="[delete]"]').val() != '1');
                        });
                        $('.fieldset#id_content_item_list_buttons').show();

                        $('.content_item:visible').each(function (index) {
                            $(this).find('.num').text((index + 1));
                        });
                    }
                }

                $(function () {
                    $('fieldset[id^="id_content_item_"]').addClass('content_item');
                    $('fieldset[id^="id_content_item_"]').filter(function () {
                        return $(this).find(':input[name$="[id]"]').val() == '0';
                    }).addClass('content_item_new');

                    rerender();

                    <?php if ($package && $package->get('filling_mode', 'default') == package::FILLING_MODE_EXPERT): ?>
                    $(':radio[name="default_filling_mode"][value="<?=package::FILLING_MODE_SIMPLE?>"]').change(function () {
                        alert('Achtung: durch den Wechsel auf den einfachen Modus werden die Kursinhalte überschrieben!');
                    })
                    <?php endif; ?>

                    $('.btn-select-competencies').click(function (e) {
                        // hide all other children
                        $(this).parent().children().not('.coursecompetenciesadd').hide();

                        $('.coursecompetenciesadd').removeClass('hidden');
                        // load first selected framework
                        $('.coursecompetenciesadd select[name="frameworkid"]').change();


                        e.preventDefault();
                    });
                })

                $(document).on('change', ':radio[name="default_filling_mode"]', function () {
                    rerender();
                })

                // function updateForm() {
                // $('.list-manager > li').each(function (index) {
                //     var $li = $(this);
                //
                //     // also disable input elements
                //     // dann werden diese beim speichern nicht übertragen, z.B. ausgewählte Dateien werden dann nicht übertragen
                //     var $selectedType = $li.find('.type-' + $li.find(':radio[name$="[content_type]"]:checked').val());
                //     $selectedType.show().find(':input').attr('disabled', false);
                //
                //     $li.find('.type-link, .type-file, .type-h5p').not($selectedType)
                //         .hide().find(':input').attr('disabled', true);
                // });
                // }

                // updateForm();

                $(document).on('click', '.collapseDidaktischeHinweiseButton', function () {
                    $(this).closest('.list-manager-item').find('.collapseDidaktischeHinweise').toggle();
                });

                // show didaktische Hinweise Fields anzeigen, wenn ausgefüllt
                // TODO: was ist bei files?!?
                // $('.collapseDidaktischeHinweise').each(function () {
                //     if ($(this).find(':input').filter(function () {
                //         return $(this).val();
                //     }).length
                //         // || $(this).find('.has-file').length
                //     ) {
                //         $(this).show();
                //     }
                // });

                $(document).on('click', '.list-manager-add-button', function (event) {
                    // the new item, is the last after all the visible items
                    var $newItem;
                    if ($('.content_item_new:visible').length) {
                        $newItem = $('.content_item_new:visible').last().next();
                    } else {
                        $newItem = $('.content_item_new').first();
                    }

                    if (!$newItem.length) {
                        alert('Bitte speichern Sie zuerst!');
                    }

                    $newItem.find(':input[name$="[delete]"]').val(0);

                    rerender();
                });

                $(document).on('click', '.list-manager-delete-button', function (event) {
                    if (!confirm('Wirklich löschen?')) {
                        return;
                    }

                    $item = $(this).closest('.content_item');
                    $item.find(':input[name$="[delete]"]').val(1);

                    rerender();
                });

                function bootstrapSelect($selects) {
                    $selects.each(function () {
                        const $originalSelect = $(this);
                        // remove old selects, when boostrapSelect is called a second time on same element
                        $originalSelect.siblings('.list-group').remove();

                        const $activeList = $('<div class="list-group selected-items" style="max-height: 260px; overflow-y: auto;"></div>').insertAfter($originalSelect);
                        const $list = $('<div class="list-group all-items" style="max-height: 260px; overflow-y: auto;"></div>').insertAfter($activeList);

                        // Initialize the div list from the select options
                        $originalSelect.find("option").each(function () {
                            // disablete optionen nicht darstellen
                            if ($(this).prop('disabled')) {
                                return;
                            }

                            const value = $(this).val();
                            const text = $(this).text();
                            const active = $(this).prop('selected');

                            var $element = $(`<button type="button" class="list-group-item list-group-item-action" style="padding-top: 3px; padding-bottom: 3px;" data-value="${value}">${text}</button>`);
                            var $activeElement = $element.clone();
                            $activeElement.addClass('active');

                            $element.appendTo($list);
                            $activeElement.appendTo($activeList);
                            if (active) {
                                $element.hide();
                            } else {
                                $activeElement.hide();
                            }

                            // :visible selector geht nicht, wenn ein parent hidden ist
                            $activeList.toggle($activeList.children().filter(function () {
                                return $(this).css('display') !== 'none';
                            }).length > 0);
                        });

                        // Handle div item click to select/deselect
                        $activeList.add($list).on("click", "button", function () {
                            var active = !$(this).is('.active');
                            const value = $(this).data("value");

                            $activeList.find('button[data-value="' + value + '"]').toggle(active);
                            $list.find('button[data-value="' + value + '"]').toggle(!active);
                            $originalSelect.find(`[value="${value}"]`).prop("selected", active);

                            $originalSelect.change();

                            // if ($originalSelect.attr('id') == 'id_schulstufen') {
                            //     onSchulstufenChange();
                            // }

                            // :visible selector geht nicht, wenn ein parent hidden ist
                            $activeList.toggle($activeList.children().filter(function () {
                                return $(this).css('display') !== 'none';
                            }).length > 0);
                        });

                        $originalSelect.hide();
                    });
                }

                bootstrapSelect($('select[multiple]').filter('[name="default_schoollevels[]"], [name="default_subjectareas[]"], [name$="_contenttypes[]"], [name$="_purposes[]"]'));

                // function onSchulstufenChange() {
                //     var schulstufenSelect = $('#id_schulstufen');
                //     // beim ändern der Schulstufe die Fächer anhand der Schulstufe filtern
                //     var ausbildungsstufeNamen = schulstufenSelect.find('option:selected').map(function () {
                //         var schulstufeName = $(this).text();
                //         var ausbildungsstufeName = schulstufeName.replace(/\s*\(.*$/, '');
                //         return ausbildungsstufeName;
                //     })
                //     $('#id_faecher option').each(function () {
                //         var educationlevels = String($(this).data('educationlevels')).split(',');
                //         var matches = educationlevels.includes('alle-edulevels');
                //         ausbildungsstufeNamen.each(function (key, ausbildungsstufeName) {
                //             matches = matches || educationlevels.includes(ausbildungsstufeName);
                //         });
                //         $(this).prop('disabled', !matches);
                //     });
                //
                //     // spezielles select neu initialisieren
                //     bootstrapSelect($('#id_faecher'));
                // }

                // $('select[name*="[schulstufen]"]').on('change', onSchulstufenChange);
                // $('select[name*="[schulstufen]"]').trigger('change');

                // $(document).on('click', ':submit', function (e) {
                //     if (!$('#gendern_beachten').prop('checked')) {
                //         alert('Sie müssen den Hinweis zur geschlechtergerechten Sprache bestätigen!');
                //         return false;
                //     }
                //     if (!$('#lizenz_akzeptieren').prop('checked')) {
                //         alert('Sie müssen die Lizenz akzeptieren!');
                //         return false;
                //     }
                // });
            });

        </script>
        <?php
        $js = preg_replace('!</?script>!', '', ob_get_clean());
        $PAGE->requires->js_amd_inline($js);
    }

    public function set_data($default_values) {
        // prefill content_items and hide them
        if (!isset($default_values->content_items)) {
            $default_values->content_items = [];
        }
        for ($content_i = 0; $content_i < 10; $content_i++) {
            $default_values->content_items[] = [
                'id' => 0,
                'delete' => 1,
            ];
        }

        parent::set_data($default_values);
    }
}

