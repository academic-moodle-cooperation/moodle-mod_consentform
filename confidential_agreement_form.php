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

require_once(dirname(__FILE__) . '/../../lib/formslib.php');

/**
 * Agreement form
 *
 * @package    mod_confidential
 * @copyright  2018 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
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

        $confirmationtexthtml = '<div id="confidential_confirmationtext">' . $data['text'] . '</div>';
        $separator = '<span id="confidential_button_separator">&nbsp;</span>';

        $mform->addElement('html', $confirmationtexthtml);

        $group = array();
        $group[] = $mform->createElement(
            'submit', 'agreement', get_string('agree', 'confidential')
        );
        $group[] = $mform->createElement(
            'submit', 'agreement', get_string('disagree', 'confidential')
        );
        $mform->addGroup($group, 'agreementgroup', get_string('choice', 'confidential'), $separator);

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
