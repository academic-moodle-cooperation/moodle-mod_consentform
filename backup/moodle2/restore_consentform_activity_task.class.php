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
 * Provides the restore activity task class
 *
 * @package   mod_consentform
 * @category  backup
 * @author    Thomas Niedermaier
 * @copyright 2020, Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/consentform/backup/moodle2/restore_consentform_stepslib.php');
require_once($CFG->dirroot . '/mod/consentform/locallib.php');

/**
 * Restore task for the consentform activity module
 *
 * Provides all the settings and steps to perform complete restore of the activity.
 *
 * @package   mod_consentform
 * @category  backup
 * @author    Thomas Niedermaier
 * @copyright 2020, Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_consentform_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // We have just one structure step here.
        $this->add_step(new restore_consentform_activity_structure_step('consentform_structure', 'consentform.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('consentform', ['intro'], 'consentform');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('NEWMODULEVIEWBYID', '/mod/consentform/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('NEWMODULEINDEX', '/mod/consentform/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the restore_logs_processor when restoring
     * consentform logs. It must return one array
     * of restore_log_rule objects
     */
    public static function define_restore_log_rules() {
        $rules = [];

        $rules[] = new restore_log_rule('consentform', 'add', 'view.php?id={course_module}', '{consentform}');
        $rules[] = new restore_log_rule('consentform', 'update', 'view.php?id={course_module}', '{consentform}');
        $rules[] = new restore_log_rule('consentform', 'view', 'view.php?id={course_module}', '{consentform}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the restore_logs_processor when restoring
     * course logs. It must return one array
     * of restore_log_rule objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];

        $rules[] = new restore_log_rule('consentform', 'view all', 'index.php?id={course}', null);

        return $rules;
    }

    /**
     * Now restore module dependencies as well if the duplication is within the same course.
     * @throws dml_exception
     */
    public function after_restore(): void {
        $courseid = $this->get_courseid();
        if ($this->is_samesite() && $this->get_old_courseid() == $courseid) {
            $cmold = $this->get_old_moduleid();
            $cmnew = get_coursemodule_from_instance('consentform', $this->get_activityid(), $courseid);
            $cms = get_course_mods($courseid);
            foreach ($cms as $cm) {
                if (consentform_find_entry_availability($cm->id, $cmold)) {
                    consentform_make_entry_availability($courseid, $cm->id, $cmnew->id);
                }
            }
        }
    }
}
