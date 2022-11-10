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
 * The main consentform configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/locallib.php');

/**
 * Module instance settings form
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_consentform_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $settings = get_config('consentform');

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_ALPHANUM);

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('consentformname', 'consentform'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'consentformname', 'consentform');

        if (!$this->_instance) {
            $mform->addElement('hidden', 'showdescription', '1');
            $mform->setType('showdescription', PARAM_INT);
        }

        // Adding the "texts" fieldset, where all the text fields are configured.
        $mform->addElement('header', 'textfields', get_string('textfields', 'consentform'));

        // The text to agree to.
        $editor = $mform->addElement('editor', 'confirmationtext', get_string('confirmationtext', 'mod_consentform'));
        if (isset($this->current->confirmationtext)) {
            $editor->setValue(array('text' => $this->current->confirmationtext, 'format' => 1));
        }
        $mform->setType('confirmationtext', PARAM_RAW); // No XSS prevention here, users must be trusted.
        $mform->addRule('confirmationtext', get_string('required'), 'required', null, 'client');

        // Agreement buttons labels.
        $labels = array('textagreementbutton', 'textrefusalbutton', 'textrevocationbutton' );
        foreach ($labels as $label) {
            $mform->addElement('text', $label, get_string($label, 'consentform'), 'size="32"');
            $mform->setType($label, PARAM_TEXT);
            $mform->setDefault($label, $settings->$label);
            $mform->addRule($label, get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        }
        $mform->addRule('textagreementbutton', null, 'required', null, 'client');

        // Adding the "configurations" fieldset, where all the consentform configuration options are configured.
        $mform->addElement('header', 'textfields', get_string('configurations', 'consentform'));

        $options = array ('optionrefuse', 'optionrevoke', 'usegrade', 'confirmincourseoverview', 'nocoursemoduleslist');
        foreach ($options as $option) {
            $mform->addElement('advcheckbox', $option, get_string($option, 'consentform'), null, null, array(0, 1));
            $mform->setType($option, PARAM_INT);
            if ($option == 'usegrade') {
                $mform->setDefault($option, 0);
            } else {
                $mform->setDefault($option, $settings->$option);
            }
            $mform->addHelpButton($option, $option, 'consentform');
        }

        if ($this->_instance) {
            $mform->disabledIf('confirmincourseoverview', 'sesskey', 'neq', '');
        }

        $this->standard_coursemodule_elements();

        $mform->disabledIf('completion', 'sesskey', 'neq', 'dummy');
        $mform->disabledIf('completionusegrade', 'completion', 'eq', 2);
        $mform->disabledIf('completionpassgrade', 'completion', 'eq', 2);

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Split form editor field array of confirmationtext into two fields
     * Set completion to value 2
     * Activate show description option if confirmincourseoverview option is on
     */
    public function get_data() {
        global $DB;

        if ($data = parent::get_data()) {
            if (isset($data->confirmationtext)) {
                $data->confirmationtext = $data->confirmationtext['text'];
            }
            $data->completion = 2;
            if (isset($data->confirmincourseoverview) && $data->confirmincourseoverview == 1) {
                $data->showdescription = 1;
            }
            if ($data->update) {
                $dbusegrade = $DB->get_field('consentform', 'usegrade', ["id" => $data->instance]);
                if ($dbusegrade != $data->usegrade) {
                    if ($data->usegrade) {
                        consentform_usegradechange_writegrades($data->coursemodule);
                    } else {
                        $consentform = $DB->get_record('consentform', array('id' => $data->instance));
                        consentform_grade_item_delete($consentform);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Called during validation to see whether some module-specific completion rules are selected.
     *
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled() {
        return true;
    }
}
