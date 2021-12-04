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
 * Adapter to fill in the data from the moodleform into the exportclass
 *
 * @copyright  2021 Thomas Niedermaier, Medical University of Vienna (thomas.niedermaier@meduniwien.ac.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_consentform;

defined('MOODLE_INTERNAL') || die();


/**
 * @package    mod_consentform
 * @copyright  2021 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class consentform_export {

    private $exportformat;
    private $contentrows;
    private $filename;

    public function init($exportformat, $contentrows, $filename) {
        $this->exportformat = $exportformat;
        $this->contentrows = $contentrows;
        $this->filename = $filename;
    }

    /**
     * Generate the file and fill it with data.
     *
     * @throws \coding_exception
     */
    public function print_file() {
        global $USER;

        $export = new \mod_consentform\mtablepdf(array_fill(0, 5, array('mode' => 'Fixed', 'value' => 20)));

        // Set document information.
        $export->SetCreator('MOODLE');
        $export->SetAuthor($USER->firstname . " " . $USER->lastname);
        $export->set_outputformat(4);

        $titles = array(
            get_string('lastname'),
            get_string('firstname'),
            get_string('email'),
            get_string('timestamp', 'consentform'),
            get_string('status')
        );

        // Title row.
        $export->set_titles($titles);

        foreach ($this->contentrows as $row) {
            $export->add_row($row);
        }

        // Generate the export file.
        $export->generate($this->filename);
    }
}
