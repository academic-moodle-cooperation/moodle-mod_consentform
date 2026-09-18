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
 * Completion tests for consentform responses.
 *
 * @package    mod_consentform
 * @copyright  2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @author     Clemens Marx
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_consentform;

use advanced_testcase;
use completion_info;
use mod_consentform\completion\custom_completion;
use mod_consentform_mod_form;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/consentform/lib.php');
require_once($CFG->dirroot . '/mod/consentform/locallib.php');
require_once($CFG->dirroot . '/mod/consentform/mod_form.php');

/**
 * Tests response rules against Moodle's completion API.
 *
 * @covers \mod_consentform\completion\custom_completion
 * @covers ::consentform_get_coursemodule_info
 * @covers ::mod_consentform_get_completion_active_rule_descriptions
 * @covers ::consentform_save_agreement
 * @covers ::consentform_update_completionstate
 * @covers \mod_consentform_mod_form
 */
final class completion_test extends advanced_testcase {
    /**
     * Set up a course with completion enabled.
     *
     * @return stdClass
     */
    private function create_course(): stdClass {
        global $CFG, $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enablecompletion = true;
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $PAGE->set_course($course);
        $PAGE->set_url('/course/modedit.php');
        return $course;
    }

    /**
     * Responses and expected automatic completion states.
     *
     * @return array
     */
    public static function response_provider(): array {
        return [
            'default agreement' => [[], CONSENTFORM_STATUS_AGREED, COMPLETION_COMPLETE],
            'default refusal' => [[], CONSENTFORM_STATUS_REFUSED, COMPLETION_INCOMPLETE],
            'response agreement' => [['completionresponded' => 1], CONSENTFORM_STATUS_AGREED, COMPLETION_COMPLETE],
            'response refusal' => [['completionresponded' => 1], CONSENTFORM_STATUS_REFUSED, COMPLETION_COMPLETE],
        ];
    }

    /**
     * Responses complete only the selected rule, and revocation clears completion.
     *
     * @dataProvider response_provider
     * @param array $rules Completion settings.
     * @param int $response Response to save.
     * @param int $expected Expected completion state.
     */
    public function test_response_completion(array $rules, int $response, int $expected): void {
        $course = $this->create_course();
        $student = $this->getDataGenerator()->create_user();
        $otherstudent = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($otherstudent->id, $course->id, 'student');
        $activity = $this->create_consentform($course, $rules);
        $otheractivity = $this->create_consentform($course, $rules);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);
        $othercm = get_fast_modinfo($course)->get_cm($otheractivity->cmid);
        $completion = new completion_info($course);
        $rule = empty($rules['completionresponded']) ? 'completionagree' : 'completionresponded';
        $custom = new custom_completion($cm, $student->id);

        $this->assertEquals(COMPLETION_INCOMPLETE, $custom->get_state($rule));
        $this->assertSame([$rule], $custom->get_available_custom_rules());
        $this->assertSame([get_string($rule, 'consentform')], mod_consentform_get_completion_active_rule_descriptions($cm));

        $this->setUser($student);
        consentform_save_agreement($response, $student->id, $cm->id);
        $this->assertEquals($expected, $custom->get_state($rule));
        $this->assertEquals($expected, $completion->get_data($cm, false, $student->id)->completionstate);
        $this->assertEquals(COMPLETION_INCOMPLETE, (new custom_completion($cm, $otherstudent->id))->get_state($rule));
        $this->assertEquals(COMPLETION_INCOMPLETE, (new custom_completion($othercm, $student->id))->get_state($rule));

