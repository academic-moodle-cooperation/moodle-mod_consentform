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
 * Module dependencies list
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
if ($id) {
    $cm           = get_coursemodule_from_id('consentform', $id, 0, false, MUST_EXIST);
    $course       = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $consentform  = $DB->get_record('consentform', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    die('You must specify a course module ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

$event = \mod_consentform\event\course_module_instance_list_viewed::create(array(
    'objectid' => $cm->id,
    'context' => context_course::instance($cm->course),
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $consentform);
$event->trigger();

$locked = false;
if ($context->locked) {
    $locked = true;
} else {
    $contextcoursecat = context_coursecat::instance($course->category);
    if ($contextcoursecat->locked) {
        $locked = true;
    } else {
        $contextcourse = context_course::instance($cm->course);
        if ($contextcourse->locked) {
            $locked = true;
        }
    }
}

$redirecturl = new moodle_url('/course/view.php', array('id' => $course->id));

// Print the page header.
$PAGE->set_url('/mod/consentform/modulelist.php', array('id' => $cm->id));
$PAGE->set_title(format_string($consentform->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('limitedwidth');

$nocompletion = consentform_checkcompletion($id, $context, $course, $cm->completion);

if ($nocompletion) {

    // Display error messages if completion settings are not sufficient.
    echo $OUTPUT->header();
    $nocompletion = html_writer::div(get_string("nocompletiontitle", "mod_consentform"),
            'font-weight-bold').$nocompletion;
    echo $OUTPUT->notification($nocompletion, 'error', false);

} else {

    if (has_capability('mod/consentform:submit', $context, null, false)) {
        if ($consentform->nocoursemoduleslist) {
            echo $OUTPUT->header();
            consentform_shownocoursemodulelistinfo($id);
        } else {
            // Display course modules list to the users with the submit right.
            consentform_showheaderwithoutintro($consentform->id);

            // List of course modules.
            $table = new html_table();
            $table->id = 'consentform_activitytable';
            $table->attributes['class'] = 'flexible generaltable generalbox';
            $table->head = consentform_generate_coursemodulestable_header();
            $table->data = consentform_generate_coursemodulestable_content($course, $cm->id, $locked);

            echo consentform_render_coursemodulestable($table);

            $jsparams = array('cmid' => $cm->id);
            $PAGE->requires->js_call_amd('mod_consentform/checkboxclicked', 'init', array($jsparams));
            $PAGE->requires->js_call_amd('mod_consentform/checkboxcontroller', 'init');
        }
    } else {  // If user has no right to submit.
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('nopermissiontoviewpage', 'error'), 'error', false);
    }
}

// Finish the page.
echo $OUTPUT->footer();
