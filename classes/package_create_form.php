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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/formslib.php");

class package_create_form extends moodleform {
    static $accepted_types = '';
    static $areamaxbytes = 10485760;
    static $maxbytes = 1024 * 1024;
    static $maxfiles = 1;
    static $subdirs = 0;

    function definition() {
        global $CFG, $COURSE, $DB, $USER;
        global $package;

        $editoroptions = array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 0,
            'changeformat' => 0, 'context' => null, 'noclean' => 0,
            'trusttext' => 0, 'enable_filemanagement' => false);

        $mform = $this->_form;
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'active', 0);
        $mform->setType('active', PARAM_INT);
        $mform->addElement('hidden', 'cantriggeractiveetapas', 0);
        $mform->setType('cantriggeractiveetapas', PARAM_INT);
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

        //$ALLOW_COMMERCIAL = get_config('block_edupublisher', 'enablecommercial') && \block_edupublisher\lib::is_publisher();
        $ALLOW_COMMERCIAL = \block_edupublisher\lib::is_publisher();
        $channels = \block_edupublisher\lib::get_channel_definition();
        $stringman = get_string_manager();
        foreach ($channels as $channel => $fields) {
            if ($channel == 'commercial' && !$ALLOW_COMMERCIAL)
                continue;
            $label = $this->get_label($channels, $channel, 'publish_as', ucfirst($channel), $stringman);
            $mform->addElement('header', $channel . '_publish_as', $label);
            if ($this->has_channel_description($channel, $stringman)) {
                $mform->addElement('html', '<p>' . get_string($channel . '__description', 'block_edupublisher') . '</p>');
            }

            foreach ($fields as $_field => $field) {
                if ($_field == 'publish_as')
                    continue;
                $required = isset($field['required']) && $field['required'];
                $hiddenexceptmaintainer = isset($field['hidden_except_maintainer']) && $field['hidden_except_maintainer'];
                if ($hiddenexceptmaintainer && !\block_edupublisher\lib::is_maintainer()) {
                    continue;
                }

                $label = $this->get_label($channels, $channel, $_field, $_field, $stringman, $required);

                unset($addedfield);
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
                if (isset($addedfield) && $channel != 'default') {
                    $mform->disabledIf($channel . '_' . $_field, $channel . '_publishas', 'notchecked');
                }
            }
            if ($channel == 'default') {
                $mform->addElement('hidden', 'clonecourse', get_string('clonecourse_attention', 'block_edupublisher'));
                $mform->setType('clonecourse', PARAM_BOOL);
                $mform->setDefault('clonecourse', 1);

                $boxes = array();
                foreach ($channels as $_channel => $fields) {
                    if ($_channel == 'default')
                        continue;
                    if ($_channel == 'commercial' && !$ALLOW_COMMERCIAL)
                        continue;
                    $label = $this->get_label($channels, $_channel, 'publish_as', ucfirst($_channel), $stringman);
                    $boxes[] = $mform->createElement('advcheckbox', $_channel . '_publishas', $label, NULL, array('onclick' => 'var inp = this; require(["jquery"], function($) { $("#id_' . $_channel . '_publish_as").css("display", $(inp).is(":checked") ? "block" : "none"); });'), array(0, 1));
                    //$mform->setType($_channel . '_publishas', PARAM_INT);
                }
                $mform->addGroup($boxes, 'publishings', get_string('channels', 'block_edupublisher'), array(' '), false);
            }
        }
        $mform->hideIf('etapas_status', 'cantriggeractiveetapas', 'neq', '1');

        $mform->addElement('html', '<script type="text/javascript"> document.addEventListener("DOMContentLoaded", function(event) { require(["block_edupublisher/main"], function(MAIN) { MAIN.preparePackageForm("' . implode(',', array_keys((array)$channels)) . '"); }); });</script>');

        // Manually disable OER-Channels if set to commercial and vice versa
        if ($ALLOW_COMMERCIAL) {
            $mform->disabledIf('commercial_publishas', 'etapas_publishas', 'checked');
            $mform->disabledIf('commercial_publishas', 'eduthek_publishas', 'checked');
            $mform->disabledIf('commercial_publishas', 'eduthekneu_publishas', 'checked');
            $mform->disabledIf('etapas_publishas', 'commercial_publishas', 'checked');
            $mform->disabledIf('eduthek_publishas', 'commercial_publishas', 'checked');
            $mform->disabledIf('eduthekneu_publishas', 'commercial_publishas', 'checked');
        }

        $this->add_action_buttons();
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
            $label .= '<p>' . get_string($channel . '_' . $field . '_label', 'block_edupublisher') . '</p>';
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
        $definition = \block_edupublisher\lib::get_channel_definition();
        $channels = array_keys($definition);
        foreach ($channels as $channel) {
            if ($channel != 'default' && (!isset($data[$channel . '_publishas']) || !$data[$channel . '_publishas']))
                continue;

            $fields = array_keys($definition[$channel]);
            foreach ($fields as $field) {
                $ofield = &$definition[$channel][$field];
                if (in_array($channel, [ 'etapas', 'eduthekneu' ]) && $field == 'kompetenzen') {
                    global $package;
                    $package->exacompetencies();
                    $data["{$channel}_kompetenzen"] = $package->get('kompetenzen', $channel);
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
        if (!empty($data['etapas_publishas']) && empty($data['etapas_kompetenzen'])) {
            $errors['etapas_kompetenzen'] = get_string('etapas_kompetenzen_missing', 'block_edupublisher');
        }
        if (!empty($data['eduthekneu_publishas']) && empty($data['eduthekneu_kompetenzen'])) {
            $errors['eduthekneu_kompetenzen'] = get_string('eduthekneu_kompetenzen_missing', 'block_edupublisher');
        }
        return $errors;
    }
}
