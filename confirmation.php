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
 * Confirmation Buttons page which is used in an iframe in the module description.
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Instance ID.

$consentform  = $DB->get_record('consentform', array('id' => $id), '*', MUST_EXIST);

list($course, $cm) = get_course_and_cm_from_instance($id, 'consentform', $consentform->course);

require_login($course, false, $cm);

$locked = false;
$contextcoursecat = context_coursecat::instance($course->category);
if ($contextcoursecat->locked) {
    $locked = true;
} else {
    $contextcourse = context_course::instance($cm->course);
    if ($contextcourse->locked) {
        $locked = true;
    } else {
        $contextmodule = context_module::instance($cm->id);
        if ($contextmodule->locked) {
            $locked = true;
        }
    }
}

// Agreement form, participant's view.
$mform = new \mod_consentform\consentform_agreement_form(null,
    array('id' => $id,
        'cmid' => $cm->id,
        'courseid' => $course->id,
        'consentform' => $consentform,
        'userid' => $USER->id,
        'confirmationtextclass' => 'consentform_confirmationtext_incourseoverview',
        'locked' => $locked
    ));
// Process participant's agreement form data and redirect.
if ($data = $mform->get_data()) {
    $PAGE->set_url('/mod/consentform/confirmation.php', array('id' => $cm->id));
    $PAGE->set_title(format_string($consentform->name));
    $PAGE->set_pagelayout('embedded');
    if (isset( $data->agreement) && $data->agreement == $consentform->textagreementbutton) {
        $ok = consentform_save_agreement(EXPECTEDCOMPLETIONVALUE, $USER->id, $cm->id);
        $message = get_string('msgagreed', 'consentform');
        $event = \mod_consentform\event\agreement_agree::create(
            array(
                'objectid' => $PAGE->cm->id,
                'context' => $PAGE->context
            )
        );
    } else if (isset($data->revocation) && $data->revocation == $consentform->textrevocationbutton) {
        $ok = consentform_save_agreement(CONSENTFORM_STATUS_REVOKED, $USER->id, $cm->id);
        $message = get_string('msgrevoked', 'consentform');
        $event = \mod_consentform\event\agreement_revoke::create(
            array(
                'objectid' => $PAGE->cm->id,
                'context' => $PAGE->context
            )
        );
    } else if (isset($data->refusal) && $data->refusal == $consentform->textrefusalbutton) {
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

    $redirecturl = new moodle_url('/mod/consentform/confirmation.php', array('id' => $id));
    $SESSION->consentform_reloadiframe = "1";
    redirect($redirecturl);

} else {  // No data from form.
    if (isset($SESSION->consentform_reloadiframe)) {
        unset($SESSION->consentform_reloadiframe);
        // Reload parent after form processing.
        echo html_writer::script('parent.location.reload();');
    } else {
        // Show inline form only if there is no user's action yet.
        if (!$DB->record_exists('consentform_state', array('consentformcmid' => $cm->id, 'userid' => $USER->id))) {
            // Display agreement form to participant.
            $PAGE->set_url('/mod/consentform/confirmation.php', array('id' => $cm->id));
            $PAGE->set_title(format_string($consentform->name));
            $PAGE->set_pagelayout('login');
            echo $OUTPUT->header();
            echo $OUTPUT->box_start('', 'consentform_main_cointainer');
            $mform->display();
            echo $OUTPUT->box_end();
        } else {
            $jscode = "window.onload=function(){var myIframe;if (myIframe = window.parent.document.getElementsByName('";
            $jscode .= "consentformiframe$consentform->id')[0]) {";
            $jscode .= "myIframe.remove();}}";
            echo html_writer::script($jscode);
        }
    }
}
