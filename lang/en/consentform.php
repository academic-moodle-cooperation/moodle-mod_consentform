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
 * English strings for consentform
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Consentform';
$string['modulenameplural'] = 'Consentforms';
$string['modulename_help'] = 'Use the consentform module to uncover certain activities not before the participant has agreed.';
$string['description'] = 'Description';
$string['consentform:addinstance'] = 'Add a new consentform module';
$string['consentform:submit'] = 'Edit consentform';
$string['consentform:view'] = 'View consentform';
$string['consentformname'] = 'Name';
$string['consentformname_help'] = 'Give this instance a name.';
$string['consentform'] = 'consentform';
$string['pluginadministration'] = 'Consentform administration';
$string['pluginname'] = 'Consentform';
$string['confirmationtext'] = 'Consentform text to agree/disagree to';
$string['modules'] = 'Activities and Ressources';
$string['dependent'] = 'Modules controlled by this consentform';
$string['noavailability'] = 'This module needs the moodle feature "availability" turned on, which is momentarily not the case at this moodle installation.';
$string['nocompletion'] = 'This module needs the moodle feature "completion" turned on, which is momentarily not the case at this moodle installation.';
$string['nocompletioncourse'] = 'This module needs the moodle feature "completion tracking" turned on in this course, which is momentarily not the case.';
$string['agree'] = 'I agree';
$string['disagree'] = 'I disagree';
$string['choice'] = 'Make your choice: ';
$string['msgagreed'] = 'You have succesfully AGREED to this consentform statement.';
$string['msgdisagreed'] = 'You have succesfully DISAGREED with this consentform statement.';
$string['msgagreedfailure'] = 'Your choice could not be saved. Please try it again.';
$string['eventagreementagree'] = "User AGREED";
$string['eventagreementagreedesc'] = 'The user with id {$a->userid} AGREED to the consentform statement of module {$a->contextinstanceid}.';
$string['eventagreementdisagree'] = "User DISAGREED";
$string['eventagreementagreedesc'] = 'The user with id {$a->userid} DISAGREED to the consentform statement of module {$a->contextinstanceid}.';
$string['revocation'] = 'Revoke';
$string['optiondisagree'] = 'Option to disagree';
$string['optiondisagreedesc'] = 'Option to disagree';
$string['optiondisagree_help'] = 'Allow participants to revoke their agreement.';
$string['usegrade'] = 'Use grade';
$string['usegradedesc'] = 'Use grade';
$string['usegrade_help'] = 'When a user agrees a value of 1 is written for this user for this module in the gradebook.';
$string['agreementlogentry'] = 'You have agreed at {$a}.';
$string['disagreementlogentry'] = 'You have revoked your agreement at {$a}.';
$string['privacy:null_reason'] = 'This plugin does not store any personal information. It merely controlls the visibility of course modules dependant of a consentform.';
$string['backbutton'] = 'Back to course overview';
$string['wrongoperator'] = 'This module has NOT an AND-conjunction in its restrictions. This {$a->consentform} is possibly without effect on this module!';
$string['nocempletiontitle'] = 'Completion not active';
$string['resetconsentform'] = 'Clear all consentform data';
$string['resetok'] = 'All data removed';