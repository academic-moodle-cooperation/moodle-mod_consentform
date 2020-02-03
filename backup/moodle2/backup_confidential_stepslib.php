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
 * Define all the backup steps that will be used by the backup_confidential_activity_task
 *
 * @package   mod_confidential
 * @category  backup
 * @copyright 2020 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete confidential structure for backup, with file and id annotations
 *
 * @package   mod_confidential
 * @category  backup
 * @copyright 2020 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_confidential_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     * @throws base_element_struct_exception
     * @throws base_step_exception
     */
    protected function define_structure() {

        // Get know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the confidential instance.
        $confidential = new backup_nested_element('confidential', array('id'), array(
            'name', 'intro', 'introformat', 'confirmationtext', 'optiondisagree'));

        // If we had more elements, we would build the tree here.

        // Define data sources.
        $confidential->set_source_table('confidential', array('id' => backup::VAR_ACTIVITYID));

        // If we were referring to other tables, we would annotate the relation
        // with the element's annotate_ids() method.

        // Define file annotations (we do not use itemid in this example).
        $confidential->annotate_files('mod_confidential', 'intro', null);

        // Return the root element (confidential), wrapped into standard activity structure.
        return $this->prepare_activity_structure($confidential);
    }
}
