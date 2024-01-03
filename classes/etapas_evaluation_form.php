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
 * @copyright  2019+ Zentrum für Lernmanagement
 * @author     Julia Laßnig & Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/formslib.php");

class etapas_evaluation_form extends \moodleform {
    function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'packageid', 0);
        $mform->setType('packageid', PARAM_INT);

        /*
        $mform->addElement('date_selector', 'evaluated_on', get_string('evaluated_on', "block_edupublisher"));
        $mform->addRule('evaluated_on', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);
        */

        $attributes = array(
            'size' => '150', 'maxlength' => '150', 'required' => 'required',
            'placeholder' => get_string('name_of_school', 'block_edupublisher'),
        );
        $mform->addElement('text', 'evaluated_at', get_string('school', 'block_edupublisher'), $attributes);
        $mform->setType('evaluated_at', PARAM_TEXT);
        $mform->addRule('evaluated_at', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);
        $mform->addRule('evaluated_at', get_string('max_length', 'block_edupublisher'), 'maxlength', 150, 'client');

        $options = array();
        for ($a = 1; $a < 14; $a++) {
            $options[$a] = $a;
        }
        $mform->addElement('select', 'schoollevel', get_string('default_schoollevel', 'block_edupublisher'), $options);
        $mform->setType('schoollevel', PARAM_INT);
        $mform->addRule('schoollevel', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);

        $mform->addElement('date_selector', 'evaldate', get_string('date'));
        $mform->setType('evaldate', PARAM_INT);
        $mform->addRule('evaldate', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);

        $verytrue = get_string('evaluated_verytrue', 'block_edupublisher');
        $nottrue = get_string('evaluated_nottrue', 'block_edupublisher');

        $ratingcoherent = array(
            $mform->createElement('html', "<span style=\"margin-right: 15px;\">$nottrue</span>"),
            $mform->createElement('radio', 'comprehensible_description', '', '', 1),
            $mform->createElement('radio', 'comprehensible_description', '', '', 2),
            $mform->createElement('radio', 'comprehensible_description', '', '', 3),
            $mform->createElement('radio', 'comprehensible_description', '', '', 4),
            $mform->createElement('radio', 'comprehensible_description', '', '', 5),
            $mform->createElement('html', $verytrue),
        );
        $mform->addElement('html', '<div class="form-group row fitem"><div class="col-md-12">' . get_string('rating_coherent', "block_edupublisher") . '</div></div>');
        $mform->addGroup($ratingcoherent, 'comprehensible_description', '', array(' '), false);
        $mform->addRule('comprehensible_description', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $ratingplausible = array(
            $mform->createElement('html', "<span style=\"margin-right: 15px;\">$nottrue</span>"),
            $mform->createElement('radio', 'suitable_workflow', '', '', 1),
            $mform->createElement('radio', 'suitable_workflow', '', '', 2),
            $mform->createElement('radio', 'suitable_workflow', '', '', 3),
            $mform->createElement('radio', 'suitable_workflow', '', '', 4),
            $mform->createElement('radio', 'suitable_workflow', '', '', 5),
            $mform->createElement('html', $verytrue),
        );
        $mform->addElement('html', '<div class="form-group row fitem"><div class="col-md-12">' . get_string('rating_plausible', "block_edupublisher") . '</div></div>');
        $mform->addGroup($ratingplausible, 'suitable_workflow', '', array(' '), false);
        $mform->addRule('suitable_workflow', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $ratingpreconditions = array(
            $mform->createElement('html', "<span style=\"margin-right: 15px;\">$nottrue</span>"),
            $mform->createElement('radio', 'reasonable_preconditions', '', '', 1),
            $mform->createElement('radio', 'reasonable_preconditions', '', '', 2),
            $mform->createElement('radio', 'reasonable_preconditions', '', '', 3),
            $mform->createElement('radio', 'reasonable_preconditions', '', '', 4),
            $mform->createElement('radio', 'reasonable_preconditions', '', '', 5),
            $mform->createElement('html', $verytrue),
        );
        $mform->addElement('html', '<div class="form-group row fitem"><div class="col-md-12">' . get_string('rating_preconditions', "block_edupublisher") . '</div></div>');
        $mform->addGroup($ratingpreconditions, 'reasonable_preconditions', '', array(' '), false);
        $mform->addRule('reasonable_preconditions', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $ratingcontent = array(
            $mform->createElement('html', "<span style=\"margin-right: 15px;\">$nottrue</span>"),
            $mform->createElement('radio', 'correct_content', '', '', 1),
            $mform->createElement('radio', 'correct_content', '', '', 2),
            $mform->createElement('radio', 'correct_content', '', '', 3),
            $mform->createElement('radio', 'correct_content', '', '', 4),
            $mform->createElement('radio', 'correct_content', '', '', 5),
            $mform->createElement('html', $verytrue),
        );
        $mform->addElement('html', '<div class="form-group row fitem"><div class="col-md-12">' . get_string('rating_content', "block_edupublisher") . '</div></div>');
        $mform->addGroup($ratingcontent, 'correct_content', '', array(' '), false);
        $mform->addRule('correct_content', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $mform->createElement(
            'radio', 'substitution', get_string('technology', "block_edupublisher"),
            get_string('substitution', 'block_edupublisher'), 'substitution'
        );

        $technology = array(
            $mform->createElement(
                'radio', 'technology_application', '', get_string('substitution', 'block_edupublisher'),
                'substitution', array()
            ),
            $mform->createElement(
                'radio', 'technology_application', '', get_string('augmentation', 'block_edupublisher'),
                'augmentation', array()
            ),
            $mform->createElement(
                'radio', 'technology_application', '', get_string('modification', 'block_edupublisher'),
                'modification', array()
            ),
            $mform->createElement(
                'radio', 'technology_application', '', get_string('redefinition', 'block_edupublisher'),
                'redefinition', array()
            ),
        );
        $mform->addGroup($technology, 'technology', get_string('technology', "block_edupublisher"), array('<br />', '<br />', '<br />', '<br />'), false);
        $mform->addHelpButton('technology', 'technology', 'block_edupublisher');
        $mform->addRule('technology', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $mform->addElement('textarea', 'improvement_specification', get_string('reason', "block_edupublisher"), 'wrap="virtual" rows="10" cols="70"');
        $mform->setType('improvement_specification', PARAM_TEXT);
        $mform->addRule('improvement_specification', get_string('max_length', 'block_edupublisher'), 'maxlength', 4000, 'client');
        $mform->addRule('improvement_specification', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);

        $mform->addElement('textarea', 'comments', get_string('feedback', "block_edupublisher"), 'wrap="virtual" rows="10" cols="70"');
        $mform->setType('comments', PARAM_TEXT);
        $mform->addRule('comments', get_string('max_length', 'block_edupublisher'), 'maxlength', 4000, 'client');
        $mform->addRule('comments', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);

        $this->add_action_buttons($cancel = true, $submitlabel = null);
    }

    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }

}
