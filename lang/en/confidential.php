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
 * English strings for confidential
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_confidential
 * @copyright  2018 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Confidentiality obligation';
$string['modulenameplural'] = 'Confidentiality obligations';
$string['modulename_help'] = 'Use the confidentiality obligation module to uncover certain activities not before the participant has agreed.';
$string['confidential:addinstance'] = 'Add a new confidentiality obligation module';
$string['confidential:submit'] = 'Submit confidentiality obligation';
$string['confidential:view'] = 'View confidentiality obligation';
$string['confidentialname'] = 'Confidentiality obligation';
$string['confidentialname_help'] = 'Use the confidentiality obligation module to uncover certain activities not before the participant has agreed.';
$string['confidential'] = 'confidentiality obligation';
$string['pluginadministration'] = 'Confidentiality obligation administration';
$string['pluginname'] = 'Confidentiality obligation';
$string['confirmationtext'] = 'Confidentiality text to agree/disagree to';
$string['modules'] = 'Activities and Ressources';
$string['dependent'] = 'Controlled by this module';
$string['noavailability'] = 'This module needs the moodle feature "availability" turned on, which is momentarily not the case at this moodle installation.';
$string['nocompletion'] = 'This module needs the moodle feature "completion" turned on, which is momentarily not the case at this moodle installation.';
$string['agree'] = 'I agree';
$string['disagree'] = 'I disagree';
$string['choice'] = 'Make your choice: ';
$string['msgagreed'] = 'You have succesfully AGREED to this confidentiality statement.';
$string['msgdisagreed'] = 'You have succesfully DISAGREED with this confidentiality statement.';
$string['msgagreedfailure'] = 'Your choice could not be saved. Please try it again.';
$string['eventagreementagree'] = "User AGREED";
$string['eventagreementagreedesc'] = 'The user with id {$a->userid} AGREED to the obligation statement of module {$a->contextinstanceid}.';
$string['eventagreementdisagree'] = "User DISAGREED";
$string['eventagreementagreedesc'] = 'The user with id {$a->userid} DISAGREED to the obligation statement of module {$a->contextinstanceid}.';
$string['revocation'] = 'Revoke';
$string['optiondisagree'] = 'Option to disagree';
$string['optiondisagreedesc'] = 'Allow participants to revoke their agreement.';
$string['optiondisagreedesc_help'] = 'Allow participants to revoke their agreement.';
$string['agreementlogentry'] = 'You have agreed at {$a}.';
$string['disagreementlogentry'] = 'You have revoked your agreement at {$a}.';
