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
 * Backup and restore tests for mod_consentform.
 *
 * @package    mod_consentform
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     Mahdi Poustini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_consentform;

use advanced_testcase;
use backup;
use backup_controller;
use backup_setting;
use context_module;
use restore_controller;
use restore_dbops;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/consentform/lib.php');

/**
 * Backup and restore tests for mod_consentform.
 *
 * @package    mod_consentform
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class backup_restore_test extends advanced_testcase {
    /**
     * Course overview iframe is regenerated with the target site's URL and new instance id.
     */
    public function test_course_overview_iframe_is_regenerated_on_restore(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $CFG->enablecompletion = true;

        $sourcewwwroot = 'https://source.example.test/moodle';
        $targetwwwroot = 'https://target.example.test/moodle';
        $CFG->wwwroot = $sourcewwwroot;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $consentform = $this->create_consentform($course, ['confirmincourseoverview' => 1]);

        $sourceintro = $DB->get_field('consentform', 'intro', ['id' => $consentform->id], MUST_EXIST);
        $this->assertStringContainsString(
            $sourcewwwroot . '/mod/consentform/confirmation.php?id=' . $consentform->id,
            $sourceintro
        );

        $backupid = $this->backup_course($course);
        $CFG->wwwroot = $targetwwwroot;
        $newcourseid = $this->restore_course($backupid, $course);

        $restored = $DB->get_record('consentform', ['course' => $newcourseid], '*', MUST_EXIST);
        $this->assertNotEquals($consentform->id, $restored->id);
        $this->assertStringContainsString(
            $targetwwwroot . '/mod/consentform/confirmation.php?id=' . $restored->id,
            $restored->intro
        );
        $this->assertStringContainsString('name="consentformiframe' . $restored->id . '"', $restored->intro);
        $this->assertStringNotContainsString($sourcewwwroot, $restored->intro);
        $this->assertStringNotContainsString('confirmation.php?id=' . $consentform->id, $restored->intro);
        $this->assertStringNotContainsString('consentformiframe' . $consentform->id, $restored->intro);
    }

    /**
     * Updating an instance uses the submitted course overview setting.
     */
    public function test_update_instance_uses_submitted_course_overview_setting(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $CFG->enablecompletion = true;
        $CFG->wwwroot = 'https://target.example.test/moodle';

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $consentform = $this->create_consentform($course);
        $update = clone $consentform;
        $update->instance = $consentform->id;
        $update->coursemodule = $consentform->cmid;
        $update->confirmationtext_editor = [
            'text' => $consentform->confirmationtext,
            'itemid' => 0,
        ];

        $update->confirmincourseoverview = 1;
        $this->assertTrue(consentform_update_instance($update));

        $enabled = $DB->get_record('consentform', ['id' => $consentform->id], '*', MUST_EXIST);
        $this->assertSame(1, (int) $enabled->confirmincourseoverview);
        $this->assertStringContainsString(
            $CFG->wwwroot . '/mod/consentform/confirmation.php?id=' . $consentform->id,
            $enabled->intro
        );
        $this->assertStringContainsString('name="consentformiframe' . $consentform->id . '"', $enabled->intro);

        $update->confirmincourseoverview = 0;
        $this->assertTrue(consentform_update_instance($update));

        $disabled = $DB->get_record('consentform', ['id' => $consentform->id], '*', MUST_EXIST);
        $this->assertSame(0, (int) $disabled->confirmincourseoverview);
        $this->assertSame('', $disabled->intro);
    }

    /**
     * Files embedded in the confirmation text are included in the restored module context.
     */
    public function test_confirmationtext_files_are_restored(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $consentform = $this->create_consentform($course);
        $sourcecontext = context_module::instance($consentform->cmid);

        $fs = get_file_storage();
        $fs->create_file_from_string([
            'contextid' => $sourcecontext->id,
            'component' => 'mod_consentform',
            'filearea' => 'consentform',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'terms.txt',
        ], 'Restored terms');

        $backupid = $this->backup_course($course);
        $newcourseid = $this->restore_course($backupid, $course);
        $restored = $DB->get_record('consentform', ['course' => $newcourseid], '*', MUST_EXIST);
        $restoredcm = get_coursemodule_from_instance('consentform', $restored->id, $newcourseid, false, MUST_EXIST);
        $restoredcontext = context_module::instance($restoredcm->id);

        $restoredfile = $fs->get_file($restoredcontext->id, 'mod_consentform', 'consentform', 0, '/', 'terms.txt');
        $this->assertNotFalse($restoredfile);
        $this->assertEquals('Restored terms', $restoredfile->get_content());
    }

    /**
     * User agreement state is restored against the restored course module id.
     */
    public function test_consentform_state_uses_restored_course_module_mapping(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $consentform = $this->create_consentform($course);

        $DB->insert_record('consentform_state', [
            'consentformcmid' => $consentform->cmid,
            'userid' => $user->id,
            'state' => CONSENTFORM_STATUS_AGREED,
            'timestamp' => time(),
        ]);

        $backupid = $this->backup_course($course, true);
        $newcourseid = $this->restore_course($backupid, $course, true);
        $restored = $DB->get_record('consentform', ['course' => $newcourseid], '*', MUST_EXIST);
        $restoredcm = get_coursemodule_from_instance('consentform', $restored->id, $newcourseid, false, MUST_EXIST);

        $state = $DB->get_record('consentform_state', [
            'consentformcmid' => $restoredcm->id,
            'userid' => $user->id,
        ], '*', MUST_EXIST);

        $this->assertEquals(CONSENTFORM_STATUS_AGREED, $state->state);
        $this->assertNotEquals($consentform->cmid, $state->consentformcmid);
    }

    /**
     * Creates a consentform activity without requiring a plugin data generator.
     *
     * @param stdClass $course Course record.
     * @param array $overrides Field overrides.
     * @return stdClass Consentform record with cmid.
     */
    private function create_consentform(stdClass $course, array $overrides = []): stdClass {
        global $DB;

        $moduleinfo = (object) array_merge([
            'modulename' => 'consentform',
            'module' => $DB->get_field('modules', 'id', ['name' => 'consentform'], MUST_EXIST),
            'course' => $course->id,
            'section' => 0,
            'visible' => 1,
            'visibleoncoursepage' => 1,
            'name' => 'Consent form',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'confirmationtext' => '<p>Please confirm.</p>',
            'textagreementbutton' => 'Agree',
            'textrefusalbutton' => 'Refuse',
            'textrevocationbutton' => 'Revoke',
            'optionrevoke' => 0,
            'optionrefuse' => 0,
            'usegrade' => 0,
            'confirmincourseoverview' => 0,
            'nocoursemoduleslist' => 0,
            'cssclassesstring' => '',
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 0,
            'completionexpected' => 0,
            'completionpassgrade' => 0,
            'showdescription' => 1,
            'cmidnumber' => '',
        ], $overrides);

        $moduleinfo = add_moduleinfo($moduleinfo, $course);
        $consentform = $DB->get_record('consentform', ['id' => $moduleinfo->instance], '*', MUST_EXIST);
        $consentform->cmid = $moduleinfo->coursemodule;

        return $consentform;
    }

    /**
     * Backs up a course and returns the generated backup id.
     *
     * @param stdClass $course Course record.
     * @param bool $userdata Whether to include user data.
     * @return string Backup id.
     */
    private function backup_course(stdClass $course, bool $userdata = false): string {
        global $CFG, $USER;

        $CFG->backup_file_logger_level = backup::LOG_NONE;

        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id
        );
        $bc->get_plan()->get_setting('users')->set_status(backup_setting::NOT_LOCKED);
        $bc->get_plan()->get_setting('users')->set_value($userdata);

        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        return $backupid;
    }

    /**
     * Restores a backup into a new course.
     *
     * @param string $backupid Backup id.
     * @param stdClass $sourcecourse Source course record.
     * @param bool $userdata Whether to include user data.
     * @return int Restored course id.
     */
    private function restore_course(string $backupid, stdClass $sourcecourse, bool $userdata = false): int {
        global $USER;

        $newcourseid = restore_dbops::create_new_course(
            $sourcecourse->fullname,
            $sourcecourse->shortname . '_restored' . random_string(4),
            $sourcecourse->category
        );

        $rc = new restore_controller(
            $backupid,
            $newcourseid,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $rc->get_plan()->get_setting('users')->set_status(backup_setting::NOT_LOCKED);
        $rc->get_plan()->get_setting('users')->set_value($userdata);

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        return $newcourseid;
    }
}
