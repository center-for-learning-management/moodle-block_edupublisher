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
        $mform = $this->_form;

        
        $radioarray=array();
        $radioarray[] = $mform->createElement('radio', 'yesno', '', 
            get_string('yes'), 1);
        $radioarray[] = $mform->createElement('radio', 'yesno', '', 
            get_string('no'), 0);
        $mform->addGroup($radioarray, 'contact', 
            get_string('author_contact', "block_edupublisher"), array(' '), false);
        $mform->setDefault('yesno', 1);
        $mform->addRule('contact', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $mform->addElement('date_selector', 'evaluated', get_string('evaluated_on', "block_edupublisher"));
        $mform->addRule('evaluated', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $attributes=array('size' => '150', 
                          'placeholder'=>get_string('name_of_school', 'block_edupublisher'),
                          'maxlength' => '150',
                          'required' => 'required');
        $mform->addElement('text', 'school', get_string('school', 'block_edupublisher'), $attributes);
        $mform->setType('school', PARAM_TEXT);
        $mform->addRule('school', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);
        $mform->addRule('school', get_string('max_length', 'block_edupublisher'), 'maxlength', 150, 'client');

        $ratingcoherent=array();
        $ratingcoherent[] = $mform->createElement('radio', 'star1', '', '', 1);
        $ratingcoherent[] = $mform->createElement('radio', 'star2', '', '', 2);
        $ratingcoherent[] = $mform->createElement('radio', 'star3', '', '', 3);
        $ratingcoherent[] = $mform->createElement('radio', 'star4', '', '', 4);
        $ratingcoherent[] = $mform->createElement('radio', 'star5', '', '', 5);
        $mform->addGroup($ratingcoherent, 'rating_coherent', 
            get_string('rating_coherent', "block_edupublisher"), array(' '), false);
        $mform->addRule('rating_coherent', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $ratingplausible=array();
        $ratingplausible[] = $mform->createElement('radio', 'star1', '', '', 1);
        $ratingplausible[] = $mform->createElement('radio', 'star2', '', '', 2);
        $ratingplausible[] = $mform->createElement('radio', 'star3', '', '', 3);
        $ratingplausible[] = $mform->createElement('radio', 'star4', '', '', 4);
        $ratingplausible[] = $mform->createElement('radio', 'star5', '', '', 5);
        $mform->addGroup($ratingplausible, 'rating_plausible', 
            get_string('rating_plausible', "block_edupublisher"), array(' '), false);
        $mform->addRule('rating_plausible', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $ratingpreconditions=array();
        $ratingpreconditions[] = $mform->createElement('radio', 'star1', '', '', 1);
        $ratingpreconditions[] = $mform->createElement('radio', 'star2', '', '', 2);
        $ratingpreconditions[] = $mform->createElement('radio', 'star3', '', '', 3);
        $ratingpreconditions[] = $mform->createElement('radio', 'star4', '', '', 4);
        $ratingpreconditions[] = $mform->createElement('radio', 'star5', '', '', 5);
        $mform->addGroup($ratingpreconditions, 'rating_preconditions', 
            get_string('rating_preconditions', "block_edupublisher"), array(' '), false);
        $mform->addRule('rating_preconditions', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $ratingcontent=array();
        $ratingcontent[] = $mform->createElement('radio', 'star1', '', '', 1);
        $ratingcontent[] = $mform->createElement('radio', 'star2', '', '', 2);
        $ratingcontent[] = $mform->createElement('radio', 'star3', '', '', 3);
        $ratingcontent[] = $mform->createElement('radio', 'star4', '', '', 4);
        $ratingcontent[] = $mform->createElement('radio', 'star5', '', '', 5);
        $mform->addGroup($ratingcontent, 'rating_content', 
            get_string('rating_content', "block_edupublisher"), array(' '), false);
        $mform->addRule('rating_content', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $mform->addElement('textarea', 'reason', 
            get_string("reason", "block_edupublisher"), 'wrap="virtual" rows="10" cols="70"');
        $mform->setType('reason', PARAM_TEXT);
        $mform->addRule('reason', get_string('max_length', 'block_edupublisher'), 'maxlength', 4000, 'client');
        $mform->addRule('reason', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);
    
        $technology=array();
        $technology[] = $mform->createElement('radio', 'substitution', '', 
            get_string('substitution', 'block_edupublisher'), 'substitution');
        $technology[] = $mform->createElement('radio', 'augmentation', '', 
            get_string('augmentation', 'block_edupublisher'), 'augmentation');
        $technology[] = $mform->createElement('radio', 'modification', '', 
            get_string('modification', 'block_edupublisher'), 'modification');
        $technology[] = $mform->createElement('radio', 'redefinition', '', 
            get_string('redefinition', 'block_edupublisher'), 'redefinition');
        $mform->addGroup($technology, 'technology', 
            get_string('technology', "block_edupublisher"), array(' '), false);
        $mform->addRule('technology', get_string('required'), 'required', 'extraruledata', 'client', false, false);

        $mform->addElement('textarea', 'feedback', 
            get_string("feedback", "block_edupublisher"), 'wrap="virtual" rows="10" cols="70"');
        $mform->setType('feedback', PARAM_TEXT);
        $mform->addRule('feedback', get_string('max_length', 'block_edupublisher'), 'maxlength', 4000, 'client');
        $mform->addRule('feedback', get_string('required', 'block_edupublisher'), 'required', 'extraruledata', 'client', false, false);


        $buttonarray=array();
        $buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] =& $mform->createElement('submit', 'cancel', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }

}