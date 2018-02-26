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

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Check access.
if (!confirm_sesskey()) {
    print_error('invalidsesskey');
}

// Get the params.
$ischecked = required_param('ischecked', PARAM_BOOL);  // Is the checkbox clicked or not?
$val = required_param('value', PARAM_INT);  // The ID of the dependent coursemodule.
$cmid = required_param('cmid', PARAM_INT);  // The ID of this confidential module.


// Update
$ret = "?";
if ($ret = is_numeric($val)) {
    if ($ischecked) {
        if (!$ret = confidential_find_entry_availability($val, $cmid)) {
            $ret = confidential_make_entry_availability($val, $cmid);
        }
    } else {
        if (confidential_find_entry_availability($val, $cmid)) {
            $ret = confidential_delete_entry_availability($val, $cmid);
        }
    }
}
echo json_encode($ret);
