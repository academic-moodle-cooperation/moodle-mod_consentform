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
 * The consentform export listusers form
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
 * Export form
 *
 * @package    mod_consentform
 * @author     Thomas Niedermaier
 * @copyright  2020, Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class consentform_export_form extends \moodleform {

    /**
     * Define this form instance
     *
     * @throws \coding_exception
     */
    public function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'tab');
        $mform->setType('tab', PARAM_INT);

        // Add standard buttons, common to all modules.
        $mform->addElement('submit', 'submitbutton', get_string('downloadbuttonlabel', 'consentform'));
    }
}
