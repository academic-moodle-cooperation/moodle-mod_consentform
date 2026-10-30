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
 * Multilingual consentform button tests.
 *
 * @package    mod_consentform
 * @copyright  2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @author     Clemens Marx
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_consentform;

use advanced_testcase;
use context_module;
use DOMDocument;
use DOMXPath;
use stdClass;
use xmldb_field;
use xmldb_table;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/consentform/lib.php');
require_once($CFG->dirroot . '/mod/consentform/locallib.php');
require_once($CFG->dirroot . '/mod/consentform/db/upgrade.php');
require_once($CFG->libdir . '/upgradelib.php');

/**
 * Covers filtering, escaping and storage of button labels.
 *
 * @covers \mod_consentform\consentform_agreement_form
 * @covers ::xmldb_consentform_upgrade
 */
final class multilang_test extends advanced_testcase {
    /**
     * Languages and response states to render.
     *
     * @return array
     */
    public static function button_provider(): array {
        return [
            'English agreement and refusal' => ['en', false],
            'German agreement and refusal' => ['de', false],
            'English revocation' => ['en', true],
            'German revocation' => ['de', true],
        ];
    }

    /**
     * The activity context controls filtering and submit labels are escaped once.
     *
     * @dataProvider button_provider
     * @param string $language Current language.
     * @param bool $agreed Whether the user has already agreed.
     */
    public function test_button_labels(string $language, bool $agreed): void {
        global $DB, $PAGE, $SESSION, $USER;

        $this->resetAfterTest();
        if (!\core_component::get_plugin_directory('filter', 'multilang2')) {
            $this->markTestSkipped('The optional filter_multilang2 plugin is not installed.');
        }
        $translations = [
            'textagreementbutton' => [
                'en' => 'I agree to the terms & conditions',
                'de' => 'Ich stimme den Bedingungen ausdrücklich zu',
            ],
            'textrefusalbutton' => [
                'en' => 'I refuse the terms & conditions',
                'de' => 'Ich lehne die Bedingungen ausdrücklich ab',
            ],
            'textrevocationbutton' => [
                'en' => 'I revoke my agreement & consent',
                'de' => 'Ich widerrufe meine Zustimmung ausdrücklich',
            ],
        ];
        $labels = [];
        foreach ($translations as $field => $texts) {
            $labels[$field] = '{mlang de}' . $texts['de'] . '{mlang}{mlang en}' . $texts['en'] . '{mlang}';
            $this->assertGreaterThan(100, \core_text::strlen($labels[$field]));
        }
        $consentform = $this->create_consentform($labels);
        $context = context_module::instance($consentform->cmid);

        // Keep the page in the course context to exercise the embedded form's module context.
        $this->assertNotEquals($context->id, $PAGE->context->id);
        filter_set_global_state('multilang2', TEXTFILTER_OFF);
        filter_set_applies_to_strings('multilang2', true);
        filter_set_local_state('multilang2', $context->id, TEXTFILTER_ON);
        $SESSION->forcelang = $language;

        if ($agreed) {
            $DB->insert_record('consentform_state', (object) [
                'consentformcmid' => $consentform->cmid,
                'userid' => $USER->id,
                'state' => CONSENTFORM_STATUS_AGREED,
                'timestamp' => time(),
            ]);
        }
        $form = new consentform_agreement_form(null, [
            'id' => $consentform->id,
            'cmid' => $consentform->cmid,
            'consentform' => $consentform,
            'userid' => $USER->id,
            'confirmationtextclass' => CONSENTFORM_DEFAULTCSSCLASS,
            'locked' => false,
            'contextid' => $context->id,
        ]);
        $html = $form->render();
        $document = new DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($document);
        $buttons = $agreed ? ['revocation' => 'textrevocationbutton'] : [
            'agreement' => 'textagreementbutton',
            'refusal' => 'textrefusalbutton',
        ];
        foreach ($buttons as $name => $field) {
            $inputs = $xpath->query('//input[@type="submit" and @name="' . $name . '"]');
            $this->assertCount(1, $inputs);
            $this->assertSame($translations[$field][$language], $inputs->item(0)->getAttribute('value'));
        }
        $this->assertStringNotContainsString('{mlang', $html);
        foreach ($labels as $field => $label) {
            $this->assertSame($label, $DB->get_field('consentform', $field, ['id' => $consentform->id]));
        }
    }

    /**
     * Existing 100-character columns upgrade without losing their labels.
     */
    public function test_upgrade_button_label_lengths(): void {
        global $DB;

        $this->resetAfterTest();
        $this->preventResetByRollback();
        $consentform = $this->create_consentform();
        $dbman = $DB->get_manager();
        $table = new xmldb_table('consentform');
        $fields = ['textagreementbutton', 'textrefusalbutton', 'textrevocationbutton'];

        try {
            foreach ($fields as $name) {
                $field = new xmldb_field($name, XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
                $dbman->change_field_precision($table, $field);
            }
            set_config('version', 2025100601, 'mod_consentform');
            $this->assertTrue(xmldb_consentform_upgrade(2025100601));

            $columns = $DB->get_columns('consentform', false);
            foreach ($fields as $name) {
                $this->assertEquals(255, $columns[$name]->max_length);
                $this->assertSame($consentform->$name, $DB->get_field('consentform', $name, ['id' => $consentform->id]));
                $label = str_repeat('ä', 255);
                $DB->set_field('consentform', $name, $label, ['id' => $consentform->id]);
                $this->assertSame($label, $DB->get_field('consentform', $name, ['id' => $consentform->id]));
            }
            $this->assertEquals(2025100602, get_config('mod_consentform', 'version'));
        } finally {
            foreach ($fields as $name) {
                $field = new xmldb_field($name, XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
                $dbman->change_field_precision($table, $field);
            }
        }
    }

    /**
     * Creates an activity using the module API, including the supplied labels.
     *
     * @param array $labels Optional button labels.
     * @return stdClass Consentform record with cmid.
     */
    private function create_consentform(array $labels = []): stdClass {
        global $CFG, $DB, $PAGE;

        $this->setAdminUser();
        $CFG->enablecompletion = true;
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $PAGE->set_course($course);
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
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
            'optionrevoke' => 1,
            'optionrefuse' => 1,
            'usegrade' => 0,
            'confirmincourseoverview' => 0,
            'nocoursemoduleslist' => 1,
            'cssclassesstring' => '',
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 0,
            'completionexpected' => 0,
            'completionpassgrade' => 0,
            'showdescription' => 1,
            'cmidnumber' => '',
        ], $labels);
        $moduleinfo = add_moduleinfo($moduleinfo, $course);
        $consentform = $DB->get_record('consentform', ['id' => $moduleinfo->instance], '*', MUST_EXIST);
        $consentform->cmid = $moduleinfo->coursemodule;
        return $consentform;
    }
}
