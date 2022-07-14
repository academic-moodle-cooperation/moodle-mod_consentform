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
 * Prints a particular userlist of consentform
 *
 * @package    mod_consentform
 * @copyright  2021 Thomas Niedermaier, Medical University of Vienna (thomas.niedermaier@meduniwien.ac.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
if ($id) {
    $cm         = get_coursemodule_from_id('consentform', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $consentform  = $DB->get_record('consentform', array('id' => $cm->instance), '*', MUST_EXIST);

} else {
    die('You must specify a course_module ID');
}

$sortkey   = optional_param('sortkey', 'lastname', PARAM_ALPHA); // Sorted view: lastname|firstname|email|timestamp.
$sortorder = optional_param('sortorder', 'ASC', PARAM_ALPHA);   // Defines the order of the sorting (ASC or DESC).
$tab  = optional_param('tab', 1, PARAM_INT); // ID of tab of listusers.php.

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

$listusers = consentform_get_listusers($sortkey, $sortorder, $tab, $context, $cm);
$csvrows = array();
foreach ($listusers as $record) {
    switch ($record->state) {
        case "1":
            $status = get_string("agreed", "consentform");
            break;
        case "0":
            $status = get_string("revoked", "consentform");
            break;
        case "-1":
            $status = get_string("refused", "consentform");
            break;
        default:
            $status = get_string("noaction", "consentform");
    }
    $csvrow = array(
        get_string('lastname') => $record->lastname,
        get_string('firstname') => $record->firstname,
        get_string('email') => $record->email,
        get_string('timestamp', 'consentform') =>
            $record->timestamp != CONSENTFORM_NOTIMESTAMP ? userdate($record->timestamp) : CONSENTFORM_NOTIMESTAMP,
        get_string('state') => $status
    );
    $csvrows[] = $csvrow;
} // End loop records.

$export = new \mod_consentform\consentform_export();
$exportformat = 'csv';
$export->init($exportformat, $csvrows, $course->shortname . '_' . $consentform->name . '_' . $tab . '_' . userdate(time(),
        '%d-%m-%Y', 99, false));
$export->print_file();