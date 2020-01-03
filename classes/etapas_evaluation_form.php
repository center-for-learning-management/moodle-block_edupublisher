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
 * @copyright  2019 Zentrum für Lernmanagement
 * @author     Julia Laßnig
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/formslib.php");
class etapas_evaluation_form extends \moodleform {
    function definition() {
        global $USER;
        if(isloggedin()  && !isguestuser()) {
            $first_name = $USER->firstname;
            $first_name_value = $first_name;
        }
        else {
            $first_name = get_string('evaluator_first_name', 'block_edupublisher');
            $first_name_value = '';
        }

        if(isloggedin()  && !isguestuser()) {
            $last_name = $USER->lastname;
            $last_name_value = $last_name;
        }
        else {
            $last_name = get_string('evaluator_last_name', 'block_edupublisher');
            $last_name_value = '';
        }

        if(isloggedin()  && !isguestuser()) {
            $email = $USER->email;
            $email_value = $email;
        }
        else {
            $email = get_string('evaluator_email', 'block_edupublisher');
            $email_value = '';
        }
 
        $mform = $this->_form;

        $attributes=array('size' => '150', 
                          'placeholder'=> $first_name,
                          'maxlength' => '150',
                          'value' => $first_name_value);
        $mform->addElement('text', 'evaluator_first_name', get_string('evaluator_first_name', 'block_edupublisher'), $attributes);
        $mform->setType('evaluator_first_name', PARAM_TEXT);
        $mform->addRule('evaluator_first_name', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);
        $mform->addRule('evaluator_first_name', get_string('max_length', 'block_edupublisher'), 'maxlength', 150, 'client');
       
        $attributes=array('size' => '150', 
                          'placeholder'=> $last_name,
                          'maxlength' => '150',
                          'required' => 'required',
                          'value' => $last_name_value);
        $mform->addElement('text', 'evaluator_last_name', get_string('evaluator_last_name', 'block_edupublisher'), $attributes);
        $mform->setType('evaluator_last_name', PARAM_TEXT);
        $mform->addRule('evaluator_last_name', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);
        $mform->addRule('evaluator_last_name', get_string('max_length', 'block_edupublisher'), 'maxlength', 150, 'client');
        
        $attributes=array('size' => '150', 
                          'placeholder'=>get_string('etapa_name', 'block_edupublisher'),
                          'maxlength' => '150',
                          'required' => 'required');
        $mform->addElement('text', 'e_tapa_name', get_string('etapa_name', 'block_edupublisher'), $attributes);
        $mform->setType('e_tapa_name', PARAM_TEXT);
        $mform->addRule('e_tapa_name', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);
        $mform->addRule('e_tapa_name', get_string('max_length', 'block_edupublisher'), 'maxlength', 150, 'client');

        $attributes=array('size' => '150', 
                          'placeholder'=>get_string('etapa_link', 'block_edupublisher'),
                          'maxlength' => '150',
                          'required' => 'required');
        $mform->addElement('text', 'e_tapa_link', get_string('etapa_link', 'block_edupublisher'), $attributes);
        $mform->setType('e_tapa_link', PARAM_TEXT);
        $mform->addRule('e_tapa_link', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);
        $mform->addRule('e_tapa_link', get_string('max_length', 'block_edupublisher'), 'maxlength', 150, 'client');


        $attributes=array('size' => '150', 
                          'placeholder'=> $email,
                          'maxlength' => '150',
                          'required' => 'required',
                          'value' => $email);
        $mform->addElement('text', 'evaluator_email', get_string('evaluator_email', 'block_edupublisher'), $attributes);
        $mform->setType('evaluator_email', PARAM_TEXT);
        $mform->addRule('evaluator_email', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);
        $mform->addRule('evaluator_email', get_string('max_length', 'block_edupublisher'), 'maxlength', 150, 'client');
        
        $mform->addElement('advcheckbox', 'author_contact', get_string('author_contact', 'block_edupublisher'), 
        '', array('group' => 1), array(0, 1));

        $mform->addElement('date_selector', 'evaluated_on', get_string('evaluated_on', "block_edupublisher"));
        $mform->addRule('evaluated_on', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);

        $attributes=array('size' => '150', 
                          'placeholder'=>get_string('name_of_school', 'block_edupublisher'),
                          'maxlength' => '150',
                          'required' => 'required');
        $mform->addElement('text', 'evaluated_at', get_string('school', 'block_edupublisher'), $attributes);
        $mform->setType('evaluated_at', PARAM_TEXT);
        $mform->addRule('evaluated_at', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);
        $mform->addRule('evaluated_at', get_string('max_length', 'block_edupublisher'), 'maxlength', 150, 'client');

        $ratingcoherent=array();
        $ratingcoherent[] = $mform->createElement('radio', 'comprehensible_description', '', '', 1);
        $ratingcoherent[] = $mform->createElement('radio', 'comprehensible_description', '', '', 2);
        $ratingcoherent[] = $mform->createElement('radio', 'comprehensible_description', '', '', 3);
        $ratingcoherent[] = $mform->createElement('radio', 'comprehensible_description', '', '', 4);
        $ratingcoherent[] = $mform->createElement('radio', 'comprehensible_description', '', '', 5);
        $mform->addGroup($ratingcoherent, 'comprehensible_description', 
            get_string('rating_coherent', "block_edupublisher"), array(' '), false);
        $mform->addRule('comprehensible_description', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $ratingplausible=array();
        $ratingplausible[] = $mform->createElement('radio', 'suitable_workflow', '', '', 1);
        $ratingplausible[] = $mform->createElement('radio', 'suitable_workflow', '', '', 2);
        $ratingplausible[] = $mform->createElement('radio', 'suitable_workflow', '', '', 3);
        $ratingplausible[] = $mform->createElement('radio', 'suitable_workflow', '', '', 4);
        $ratingplausible[] = $mform->createElement('radio', 'suitable_workflow', '', '', 5);
        $mform->addGroup($ratingplausible, 'suitable_workflow', 
            get_string('rating_plausible', "block_edupublisher"), array(' '), false);
        $mform->addRule('suitable_workflow', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $ratingpreconditions=array();
        $ratingpreconditions[] = $mform->createElement('radio', 'reasonable_preconditions', '', '', 1);
        $ratingpreconditions[] = $mform->createElement('radio', 'reasonable_preconditions', '', '', 2);
        $ratingpreconditions[] = $mform->createElement('radio', 'reasonable_preconditions', '', '', 3);
        $ratingpreconditions[] = $mform->createElement('radio', 'reasonable_preconditions', '', '', 4);
        $ratingpreconditions[] = $mform->createElement('radio', 'reasonable_preconditions', '', '', 5);
        $mform->addGroup($ratingpreconditions, 'reasonable_preconditions', 
            get_string('rating_preconditions', "block_edupublisher"), array(' '), false);
        $mform->addRule('reasonable_preconditions', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $ratingcontent=array();
        $ratingcontent[] = $mform->createElement('radio', 'correct_content', '', '', 1);
        $ratingcontent[] = $mform->createElement('radio', 'correct_content', '', '', 2);
        $ratingcontent[] = $mform->createElement('radio', 'correct_content', '', '', 3);
        $ratingcontent[] = $mform->createElement('radio', 'correct_content', '', '', 4);
        $ratingcontent[] = $mform->createElement('radio', 'correct_content', '', '', 5);
        $mform->addGroup($ratingcontent, 'correct_content', 
            get_string('rating_content', "block_edupublisher"), array(' '), false);
        $mform->addRule('correct_content', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $mform->addElement('textarea', 'improvement_specification', 
            get_string("reason", "block_edupublisher"), 'wrap="virtual" rows="10" cols="70"');
        $mform->setType('improvement_specification', PARAM_TEXT);
        $mform->addRule('improvement_specification', get_string('max_length', 'block_edupublisher'), 'maxlength', 4000, 'client');
        $mform->addRule('improvement_specification', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);
    
        $technology=array();
        $technology[] = $mform->createElement('radio', 'substitution', '', 
            get_string('substitution', 'block_edupublisher'), 'substitution');
        $technology[] = $mform->createElement('radio', 'augmentation', '', 
            get_string('augmentation', 'block_edupublisher'), 'augmentation');
        $technology[] = $mform->createElement('radio', 'modification', '', 
            get_string('modification', 'block_edupublisher'), 'modification');
        $technology[] = $mform->createElement('radio', 'redefinition', '', 
            get_string('redefinition', 'block_edupublisher'), 'redefinition');
        $mform->addGroup($technology, 'technology_application', 
            get_string('technology', "block_edupublisher"), array(' '), false);
        $mform->addRule('technology_application', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $mform->addElement('textarea', 'comments', 
            get_string("feedback", "block_edupublisher"), 'wrap="virtual" rows="10" cols="70"');
        $mform->setType('comments', PARAM_TEXT);
        $mform->addRule('comments', get_string('max_length', 'block_edupublisher'), 'maxlength', 4000, 'client');
        $mform->addRule('comments', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);


        /*$buttonarray=array();
        $buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] =& $mform->createElement('cancel', 'cancelbutton', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);*/

        $this->add_action_buttons($cancel = true, $submitlabel=null);

        
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }

}