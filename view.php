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

$id = optional_param('id', 0, PARAM_INT); // Course_module ID

if ($id) {
    $cm         = get_coursemodule_from_id('consentform', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $consentform  = $DB->get_record('consentform', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    die('You must specify a course_module ID');
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
$nogostrcon = "";
if (!$CFG->enablecompletion) {
    $nogostring .= get_string("nocompletion", "mod_consentform");
    $nogostrcon = " ";
}
if (!$COURSE->enablecompletion) {
    $nogostring .= $nogostrcon . get_string("nocompletioncourse", "mod_consentform");
    $nogostrcon = " ";
}
if (!$cm->completion) {
    $nogostring .= $nogostrcon . get_string("nocompletionmodule", "mod_consentform");
    $nogostrcon = " ";
}

if ($nogostring) {

    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($consentform->name));
    $nogostring = get_string("nocompletiontitle", "mod_consentform"). $nogostrcon . $nogostring;
    echo $OUTPUT->fatal_error(
        $nogostring, "https://docs.moodle.org/en/Activity_completion_settings", $redirecturl, null);

} else {

    if (has_capability('mod/consentform:submit', $context, null, false)) {
        if ($consentform->nocoursemoduleslist) {
            redirect(new moodle_url('/mod/consentform/listusers.php', array('id' => $id)));
        }
        echo $OUTPUT->header();
        echo $OUTPUT->heading(format_string($consentform->name));
        if (!$consentform->confirmincourseoverview) {
            if ($consentform->intro) {
                echo $OUTPUT->box(format_module_intro(
                    'consentform', $consentform, $cm->id), 'generalbox mod_introbox', 'consentformintro');
            }
        }
        // Render action link to the reaction lists.
        echo $OUTPUT->box_start('', 'consentform_linklistusers_cointainer');
        echo $OUTPUT->image_icon('t/groupv', get_string('listusers', 'consentform'), 'moodle', array(
            'style' => 'cursor:pointer;margin-right:3px;nowrap'));
        echo $OUTPUT->action_link(new moodle_url('listusers.php', array('id' => $cm->id)),
            get_string('listusers', 'consentform'));
        echo $OUTPUT->box_end();
        // List of course modules, teacher's view.
        $table = new html_table();
        $table->id = 'consentform_activitytable';
        $table->attributes['class'] = 'flexible generaltable generalbox';
        $table->head = consentform_generate_table_header();
        $table->data = consentform_generate_table_content($course, $cm->id);

        echo consentform_render_table($table);
        echo $OUTPUT->single_button($redirecturl, get_string("backbutton", "consentform"));

        $jsparams = array('cmid' => $cm->id);
        $PAGE->requires->js_call_amd('mod_consentform/checkboxclicked', 'init', array($jsparams));
        $PAGE->requires->js_call_amd('mod_consentform/checkboxcontroller', 'init');

    } else {  // Participant's view, lack the right to submit.
        // Agreement form, participant's view.
        $mform = new \mod_consentform\consentform_agreement_form(null,
            array('id' => $id,
                'text' => $consentform->confirmationtext,
                'cmid' => $cm->id,
                'courseid' => $course->id,
                'consentform' => $consentform,
                'userid' => $USER->id,
                'confirmationtextclass' => 'consentform_confirmationtext'
            ));
        // Process participant's agreement form data and redirect.
        if ($data = $mform->get_data()) {
            if (isset($data->agreement)) {
                $ok = consentform_save_agreement(EXPECTEDCOMPLETIONVALUE, $USER->id, $cm->id);
                $message = get_string('msgagreed', 'consentform');
                $event = \mod_consentform\event\agreement_agree::create(
                    array(
                        'objectid' => $PAGE->cm->id,
                        'context' => $PAGE->context
                    )
                );
            } else if (isset($data->revocation)) {
                $ok = consentform_save_agreement(CONSENTFORM_STATUS_REVOKED, $USER->id, $cm->id);
                $message = get_string('msgrevoked', 'consentform');
                $event = \mod_consentform\event\agreement_revoke::create(
                    array(
                        'objectid' => $PAGE->cm->id,
                        'context' => $PAGE->context
                    )
                );
            } else if (isset($data->refusal)) {
                $ok = consentform_save_agreement(CONSENTFORM_STATUS_REFUSED, $USER->id, $cm->id);
                $message = get_string('msgrefused', 'consentform');
                $event = \mod_consentform\event\agreement_refuse::create(
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
            echo $OUTPUT->header();
            echo $OUTPUT->heading(format_string($consentform->name));
            if (!$consentform->confirmincourseoverview) {
                if ($consentform->intro) {
                    echo $OUTPUT->box(format_module_intro(
                        'consentform', $consentform, $cm->id), 'generalbox mod_introbox', 'consentformintro');
                }
            }
            echo $OUTPUT->box_start('', 'consentform_main_cointainer');
            $mform->display();
            echo $OUTPUT->box_end();
        }
    }
}

// Finish the page.
echo $OUTPUT->footer();