        consentform_save_agreement(CONSENTFORM_STATUS_REVOKED, $student->id, $cm->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $custom->get_state($rule));
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_data($cm, false, $student->id)->completionstate);
    }

    /**
     * Consent responses leave manual completion under the student's control.
     */
    public function test_manual_completion_is_preserved(): void {
        $course = $this->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $activity = $this->create_consentform($course, ['completion' => COMPLETION_TRACKING_MANUAL]);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);
        $completion = new completion_info($course);
        $this->setUser($student);

        consentform_save_agreement(CONSENTFORM_STATUS_AGREED, $student->id, $cm->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_data($cm)->completionstate);
        $completion->update_state($cm, COMPLETION_COMPLETE, $student->id);
        consentform_save_agreement(CONSENTFORM_STATUS_REFUSED, $student->id, $cm->id);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_data($cm)->completionstate);
        consentform_save_agreement(CONSENTFORM_STATUS_REVOKED, $student->id, $cm->id);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_data($cm)->completionstate);
        $this->assertSame([], mod_consentform_get_completion_active_rule_descriptions($cm));
    }

    /**
     * The form offers only supported settings and defaults new instances to agreement.
     */
    public function test_completion_form(): void {
        $course = $this->create_course();
        $current = (object) ['instance' => 0, 'course' => $course->id];
        $form = new mod_consentform_mod_form($current, 0, null, $course);
        $defaults = [];
        $form->data_preprocessing($defaults);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $defaults['completion']);
        $this->assertEquals(1, $defaults['completionagree']);
        $this->assertEquals(0, $defaults['completionresponded']);
        $html = $form->render();
        $this->assertStringContainsString('Agree to the consent form', $html);
        $this->assertStringContainsString('Agree to or refuse the consent form', $html);
        $this->assertStringContainsString('Students must manually mark the activity as done', $html);
        $this->assertStringNotContainsString('name="completionusegrade"', $html);
        $this->assertStringNotContainsString('name="completionpassgrade"', $html);
        $this->assertStringNotContainsString('name="completionview"', $html);
        $this->assertDoesNotMatchRegularExpression('/name="completion"[^>]*value="0"/', $html);
        $this->assertFalse($form->completion_rule_enabled([]));
        $this->assertTrue($form->completion_rule_enabled(['completionagree' => 1]));
        $this->assertTrue($form->completion_rule_enabled(['completionresponded' => 1]));
    }

    /**
     * Editable and locked completion settings submitted to the form.
     *
     * @return array
     */
    public static function form_settings_provider(): array {
        return [
            'manual' => [1, COMPLETION_TRACKING_MANUAL, 1, 1, 0, 0],
            'response overrides agreement' => [1, COMPLETION_TRACKING_AUTOMATIC, 1, 1, 0, 1],
            'agreement only' => [1, COMPLETION_TRACKING_AUTOMATIC, 1, 0, 1, 0],
            'unchecked' => [1, COMPLETION_TRACKING_AUTOMATIC, null, null, 0, 0],
            'locked' => [0, COMPLETION_TRACKING_AUTOMATIC, null, null, null, null],
        ];
    }

    /**
     * Submission honours the selected mode and preserves locked rules.
     *
     * @dataProvider form_settings_provider
     * @param int $unlocked Whether completion settings can be edited.
     * @param int $mode Completion mode.
     * @param int|null $agree Submitted agreement rule.
     * @param int|null $responded Submitted response rule.
     * @param int|null $expectedagree Expected agreement rule.
     * @param int|null $expectedresponded Expected response rule.
     */
    public function test_form_settings(
        int $unlocked,
        int $mode,
        ?int $agree,
        ?int $responded,
        ?int $expectedagree,
        ?int $expectedresponded
    ): void {
        $course = $this->create_course();
        $form = new mod_consentform_mod_form((object) ['instance' => 0, 'course' => $course->id], 0, null, $course);
        $data = (object) ['completionunlocked' => $unlocked, 'completion' => $mode];
        if ($agree !== null) {
            $data->completionagree = $agree;
        }
        if ($responded !== null) {
            $data->completionresponded = $responded;
        }
        $form->data_postprocessing($data);
        $this->assertSame($expectedagree, $data->completionagree ?? null);
        $this->assertSame($expectedresponded, $data->completionresponded ?? null);
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
}
