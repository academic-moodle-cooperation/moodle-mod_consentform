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
 * The mod_consentform instance list viewed event.
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_consentform\event;

/**
 * The mod_consentform instance list viewed event class.
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_instance_list_viewed extends \core\event\course_module_instance_list_viewed {
    /**
     * Initialize the event
     */
    protected function init() {
        $this->data['crud'] = 'u'; // Options: c (reate), r (ead), u (pdate), d (elete).
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'consentform';
    }

    /**
     * Get name of instance
     *
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public static function get_name() {
        return get_string('instancelistviewed', 'mod_consentform');
    }

    /**
     * Get description of instance
     *
     * @return \lang_string|string|null
     * @throws \coding_exception
     */
    public function get_description() {
        $a = new \stdClass();
        $a->userid = $this->userid;
        $a->contextinstanceid = $this->objectid;
        return get_string('instancelistvieweddesc', 'mod_consentform', $a);
    }

    /**
     * Get url of instance
     *
     * @return \moodle_url
     * @throws \moodle_exception
     */
    public function get_url() {
        return new \moodle_url('/mod/consentform/modulelist.php', array('id' => $this->objectid));
    }
}
