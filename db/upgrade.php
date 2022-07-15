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
 * This file keeps track of upgrades to the consentform module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute consentform upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 * @throws ddl_exception
 * @throws ddl_table_missing_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_consentform_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020052700) {

        // Define field usegrade to be added to consentform.
        $table = new xmldb_table('consentform');
        $field = new xmldb_field('usegrade', XMLDB_TYPE_INTEGER, '4', null, null, null, '0',
            'optionrevoke');

        // Conditionally launch add field usegrade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2020052700, 'consentform');
    }

    if ($oldversion < 2021020500) {

        $table = new xmldb_table('consentform');

        // Define field optionrevoke to be added to consentform.
        $field = new xmldb_field('optiondisagree', XMLDB_TYPE_INTEGER, '4', null, null, null, '0',
            'confirmationtext');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'optionrevoke');
        }

        // Define field optionrefuse to be added to consentform.
        $field = new xmldb_field('optionrefuse', XMLDB_TYPE_INTEGER, '4', null, null, null, '0',
            'optionrevoke');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field textagreementbutton to be added to consentform.
        $field = new xmldb_field('textagreementbutton', XMLDB_TYPE_TEXT, null, null, null, null, null,
            'optionrefuse');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field textrefusalbutton to be added to consentform.
        $field = new xmldb_field('textrefusalbutton', XMLDB_TYPE_TEXT, null, null, null, null, null,
            'textagreementbutton');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field textrevocationbutton to be added to consentform.
        $field = new xmldb_field('textrevocationbutton', XMLDB_TYPE_TEXT, null, null, null, null, null,
            'textrefusalbutton');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field confirmincourseoverview to be added to consentform.
        $field = new xmldb_field('confirmincourseoverview', XMLDB_TYPE_INTEGER, '4', null, null, null, '0',
            'usegrade');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $record = new stdClass();
        $record->textagreementbutton = get_string('agree', 'consentform');
        $record->textrevocationbutton = get_string('revoke', 'consentform');
        $record->textrefusalbutton = get_string('refuse', 'consentform');
        $ids = $DB->get_fieldset_select('consentform', 'id', '1 = 1');
        foreach ($ids as $id) {
            $record->id = $id;
            $DB->update_record('consentform', $record);
        }

        upgrade_mod_savepoint(true, 2021020500, 'consentform');
    }

    if ($oldversion < 2021020503) {

        $table = new xmldb_table('consentform');

        // Define field optionrevoke to be added to consentform.
        $field = new xmldb_field('nocoursemoduleslist', XMLDB_TYPE_INTEGER, '4', null, null, null, '0',
            'confirmincourseoverview');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2021020503, 'consentform');
    }

    if ($oldversion < 2021021101) {

        $table = new xmldb_table('consentform_state');

        // Adding foreign key to table consentform_state.
        $table->add_key('consentformcmid', XMLDB_KEY_FOREIGN, ['consentformcmid'], 'course_modules', ['id']);

        // Adding index to table consentform_state.
        $table->add_index('consentformcmid-userid', XMLDB_INDEX_UNIQUE, ['consentformcmid', 'userid']);

        upgrade_mod_savepoint(true, 2021021101, 'consentform');
    }

    return true;
}
