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
 * @author     Thomas Niedermaier
 * @copyright  2020, Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_consentform;

defined('MOODLE_INTERNAL') || die();

// Global variable $CFG is always set, but with this little wrapper PHPStorm won't give wrong error messages!
if (isset($CFG)) {
    require_once($CFG->libdir . '/formslib.php');
}

/**
 * Agreement form
 *
 * @package    mod_consentform
 * @author     Thomas Niedermaier
 * @copyright  2020, Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class consentform_agreement_form extends \moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $DB, $OUTPUT;

        $mform = $this->_form;
        $data = &$this->_customdata;

        $mform->addElement('hidden', 'id', $data['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'cmid', $data['cmid']);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_ALPHANUM);

        // Display confirmation text.
        $paneldata = new \stdClass();
        $paneldata->cmid = $data['cmid'];
        $paneldata->cssclasses = $data['confirmationtextclass'];
        $text = $data['consentform']->confirmationtext;
        $text = file_rewrite_pluginfile_urls(
            $text,
            'pluginfile.php',
            $data['contextid'],
            'mod_consentform',
            'consentform',
            0);
        $paneldata->confirmationtext = format_text($text);
        $confirmationtexthtml = $OUTPUT->render_from_template('mod_consentform/confirmation_panel', $paneldata);
        $mform->addElement('html', $confirmationtexthtml);

        // Show state of confirmation of this user.
        $state = $DB->get_field('consentform_state', 'state',
                ['consentformcmid' => $data['cmid'], 'userid' => $data['userid']]) ?? false;
        $mform->addElement('html', consentform_get_agreementlogentry($data['cmid'], $data['userid'], $state));

        if (!$data['locked']) {
            // Display submit buttons.
            if ($state == CONSENTFORM_STATUS_AGREED) { // Already agreed.
                if ($data['consentform']->optionrevoke) {
                    $mform->addElement('submit', 'revocation', $data['consentform']->textrevocationbutton);
                }
            } else {
                $buttonarray = [];
                $buttonarray[] =& $mform->createElement('submit', 'agreement', $data['consentform']->textagreementbutton);
                if ($data['consentform']->optionrefuse && $state != CONSENTFORM_STATUS_REFUSED) {
                    $buttonarray[] =& $mform->createElement('submit', 'refusal', $data['consentform']->textrefusalbutton);
                }
                $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
            }
        }
    }

    /**
     * Get text from form editor field confirmationtext
     *
     * @return object|null
     */
    public function get_data() {
        if ($data = parent::get_data()) {
            if (isset($data->confirmationtext['text'])) {
                $data->confirmationtext = $data->confirmationtext['text'];
            }
        }
        return $data;
    }

}
