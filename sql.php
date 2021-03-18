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
 * @copyright  2020 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 * This file is included from listusers.php.
*/

defined('MOODLE_INTERNAL') || die();

$sortkey   = clean_param($sortkey, PARAM_ALPHA);// Sorted view: lastname | firstname | email | timestamp
$sortorder = clean_param($sortorder, PARAM_ALPHA);   // it defines the order of the sorting (ASC or DESC)

// Creating the SQL statement.

// Initialise some variables.
$sqlorderby = '';
$sqlsortkey = null;

// Calculate the SQL sortkey to be used by the SQL statements later.
switch ( $sortkey ) {
    case "lastname":
        $sqlsortkey = "lastname";
        break;
    case "firstname":
        $sqlsortkey = "firstname";
        break;
    case "email":
        $sqlsortkey = "email";
        break;
    case "timestamp":
        $sqlsortkey = "timestamp";
        break;
}
$sqlsortorder = $sortorder;

if ($tab == CONSENTFORM_STATUS_NOACTION) {
    $enrolledview = get_enrolled_users($context, 'mod/consentform:view', 0,
        'u.id, u.lastname, u.firstname, u.email', $sqlsortkey.' '.$sqlsortorder, 0, 0, true);
    $enrolledsubmit = get_enrolled_users($context, 'mod/consentform:submit', 0,
        'u.id, u.lastname, u.firstname, u.email', $sqlsortkey.' '.$sqlsortorder);
    $sqlselect = "SELECT u.id, u.lastname, u.firstname, u.email ";
    $sqlfrom   = "FROM {consentform_state} c INNER JOIN {user} u ON c.userid = u.id ";
    $sqlwhere  = "WHERE (c.consentformcmid = $cm->id) ";
    $sqlorderby = "ORDER BY $sqlsortkey $sqlsortorder";
    $query = "$sqlselect $sqlfrom $sqlwhere $sqlorderby";
    $withaction = $DB->get_records_sql($query);
    $sqlresult = array_diff_key($enrolledview, $enrolledsubmit, $withaction);
    foreach ($sqlresult as &$row) {
        $row->timestamp = CONSENTFORM_NOTIMESTAMP;
        $row->state = get_string('noaction', 'consentform');
    }
} else if ($tab == CONSENTFORM_ALL) {
    $enrolledview = get_enrolled_users($context, 'mod/consentform:view', 0,
        'u.id, u.lastname, u.firstname, u.email', $sqlsortkey.' '.$sqlsortorder, 0, 0, true);
    $enrolledsubmit = get_enrolled_users($context, 'mod/consentform:submit', 0,
        'u.id, u.lastname, u.firstname, u.email', $sqlsortkey.' '.$sqlsortorder);
    $sqlresult = array_diff_key($enrolledview, $enrolledsubmit);
    foreach ($sqlresult as &$row) {
        if ($fields = $DB->get_record('consentform_state', array('userid' => $row->id, 'consentformcmid' => $cm->id), 'timestamp, state')) {
            $row->timestamp = $fields->timestamp;
            $row->state = $fields->state;
        } else {
            $row->timestamp = CONSENTFORM_NOTIMESTAMP;
            $row->state = get_string('noaction', 'consentform');
        }
    }
} else {
    $sqlenrolled = get_enrolled_sql($context, '', 0, true);
    $enrolled = $DB->get_records_sql($sqlenrolled[0],$sqlenrolled[1]);
    $sqlselect = "SELECT u.id, u.lastname, u.firstname, u.email, c.timestamp, c.state ";
    $sqlfrom   = "FROM {consentform_state} c INNER JOIN {user} u ON c.userid = u.id ";
    $sqlwhere  = "WHERE (c.consentformcmid = $cm->id AND c.state = $tab) ";
    $sqlorderby = "ORDER BY $sqlsortkey $sqlsortorder";
    $query = "$sqlselect $sqlfrom $sqlwhere $sqlorderby";
    $sqlresult = $DB->get_records_sql($query);
    $sqlresult = array_intersect_key($sqlresult, $enrolled);
}

