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
 * The main confidential configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_confidential
 * @copyright  2018 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_confidential
 * @copyright  2018 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_confidential_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_ALPHANUM);

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('confidentialname', 'confidential'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'confidentialname', 'confidential');

        $this->standard_intro_elements(get_string('description', 'checkmark'));

        $mform->addElement('checkbox', 'optiondisagree', get_string('optiondisagree', 'confidential'));
        $mform->addHelpButton('optiondisagree', 'optiondisagreedesc', 'confidential');
        $mform->setDefault('optiondisagree', 1);

        $editor = $mform->addElement('editor', 'confirmationtext', get_string('confirmationtext', 'mod_confidential'));
        if (isset($this->current->confirmationtext)) {
            $editor->setValue(array('text' => $this->current->confirmationtext, 'format' => 1));
        }
        $mform->setType('confirmationtext', PARAM_RAW); // no XSS prevention here, users must be trusted
        $mform->addRule('confirmationtext', get_string('required'), 'required', null, 'client');

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Split form editor field array of confirmationtext into two fields
     */
    public function get_data($slashed = true) {
        if ($data = parent::get_data($slashed)) {
            if(isset($data->confirmationtext)) {
                $data->confirmationtext = $data->confirmationtext['text'];
            }
        }
        return $data;
    }


}
