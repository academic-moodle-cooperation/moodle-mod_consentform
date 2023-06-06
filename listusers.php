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

/** List of users and their status
 *
 * @package    mod_consentform
 * @copyright  2021 Thomas Niedermaier, Medical University of Vienna (thomas.niedermaier@meduniwien.ac.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
if ($id) {
    $cm           = get_coursemodule_from_id('consentform', $id, 0, false, MUST_EXIST);
    $course       = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $consentform  = $DB->get_record('consentform', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    die('You must specify a course_module ID');
}

$deleteuseraction = optional_param('delete', null, PARAM_INT); // User-ID to delete own test action.
$sortkey   = optional_param('sortkey', 'lastname', PARAM_ALPHA); // Sorted view: lastname|firstname|email|timestamp.
$sortorder = optional_param('sortorder', 'ASC', PARAM_ALPHA);   // Defines the order of the sorting (ASC or DESC).
$tab  = optional_param('tab', 1, PARAM_INT); // ID of tab of listusers.php.

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

if (!(has_capability('mod/consentform:submit', $context) || is_siteadmin())) {
    redirect(new moodle_url('/mod/consentform/view.php', array('id' => $id, 'sesskey' => sesskey())));
}

if ($deleteuseraction) {
    require_sesskey();
    $thisurl = new moodle_url('/mod/consentform/listusers.php',
        array('id' => $cm->id, 'sortkey' => $sortkey, 'sortorder' => $sortorder, 'sesskey' => sesskey()));
    if ($DB->delete_records('consentform_state', array('consentformcmid' => $cm->id, 'userid' => $USER->id))) {
        redirect($thisurl, get_string("deletetestmessage", "consentform"), 0, 'notify');
    } else {
        redirect($thisurl, get_string("deletetesterrormessage", "consentform"), 0, 'error');
    }
}

$event = \mod_consentform\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $consentform);
$event->trigger();

// Print the page header.
$PAGE->set_url('/mod/consentform/listusers.php', array('id' => $cm->id));
$PAGE->set_title(format_string($consentform->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('limitedwidth');

// Output starts here.
consentform_showheaderwithoutintro($consentform->id);

$coursecontext = context_course::instance($course->id);

list($sumagreed, $sumrefused, $sumrevoked, $sumnoaction, $sumall) =
    consentform_statistics_listusers($coursecontext, $cm->id);

$url = new moodle_url('/mod/consentform/listusers.php', array('id' => $id));
// Get tabs for display.

$thirdnav = array();
$url->param('tab', CONSENTFORM_STATUS_AGREED);
$thirdnavlink[CONSENTFORM_STATUS_AGREED] = $url->out();
$url->param('tab', CONSENTFORM_STATUS_REFUSED);
$thirdnavlink[CONSENTFORM_STATUS_REFUSED] = $url->out();
$url->param('tab', CONSENTFORM_STATUS_REVOKED);
$thirdnavlink[CONSENTFORM_STATUS_REVOKED] = $url->out();
$url->param('tab', CONSENTFORM_STATUS_NOACTION);
$thirdnavlink[CONSENTFORM_STATUS_NOACTION] = $url->out();
$url->param('tab', CONSENTFORM_ALL);
$thirdnavlink[CONSENTFORM_ALL] = $url->out();

$thirdnav[$thirdnavlink[CONSENTFORM_STATUS_AGREED]] =
    get_string('titleagreed', 'consentform')." (".$sumagreed.")";
$thirdnav[$thirdnavlink[CONSENTFORM_STATUS_REFUSED]] =
    get_string('titlerefused', 'consentform') . " (" . $sumrefused . ")";
$thirdnav[$thirdnavlink[CONSENTFORM_STATUS_REVOKED]] =
    get_string('titlerevoked', 'consentform') . " (" . $sumrevoked . ")";
$thirdnav[$thirdnavlink[CONSENTFORM_STATUS_NOACTION]] =
    get_string('titlenone', 'consentform')." (".$sumnoaction.")";
$thirdnav[$thirdnavlink[CONSENTFORM_ALL]] =
    get_string('titleall', 'consentform')." (".$sumall.")";

$urlselector = new \url_select($thirdnav, $thirdnavlink[$tab]);

$download = false;
$title = "";
switch ($tab) {
    case CONSENTFORM_STATUS_AGREED:
        if ($sumagreed) {
            $download = true;
        }
        $title = get_string('titleagreed', 'consentform');
        break;
    case CONSENTFORM_STATUS_REFUSED:
        if ($sumrefused) {
            $download = true;
        }
        $title = get_string('titlerefused', 'consentform');
        break;
    case CONSENTFORM_STATUS_REVOKED:
        if ($sumrevoked) {
            $download = true;
        }
        $title = get_string('titlerevoked', 'consentform');
        break;
    case CONSENTFORM_STATUS_NOACTION:
        if ($sumnoaction) {
            $download = true;
        }
        $title = get_string('titlenone', 'consentform');
        break;
    case CONSENTFORM_ALL:
        if ($sumall) {
            $download = true;
        }
        $title = get_string('titleall', 'consentform');
}

echo html_writer::start_div('d-inline-block');
echo $OUTPUT->render($urlselector);
echo html_writer::end_div();

if ($download) {
    $exportlink = new moodle_url('exportcsv.php', array(
        'id' => $id, 'tab' => $tab, 'sortkey' => $sortkey, 'sortorder' => $sortorder));
    $exporttext = get_string('downloadbuttonlabel', 'consentform');
    echo html_writer::start_div('d-inline-block ml-3');
    echo html_writer::link($exportlink, $exporttext,  ['class' => 'btn btn-primary']);
    echo html_writer::end_div();
}

echo html_writer::tag('h2', $title, array("class" => "mt-3"));

$listusers = consentform_get_listusers($sortkey, $sortorder, $tab, $coursecontext, $cm);

// Display users and their status.
echo consentform_display_participants($listusers, $cm->id, consentform_get_sqlsortkey($sortkey), $sortorder, $tab);

// Finish the page.
echo $OUTPUT->footer();
