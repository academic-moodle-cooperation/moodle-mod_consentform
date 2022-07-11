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
 * Define all the backup steps that will be used by the backup_consentform_activity_task
 *
 * @package   mod_consentform
 * @category  backup
 * @copyright 2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete consentform structure for backup, with file and id annotations
 *
 * @package   mod_consentform
 * @category  backup
 * @copyright 2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_consentform_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     * @throws base_element_struct_exception
     * @throws base_step_exception
     */
    protected function define_structure() {

        // Define the root element describing the consentform instance.
        $consentform = new backup_nested_element('consentform', array('id'), array(
            'name', 'intro', 'introformat', 'timecreated', 'timemodified', 'grade', 'confirmationtext', 'optionrevoke',
            'optionrefuse', 'textagreementbutton', 'textrefusalbutton', 'textrevocationbutton', 'usegrade',
            'confirmincourseoverview'));

        // Define data source.
        $consentform->set_source_table('consentform', array('id' => backup::VAR_ACTIVITYID));

        $userinfo = $this->get_setting_value('userinfo');
        // If userinfo is requested backup consentformstate as well.
        if ($userinfo) {
            $consentformstates = new backup_nested_element('consentformstates');
            $consentformstate = new backup_nested_element(
                'consentformstate', array('id'), array('consentformcmid', 'userid', 'state', 'timestamp')
            );
            // Build the tree.
            $consentform->add_child($consentformstates);
            $consentformstates->add_child($consentformstate);
            $consentformstate->set_source_table('consentform_state', array('consentformcmid' => backup::VAR_MODID));
            $consentformstate->annotate_ids('user', 'userid');
        }

        // Define file annotations.
        $consentform->annotate_files('mod_consentform', 'intro', null);

        // Return the root element (consentform), wrapped into standard activity structure.
        return $this->prepare_activity_structure($consentform);
    }
}
