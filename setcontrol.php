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
 * Code to update if a course module is controlled by a confidential module or not in response to an ajax call.
 *
 * @package    mod_confidential
 * @copyright  2018 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

// Check access.
if (!confirm_sesskey()) {
    print_error('invalidsesskey');
}

// Get the params.
$ischecked = required_param('ischecked', PARAM_BOOL);  // Is the checkbox clicked or not?
$cmid = required_param('value', PARAM_INT);  // The ID of the coursemodule.


// Update
if (is_numeric($cmid)) {
    if ($dbok = $DB->set_field('course_modules', 'score', $ischecked ? "1" : "0", array('id' => $cmid))) {
        echo json_encode(array('status' => 'OK'));
    } else {
        echo json_encode(array('status' => 'NOT OK'));
    }
} else {
    echo json_encode(array('status' => 'NOT OK'));
}

