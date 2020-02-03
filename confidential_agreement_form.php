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
 * @copyright  2020 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../lib/formslib.php');

/**
 * Agreement form
 *
 * @package    mod_confidential
 * @copyright  2020 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class confidential_agreement_form extends moodleform {

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

        $confirmationtexthtml = '<div id="confidential_confirmationtext">' . $data['text'] . '</div>';

        $mform->addElement('html', $confirmationtexthtml);

        $state = confidential_get_completion_state(null, $data['cmid'], $data['userid'], null);
        $mform->addElement('html', $this->get_agreementlogentry($data['cmid'], $data['userid'], $state));
        if ($state == 1) { // Already agreed.
            if (get_config('confidential', 'optiondisagree')) {
                $mform->addElement('submit', 'agreement', get_string('revocation', 'confidential'));
            }
        } else {
            $mform->addElement('submit', 'agreement', get_string('agree', 'confidential'));
        }
    }

    /**
     * Split form editor field array of confirmationtext into two fields
     */
    public function get_data($slashed = true) {
        if ($data = parent::get_data($slashed)) {
            if (isset($data->confirmationtext)) {
                $data->confirmationtext = $data->confirmationtext['text'];
            }
        }
        return $data;
    }

    /**
     * Get log entry of last agreement/revocation of this user.
     *
     * @param $cmid    coursemodule id
     * @param $userid  user id
     * @param $agreed  agreed (1) or disagreed (0)
     * @return bool    returns false if no logentry (=timestamp) was found.
     * @throws coding_exception
     * @throws dml_exception
     */
    private function get_agreementlogentry($cmid, $userid, $agreed) {
        global $DB, $OUTPUT;

        if ($timestamp = $DB->get_field('confidential_state', 'timestamp',
            array('confidentialcmid' => $cmid, 'userid' => $userid))) {
            if ($agreed == 1) {
                return $OUTPUT->notification(get_string('agreementlogentry', 'confidential', userdate($timestamp)));
            } else {
                return $OUTPUT->notification(get_string('disagreementlogentry', 'confidential', userdate($timestamp)));
            }
        }

        return false;
    }
}
