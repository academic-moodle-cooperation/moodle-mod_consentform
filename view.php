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
 * Prints a particular instance of consentform
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/consentform_agreement_form.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... consentform instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('consentform', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $consentform  = $DB->get_record('consentform', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $consentform  = $DB->get_record('consentform', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $consentform->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('consentform', $consentform->id, $course->id, false, MUST_EXIST);
} else {
    die('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

$event = \mod_consentform\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $consentform);
$event->trigger();

$redirecturl = new moodle_url('/course/view.php', array('id' => $course->id));

// Print the page header.
$PAGE->set_url('/mod/consentform/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($consentform->name));
$PAGE->set_heading(format_string($course->fullname));

$nogostring = "";
if (!$CFG->enablecompletion) {
    $nogostring .= " " . get_string("nocompletion", "mod_consentform");
}
if (!$COURSE->enablecompletion) {
    $nogostring .= " " . get_string("nocompletioncourse", "mod_consentform");
}
if (!$cm->completion) {
    $nogostring .= " " . get_string("nocompletionmodule", "mod_consentform");
}

if ($nogostring) {

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string("nocompletiontitle", "mod_consentform"));
    echo $nogostring;

} else {

    if (has_capability('mod/consentform:submit', $context, null, false)) {
        // List of course modules, teacher's view.
        echo $OUTPUT->header();
        $table = new html_table();
        $table->id = 'consentform_activitytable';
        $table->attributes['class'] = 'flexible generaltable generalbox';
        $table->head = consentform_generate_table_header();
        $table->data = consentform_generate_table_content($course, $cm->id);

        echo $OUTPUT->action_link($redirecturl, get_string("backbutton", "consentform"));
        echo consentform_render_table($table);

        $jsparams = array('cmid' => $cm->id);
        $PAGE->requires->js_call_amd('mod_consentform/checkboxclicked', 'init', array($jsparams));
        $PAGE->requires->js_call_amd('mod_consentform/checkboxcontroller', 'init');

    } else {
        // Agreement form, participant's view.
        $mform = new consentform_agreement_form(null,
            array('id' => $id,
                'text' => $consentform->confirmationtext,
                'cmid' => $cm->id,
                'courseid' => $course->id,
                'consentform' => $consentform,
                'userid' => $USER->id
            ));
        // Process participant's agreement form data and redirect.
        if ($data = $mform->get_data()) {
            if ($data->agreement == get_string('agree', 'consentform')) {
                $ok = consentform_save_agreement(EXPECTEDCOMPLETIONVALUE, $USER->id, $cm->id);
                $message = get_string('msgagreed', 'consentform');
                $event = \mod_consentform\event\agreement_agree::create(
                    array(
                        'objectid' => $PAGE->cm->id,
                        'context' => $PAGE->context
                    )
                );
            } else {
                $ok = consentform_save_agreement(0, $USER->id, $cm->id);
                $message = get_string('msgdisagreed', 'consentform');
                $event = \mod_consentform\event\agreement_disagree::create(
                    array(
                        'objectid' => $PAGE->cm->id,
                        'context' => $PAGE->context
                    )
                );
            }
            $event->trigger();
            // Redirect after form processing.
            if ($message) {
                redirect($redirecturl, $message, 5);
            } else {
                redirect($redirecturl);
            }
        } else {  // Display agreement form to participant.
            // Output starts here.
            echo $OUTPUT->header();
            echo $OUTPUT->box_start('', 'consentform_main_cointainer');
            $mform->display();
            echo $OUTPUT->box_end();
        }
    }
}

// Finish the page.
echo $OUTPUT->footer();
