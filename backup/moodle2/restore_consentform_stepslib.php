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
 * Define all the restore steps that will be used by the restore_consentform_activity_task
 *
 * @package   mod_consentform
 * @category  backup
 * @copyright 2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one consentform activity
 *
 * @package   mod_consentform
 * @category  backup
 * @copyright 2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_consentform_activity_structure_step extends restore_activity_structure_step {

    /** @var int ID of new consentform instance. */
    protected $newcfid;

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return object
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('consentform', '/activity/consentform');

        $userinfo = $this->get_setting_value('userinfo');
        if ($userinfo) {
            $paths[] = new restore_path_element('consentformstate',
                '/activity/consentform/consentformstates/consentformstate');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     * @throws base_step_exception
     * @throws dml_exception
     */
    protected function process_consentform($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        if (isset($data->grade) && $data->grade < 0) {
            // Scale found, get mapping.
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        // Create the consentform instance.
        $newitemid = $DB->insert_record('consentform', $data);
        $this->newcfid = $newitemid;
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     * @throws base_step_exception
     * @throws dml_exception
     */
    protected function process_consentformstate($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $moduleid = $DB->get_field('course_modules', 'module', array('id' => $data->consentformcmid));
        $newcmid = $DB->get_field('course_modules', 'id',
            array('module' => $moduleid, 'instance' => $this->newcfid));
        $data->consentformcmid = $newcmid;

        $newitemid = $DB->insert_record('consentform_state', $data);
        $this->set_mapping('consentformstate', $oldid, $newitemid);
    }

    /**
     * Post-execution actions
     */
    protected function after_execute() {
        // Add consentform related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_consentform', 'intro', null);
    }
}
