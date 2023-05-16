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
 * Main page of module consentform
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
if ($id) {
    $cm         = get_coursemodule_from_id('consentform', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $consentform  = $DB->get_record('consentform', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    die('You must specify a course_module ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
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

        // Print header and start_div.
        echo $OUTPUT->header();
        echo html_writer::start_div();

        // Link buttons to module list (optional) and users list
        $mllink = new moodle_url('modulelist.php', array('id' => $id));
        $mllinktext = get_string('modulelistlinktext', 'consentform');
        $lulink = new moodle_url('/mod/consentform/listusers.php', array('id' => $id));
        $lulinktext = get_string('listusers', 'consentform');
        // Show information message if course module list is deactivated.
        if ($consentform->nocoursemoduleslist) {
            consentform_shownocoursemodulelistinfo($id);
        } else {
            echo html_writer::link($mllink, $mllinktext,  ['class' => 'btn btn-primary']);
        }
        echo html_writer::link($lulink, $lulinktext,  ['class' => 'btn btn-secondary ml-2']);

        // Print list of user reaction statistics.
        $coursecontext = context_course::instance($course->id);
        list($sumagreed, $sumrefused, $sumrevoked, $sumnoaction, $sumall) =
            consentform_statistics_listusers($coursecontext, $cm->id);
        $linkclass = array("class" => "list-group-item d-flex justify-content-between align-items-center");
        $badgeclass = "badge badge-primary badge-pill";
        $badgeclassnull = "badge badge-secondary badge-pill";
        echo html_writer::start_div('list-group mt-3');
        $lulink->param('tab', CONSENTFORM_STATUS_AGREED);
        $lulinktext = get_string('titleagreed', 'consentform').
            html_writer::span($sumagreed, $sumagreed ? $badgeclass : $badgeclassnull);
        echo html_writer::link($lulink, $lulinktext, $linkclass);
        $lulink->param('tab', CONSENTFORM_STATUS_REFUSED);
        $lulinktext = get_string('titlerefused', 'consentform').
            html_writer::span($sumrefused, $sumrefused ? $badgeclass : $badgeclassnull);
        echo html_writer::link($lulink, $lulinktext, $linkclass);
        $lulink->param('tab', CONSENTFORM_STATUS_REVOKED);
        $lulinktext = get_string('titlerevoked', 'consentform').
            html_writer::span($sumrevoked, $sumrevoked ? $badgeclass : $badgeclassnull);
        echo html_writer::link($lulink, $lulinktext, $linkclass);
        $lulink->param('tab', CONSENTFORM_STATUS_NOACTION);
        $lulinktext = get_string('titlenone', 'consentform').
            html_writer::span($sumnoaction, $sumnoaction ? $badgeclass : $badgeclassnull);
        echo html_writer::link($lulink, $lulinktext, $linkclass);
        $lulink->param('tab', CONSENTFORM_ALL);
        $lulinktext = get_string('titleall', 'consentform').
            html_writer::span($sumall, $sumall ? $badgeclass : $badgeclassnull);
        echo html_writer::link($lulink, $lulinktext, $linkclass);

        echo html_writer::end_div(); // Reactions list.

        echo html_writer::end_div(); // Content main page.

    } else {  // Participant's view, lacks the right to submit.
        // Agreement form, participant's view.
        $mform = new \mod_consentform\consentform_agreement_form(null,
            array('id' => $id,
                'cmid' => $cm->id,
                'courseid' => $course->id,
                'consentform' => $consentform,
                'userid' => $USER->id,
                'confirmationtextclass' => 'consentform_confirmationtext',
                'locked' => $locked
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
            consentform_showheaderwithoutintro($consentform->id);
            echo $OUTPUT->box_start('', 'consentform_main_cointainer');
            $mform->display();
            echo $OUTPUT->box_end();
        }
    }
}

// Finish the page.
echo $OUTPUT->footer();
