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
        global $CFG, $OUTPUT;

        $editoroptions = consentform_get_editor_options($this->context);

        $mform = $this->_form;

        $nocompletion = consentform_checkcompletion(null, $this->context, $this->_course, "nocheckcm");
        if ($nocompletion) {
            $nocompletion = html_writer::div(get_string("nocompletiontitle", "mod_consentform"),
                    'font-weight-bold').$nocompletion;
            $nocompletion = $OUTPUT->notification($nocompletion, 'error', false);
            $mform->addElement('html', $nocompletion);
            $mform->addElement('hidden', 'update', "");
            $mform->setType('update', PARAM_ALPHANUM);
            return;
        }

        $settings = get_config('consentform');

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_ALPHANUM);

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('consentformname', 'consentform'), ['size' => '64']);
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
        $mform->addElement('editor',
            'confirmationtext_editor',
            get_string("confirmationtext", "consentform"),
            null,
            $editoroptions);
        $mform->setType('confirmationtext_editor', PARAM_RAW);
        $mform->addRule('confirmationtext_editor', get_string('required'), 'required', null, 'client');

        // Agreement buttons labels.
        $labels = ['textagreementbutton', 'textrefusalbutton', 'textrevocationbutton'];
        foreach ($labels as $label) {
            $mform->addElement('text', $label, get_string($label, 'consentform'), 'size="32"');
            $mform->setType($label, PARAM_TEXT);
            $mform->setDefault($label, $settings->$label);
            $mform->addRule($label, get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
            $mform->addRule($label, null, 'required', null, 'client');
        }

        // Adding the "configurations" fieldset, where all the consentform configuration options are configured.
        $mform->addElement('header', 'textfields', get_string('configurations', 'consentform'));

        $options = ['optionrefuse', 'optionrevoke', 'usegrade', 'confirmincourseoverview', 'nocoursemoduleslist'];
        foreach ($options as $option) {
            $mform->addElement('advcheckbox', $option, get_string($option, 'consentform'), null, null, [0, 1]);
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

        // Field for user defined css classes string for the confirmation text panel.
        $mform->addElement('text', 'cssclassesstring', get_string('cssclassesstring', 'consentform'), 'size="64"');
        $mform->setType('cssclassesstring', PARAM_TEXT);
        $mform->addHelpButton('cssclassesstring', 'cssclassesstring', 'consentform');

        $this->standard_coursemodule_elements();

        $mform->disabledIf('completion', 'sesskey', 'neq', 'dummy');
        $mform->disabledIf('completionusegrade', 'completion', 'eq', 2);
        $mform->disabledIf('completionpassgrade', 'completion', 'eq', 2);

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Set completion to value 2
     * Activate show description option if confirmincourseoverview option is on
     */
    public function get_data() {

        if ($data = parent::get_data()) {
            $data->completion = 2;
            if (isset($data->confirmincourseoverview) && $data->confirmincourseoverview == 1) {
                $data->showdescription = 1;
            }
        }
        return $data;
    }

    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('confirmationtext_editor');
            $defaultvalues['confirmationtext_editor']['format'] = 1;
            $defaultvalues['confirmationtext_editor']['text'] = file_prepare_draft_area($draftitemid, $this->context->id, 'mod_consentform',
                'consentform', 0, consentform_get_editor_options($this->context), $defaultvalues['confirmationtext']);
            $defaultvalues['confirmationtext_editor']['itemid'] = $draftitemid;
        }
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
