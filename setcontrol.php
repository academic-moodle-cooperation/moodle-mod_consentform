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
 * Code to update if a course module is controlled by a consentform module or not in response to an ajax call.
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Check access.
if (!confirm_sesskey()) {
    print_error('invalidsesskey');
}

// Get the params.
$ischecked = required_param('ischecked', PARAM_BOOL);  // Is the checkbox clicked or not?
$cmidcontrolled = required_param('value', PARAM_INT);  // The ID of the dependent coursemodule.
$cmidcontroller = required_param('cmid', PARAM_INT);  // The ID of this consentform module.

$course = get_course_and_cm_from_cmid($cmidcontrolled)[0];

require_course_login($course);

// Update database entry.

// Legend: $ret... 1 if db-entry was made, 2 if db-entry was removed (or not found), 3 if nothing was done.
$ret = 3;
if (is_numeric($cmidcontrolled)) {
    if ($ischecked) {  // Checkbox is clicked.
        // If NO db-entry yet make it.
        if (!consentform_find_entry_availability($cmidcontrolled, $cmidcontroller)) {
            if ($ok = consentform_make_entry_availability($course->id, $cmidcontrolled, $cmidcontroller)) {
                $ret = 1;
            } else {
                $ret = 3;
            }
        }
    } else { // Checkbox is deselected.
        // If DB-entry exists remove it.
        if (consentform_find_entry_availability($cmidcontrolled, $cmidcontroller)) {
            if ($ok = consentform_delete_entry_availability($course->id, $cmidcontrolled, $cmidcontroller)) {
                $ret = 2;
            }
        } else {
            $ret = 3;
        }
    }
}

echo $ret;
