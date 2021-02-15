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

$string['agree'] = 'I agree';
$string['agreed'] = 'agreed';
$string['agreementlogentry'] = 'You have agreed at {$a}.';
$string['backbutton'] = 'Back to course overview';
$string['backbuttonlist'] = 'Back to module list';
$string['choice'] = 'Make your choice: ';
$string['configurations'] = 'Configuration of this consentform instance';
$string['confirmationtext'] = 'Consentform text to agree to';
$string['confirmincourseoverview'] = 'Agreement in course overview';
$string['confirmincourseoverview_help'] = 'Agreement/refusal is collected in course overview page.';
$string['confirmincourseoverviewdesc'] = 'Agreement in course overview';
$string['consentform'] = 'consentform';
$string['consentform:addinstance'] = 'Add a new consentform module';
$string['consentform:submit'] = 'Edit consentform';
$string['consentform:view'] = 'View consentform';
$string['consentformname'] = 'Name';
$string['consentformname_help'] = 'Give this instance a name.';
$string['dependent'] = 'Course elements not visible without agreement';
$string['description'] = 'Description';
$string['downloadbuttonlabel'] = 'Download';
$string['eventagreementagree'] = "User AGREED";
$string['eventagreementagreedesc'] = 'The user with id {$a->userid} AGREED to the consentform statement of module {$a->contextinstanceid}.';
$string['eventagreementrefuse'] = 'User REFUSED';
$string['eventagreementrefusedesc'] = 'The user with id {$a->userid} REFUSED the consentform statement of module {$a->contextinstanceid}.';
$string['eventagreementrevoke'] = 'User REVOKED';
$string['eventagreementrevokedesc'] = 'The user with id {$a->userid} REVOKED THE AGREEMENT to the consentform statement of module {$a->contextinstanceid}.';
$string['listempty'] = 'No entries found.';
$string['listusers'] = 'Reactions';
$string['modulename'] = 'Consentform';
$string['modulename_help'] = 'Use the consentform module to uncover certain activities not before the participant has agreed.';
$string['modulenameplural'] = 'Consentforms';
$string['modules'] = 'Activities and Ressources';
$string['msgagreed'] = 'You have succesfully AGREED to this consentform statement.';
$string['msgagreedfailure'] = 'Your choice could not be saved. Please try it again.';
$string['msgrefused'] = 'You have refused this consentform statement.';
$string['msgrevoked'] = 'You have succesfully REVOKED this consentform statement.';
$string['noaction'] = 'no reaction';
$string['noavailability'] = 'This module needs the moodle feature "availability" turned on, which is momentarily not the case at this moodle installation.';
$string['nocompletion'] = 'This module needs the moodle feature "completion" turned on, which is momentarily not the case at this moodle installation.';
$string['nocompletioncourse'] = 'This module needs the moodle feature completion tracking turned on in this course, which is momentarily not the case.';
$string['nocompletionmodule'] = 'This module needs the moodle feature completion tracking turned on, which is momentarily not the case.';
$string['nocompletiontitle'] = 'Completion not active';
$string['nocoursemoduleslist'] = 'No course module list';
$string['nocoursemoduleslist_help'] = 'No course module list is used to configure dependencies';
$string['nocoursemoduleslistdesc'] = 'No course module list is used. If you want to configure the dependencies by yourself';
$string['optionrefuse'] = 'Refusal';
$string['optionrefuse_help'] = 'Enable participants to refuse this consentform statement.';
$string['optionrefusedesc'] = 'Default for option to refuse.';
$string['optionrevoke'] = 'Option to revoke';
$string['optionrevoke_help'] = 'Allow participants to revoke their agreement.';
$string['optionrevokedesc'] = 'Default for option to revoke';
$string['pluginadministration'] = 'Consentform administration';
$string['pluginname'] = 'Consentform';
$string['privacy:null_reason'] = 'This plugin does not store any personal information. It merely controlls the visibility of course modules dependant of a consentform.';
$string['refuse'] = 'I refuse';
$string['refused'] = 'refused';
$string['refuselogentry'] = 'You have refused agreement at {$a}.';
$string['resetconsentform'] = 'Clear all consentform data';
$string['resetok'] = 'All data removed';
$string['revocation'] = 'Revoke';
$string['revoke'] = 'I revoke';
$string['revoked'] = 'revoked';
$string['revokelogentry'] = 'You have revoked your agreement at {$a}.';
$string['textagreementbutton'] = 'Label Agreement Button';
$string['textagreementbuttondesc'] = 'Default value for the label of the button to agree.';
$string['textfields'] = 'Text fields';
$string['textrefusalbutton'] = 'Label Refusal Button';
$string['textrefusalbuttondesc'] = 'Default value for the label of the button to refuse.';
$string['textrevocationbutton'] = 'Label Revocation Button';
$string['textrevocationbuttondesc'] = 'Default value for the label of the button to revoke.';
$string['timestamp'] = 'Date';
$string['titleagreed'] = 'Agreements';
$string['titlenone'] = 'No action';
$string['titlerevoked'] = 'Revocations';
$string['titlerefused'] = 'Refusals';
$string['usegrade'] = 'Use grade';
$string['usegrade_help'] = 'When a user agrees a value of 1 is written for this user for this module in the gradebook.';
$string['usegradedesc'] = 'Use grade for export';
$string['wrongoperator'] = 'This module has NOT an AND-conjunction in its restrictions. This {$a->consentform} is possibly without effect on this module!';