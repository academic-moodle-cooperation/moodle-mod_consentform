<?php
// This file is part of mod_checkmark for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * settings.php contains admin-Settings for consentform
 *
 * @package   mod_consentform
 * @author    Thomas Niedermaier (thomas.niedermaier@meduniwien.ac.at)
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Option to revoke default.
    $settings->add(new admin_setting_configcheckbox('consentform/optionrevoke',
                                                get_string('optionrevoke', 'consentform'),
                                                get_string('optionrevokedesc', 'consentform'),
                                                1));

    // Option to refuse default.
    $settings->add(new admin_setting_configcheckbox('consentform/optionrefuse',
        get_string('optionrefuse', 'consentform'),
        get_string('optionrefusedesc', 'consentform'),
        0));

    // Confirmation in course overview page default.
    $settings->add(new admin_setting_configcheckbox('consentform/confirmincourseoverview',
        get_string('confirmincourseoverview', 'consentform'),
        get_string('confirmincourseoverviewdesc', 'consentform'),
        0));

    // No course modules list default.
    $settings->add(new admin_setting_configcheckbox('consentform/nocoursemoduleslist',
        get_string('nocoursemoduleslist', 'consentform'),
        get_string('nocoursemoduleslistdesc', 'consentform'),
        0));

    // Buttonlabels defaults.
    $settings->add(new admin_setting_configtext('consentform/textagreementbutton',
        get_string('textagreementbutton', 'consentform'),
        get_string('textagreementbuttondesc','consentform'),
        get_string('agree', 'consentform'), PARAM_TEXT));
    $settings->add(new admin_setting_configtext('consentform/textrefusalbutton',
        get_string('textrefusalbutton', 'consentform'),
        get_string('textrefusalbuttondesc','consentform'),
        get_string('refuse', 'consentform'), PARAM_TEXT));
    $settings->add(new admin_setting_configtext('consentform/textrevocationbutton',
        get_string('textrevocationbutton', 'consentform'),
        get_string('textrevocationbuttondesc','consentform'),
        get_string('revoke', 'consentform'), PARAM_TEXT));

}

