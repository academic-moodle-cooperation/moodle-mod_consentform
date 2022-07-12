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
 * @copyright  2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class consentform_agreement_form extends \moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;
        $data = &$this->_customdata;

        $mform->addElement('hidden', 'id', $data['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'cmid', $data['cmid']);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_ALPHANUM);

        // Display confirmation text.
        $confirmationtexthtml = '<div class="' . $data['confirmationtextclass'] . '">' .
            $data['consentform']->confirmationtext . '</div>';
        $mform->addElement('html', $confirmationtexthtml);

        // Show state of confirmation of this user.
        $state = $this->get_completion_state($data['cmid'], $data['userid']);
        $mform->addElement('html', $this->get_agreementlogentry($data['cmid'], $data['userid'], $state));

        // Display submit buttons.
        if ($state == CONSENTFORM_STATUS_AGREED) { // Already agreed.
            if ($data['consentform']->optionrevoke) {
                $mform->addElement('submit', 'revocation', $data['consentform']->textrevocationbutton);
            }
        } else {
            $buttonarray = array();
            $buttonarray[] =& $mform->createElement('submit', 'agreement', $data['consentform']->textagreementbutton);
            if ($data['consentform']->optionrefuse && $state != CONSENTFORM_STATUS_REFUSED) {
                $buttonarray[] =& $mform->createElement('submit', 'refusal', $data['consentform']->textrefusalbutton);
            }
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        }
    }

    /**
     * Get text from form editor field confirmationtext
     *
     * @return object|null
     */
    public function get_data() {
        if ($data = parent::get_data()) {
            if (isset($data->confirmationtext)) {
                $data->confirmationtext = $data->confirmationtext['text'];
            }
        }
        return $data;
    }

    /**
     * Get log entry of last agreement/refusal/revocation of this user.
     *
     * @param $cmid    coursemodule id
     * @param $userid  user id
     * @param $status  agreed or revoked or refused
     * @return string  returns logentry.
     * @throws coding_exception
     * @throws dml_exception
     */
    private function get_agreementlogentry($cmid, $userid, $status) {
        global $DB, $OUTPUT;

        if ($timestamp = $DB->get_field('consentform_state', 'timestamp',
            array('consentformcmid' => $cmid, 'userid' => $userid))) {
            if ($status == CONSENTFORM_STATUS_AGREED) {
                return $OUTPUT->notification(get_string('agreementlogentry', 'consentform', userdate($timestamp)), 'success');
            } else {
                if ($status == CONSENTFORM_STATUS_REVOKED) {
                    return $OUTPUT->notification(get_string('revokelogentry', 'consentform', userdate($timestamp)), 'warning');
                } else if ($status == CONSENTFORM_STATUS_REFUSED) {
                    return $OUTPUT->notification(get_string('refuselogentry', 'consentform', userdate($timestamp)), 'error');
                }
            }
        }

        return "";
    }

    /**
     * Obtains the automatic completion state for this consentform instance for this user
     *
     * @param object $cm Course-module
     * @param int $userid User ID
     * @return bool|mixed
     * @throws dml_exception
     */
    private function get_completion_state($cm, $userid) {
        global $DB;

        if (isset($cm->id)) {
            $cmid = $cm->id;
        } else {
            $cmid = $cm;
        }
        $state = $DB->get_field('consentform_state', 'state', array('consentformcmid' => $cmid, 'userid' => $userid));
        return $state ?? false;
    }
}
