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
        } else {
            if (!(isset($this->current->confirmincourseoverview) && $this->current->confirmincourseoverview == 1)) {
                $mform->addElement('advcheckbox', 'showdescription', null, get_string('showdescription', 'moodle'), null, array(0, 1));
                $mform->setType('showdescription', PARAM_INT);
                $mform->addHelpButton('showdescription', 'showdescription', 'moodle');
            }
        }

        // Adding the "text" fieldset, where all the text field options are configured.
        $mform->addElement('header', 'textfields', get_string('textfields', 'consentform'));

        // The confirmation Text.
        $editor = $mform->addElement('editor', 'confirmationtext', get_string('confirmationtext', 'mod_consentform'));
        if (isset($this->current->confirmationtext)) {
            $editor->setValue(array('text' => $this->current->confirmationtext, 'format' => 1));
        }
        $mform->setType('confirmationtext', PARAM_RAW); // No XSS prevention here, users must be trusted.
        $mform->addRule('confirmationtext', get_string('required'), 'required', null, 'client');

        // Agreement buttons labels.
        $mform->addElement('text', 'textagreementbutton', get_string('textagreementbutton', 'consentform'), 'size="32"');
        $mform->setType('textagreementbutton', PARAM_TEXT);
        $mform->setDefault('textagreementbutton', $settings->textagreementbutton);
        $mform->addRule('textagreementbutton', null, 'required', null, 'client');
        $mform->addRule('textagreementbutton', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');

        $mform->addElement('text', 'textrefusalbutton', get_string('textrefusalbutton', 'consentform'), 'size="32"');
        $mform->setType('textrefusalbutton', PARAM_TEXT);
        $mform->setDefault('textrefusalbutton', $settings->textrefusalbutton);
        $mform->addRule('textrefusalbutton', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');

        $mform->addElement('text', 'textrevocationbutton', get_string('textrevocationbutton', 'consentform'), 'size="32"');
        $mform->setType('textrevocationbutton', PARAM_TEXT);
        $mform->setDefault('textrevocationbutton', $settings->textrevocationbutton);
        $mform->addRule('textrevocationbutton', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');

        // Adding the "configurations" fieldset, where all the consentform configuration options are configured.
        $mform->addElement('header', 'textfields', get_string('configurations', 'consentform'));

        // Option to refuse.
        $mform->addElement('advcheckbox', 'optionrefuse', get_string('optionrefuse', 'consentform'), null, null, array(0, 1));
        $mform->setType('optionrefuse', PARAM_INT);
        $mform->setDefault('optionrefuse', $settings->optionrefuse);
        $mform->addHelpButton('optionrefuse', 'optionrefuse', 'consentform');

        // Option to revoke.
        $mform->addElement('advcheckbox', 'optionrevoke', get_string('optionrevoke', 'consentform'), null, null, array(0, 1));
        $mform->setType('optionrevoke', PARAM_INT);
        $mform->setDefault('optionrevoke', $settings->optionrevoke);
        $mform->addHelpButton('optionrevoke', 'optionrevoke', 'consentform');

        // Option to write grade value for an agreement.
        $mform->addElement('advcheckbox', 'usegrade', get_string('usegrade', 'consentform'), null, null, array(0, 1));
        $mform->setType('usegrade', PARAM_INT);
        $mform->setDefault('usegrade', 0);
        $mform->addHelpButton('usegrade', 'usegrade', 'consentform');

        // Option to place confirmation in course overview.
        $mform->addElement('advcheckbox', 'confirmincourseoverview', get_string('confirmincourseoverview', 'consentform'), null, null, array(0, 1));
        $mform->setType('confirmincourseoverview', PARAM_INT);
        $mform->setDefault('confirmincourseoverview', $settings->confirmincourseoverview);
        $mform->addHelpButton('confirmincourseoverview', 'confirmincourseoverview', 'consentform');
        if ($this->_instance) {
            $mform->disabledIf('confirmincourseoverview', 'sesskey', 'neq', '');
        }
        // Option not to use course module list for configuration of dependencies.
        $mform->addElement('advcheckbox', 'nocoursemoduleslist', get_string('nocoursemoduleslist', 'consentform'), null, null, array(0, 1));
        $mform->setType('nocoursemoduleslist', PARAM_INT);
        $mform->setDefault('nocoursemoduleslist', $settings->nocoursemoduleslist);
        $mform->addHelpButton('nocoursemoduleslist', 'nocoursemoduleslist', 'consentform');

        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Split form editor field array of confirmationtext into two fields
     * Set completion to value 2
     * Activate show description option if confirmincourseoverview option is on
     */
    public function get_data($slashed = true) {

        if ($data = parent::get_data($slashed)) {
            if (isset($data->confirmationtext)) {
                $data->confirmationtext = $data->confirmationtext['text'];
            }
            $data->completion = 2;
            if (isset($data->confirmincourseoverview) && $data->confirmincourseoverview == 1) {
                $data->showdescription = 1;
            }
        }
        return $data;
    }

    /**
     * Called during validation to see whether some module-specific completion rules are selected.
     *
     * @param array $data Input data not yet validated.
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return true;
    }
}
