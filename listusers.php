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
 * @package    mod_consentfom
 * @copyright  2021 Thomas Niedermaier Medizinische Universitaet Wien (thomas.niedermaier@meduniwien.ac.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID
$deleteuseraction = optional_param('delete', null, PARAM_INT); // User-ID to delete own test action.

if ($id) {
    $cm           = get_coursemodule_from_id('consentform', $id, 0, false, MUST_EXIST);
    $course       = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $consentform  = $DB->get_record('consentform', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    die('You must specify a course_module ID');
}

$sortkey   = optional_param('sortkey', 'lastname', PARAM_ALPHA); // Sorted view: lastname | firstname | email | timestamp
$sortorder = optional_param('sortorder', 'ASC', PARAM_ALPHA);   // Defines the order of the sorting (ASC or DESC)

$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);

require_login($course, true, $cm);

if ($deleteuseraction) {
    $thisurl = new moodle_url('/mod/consentform/listusers.php', array('id' => $cm->id, 'sortkey' => $sortkey, 'sortorder' => $sortorder));
    if ($DB->delete_records('consentform_state', array('consentformcmid' => $cm->id, 'userid' => $USER->id))) {
        redirect($thisurl, get_string("deletetestmessage", "consentform"), 'notify');
    } else {
        redirect($thisurl, get_string("deletetesterrormessage", "consentform"), 'error');
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

$PAGE->set_url('/mod/consentform/listusers.php', array('id' => $cm->id, 'sortkey' => $sortkey, 'sortorder' => $sortorder));
$PAGE->set_title(format_string($consentform->name));
$PAGE->set_heading(format_string($course->fullname));

// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($consentform->name));

$tab  = optional_param('tab', 1, PARAM_INT);

require("tabs.php");

$sqlresult = null;
require("sql.php");

if ($download) {
    $xform = new \mod_consentform\consentform_export_form('exportcsv.php?id=' . $cm->id,
        null, null, null, array("id" => "consentform_export_form"));
    $data = new stdClass();
    $data->id = $cm->id;
    $data->tab = $tab;
    $data->sortkey = $sortkey;
    $data->sortorder = $sortorder;
    $xform->set_data($data);
    echo $OUTPUT->box_start();
    $xform->display();
    echo $OUTPUT->box_end();
}

if (array_key_exists($USER->id, $userswithaction)) {
    $deletelink = new moodle_url($PAGE->url, array("delete" => "1"));
    echo $OUTPUT->box_start();
    echo $OUTPUT->single_button($deletelink, get_string("deletetestaction", "consentform"));
    echo $OUTPUT->box_end();
}

// Display users and their status.
list($listnotempty, $htmltable) = consentform_display_participants($sqlresult, $cm->id, $sqlsortkey, $sortorder, $tab);

echo $htmltable;

if (!$consentform->nocoursemoduleslist) {
    echo $OUTPUT->single_button(new moodle_url('view.php', array('id' => $cm->id)),
        get_string("backbuttonlist", "consentform"));
}

// Finish the page.
echo $OUTPUT->footer();

//**************************************************************************************************
function consentform_display_participants($sqlresult, $cmid, $sortkey, $sortorder, $tab) {

    $index = 0;
    $urlinit  = '/mod/consentform/listusers.php?';
    $urlinit .= 'id=' . $cmid;
    $urlinit .= '&sesskey=' . sesskey();
    $urlinit .= '&tab=' . $tab;

    foreach ($sqlresult as $row) {

        if ($index == 0) {

            $table = new html_table();
            $table->head = array(
                "",
                consentform_tableheader_column("lastname", get_string('lastname'),
                    $urlinit, $sortkey, $sortorder),
                consentform_tableheader_column("firstname", get_string('firstname'),
                    $urlinit, $sortkey, $sortorder),
                consentform_tableheader_column("email", get_string('email'),
                    $urlinit, $sortkey, $sortorder),
                consentform_tableheader_column("timestamp", get_string('timestamp', 'consentform'),
                    $urlinit, $sortkey, $sortorder),
                get_string('status'),
                );
            $table->align = array(
                'right',
                'left',
                'left',
                'left',
                'center',
                'center',
            );

        } // end if index=0

        $index++;
        $table->data[]  = array(
            $index,
            $row->lastname,
            $row->firstname,
            $row->email,
            $row->timestamp != CONSENTFORM_NOTIMESTAMP ? userdate($row->timestamp) : CONSENTFORM_NOTIMESTAMP,
            consentform_display_status($row->state),
            );

    }  // for each row

    $returnok = false;

    if ($index == 0) {
        $html = html_writer::tag('p', get_string('listempty', 'consentform'), array('class' => 'alert-warning'));
    } else {
        $html = html_writer::table($table);
        $returnok = true;
    }

    return array($returnok, $html);

} // end function
//*******************************

//**************************************************************************************************
function consentform_tableheader_column($column, $columntitle, $urlinit, $sortkey, $sortorder) {
    global $OUTPUT;

    $url = $urlinit . "&sortkey=" . $column;

    if ($column == $sortkey) {
        if ($sortorder == "DESC") {
            $icon = $OUTPUT->image_icon('t/sort_desc', get_string('sort'), 'moodle', array(
                'style' => 'cursor:pointer;margin-left:2px;nowrap'));
            $url .= "&sortorder=ASC";
        } else {
            $icon = $OUTPUT->image_icon('t/sort_asc', get_string('sort'), 'moodle', array(
                'style' => 'cursor:pointer;margin-left:2px;nowrap'));
            $url .= "&sortorder=DESC";
        }
    } else {
        $icon = $OUTPUT->image_icon('t/sort_by', get_string('sort'), 'moodle', array(
            'style' => 'cursor:pointer;margin-left:2px;nowrap'));
        $url .= "&sortorder=ASC";
    }

    $linkstr = html_writer::link($url, $columntitle . $icon);

    return $linkstr;

} // end function
//*******************************

//**************************************************************************************************
function consentform_display_status($status) {

    switch ($status) {
        case "1":
            return html_writer::span(get_string("agreed", "consentform"), "agreed");
            break;
        case "0":
            return html_writer::span(get_string("revoked", "consentform"), "revoked");
            break;
        case "-1":
            return html_writer::span(get_string("refused", "consentform"), "refused");
            break;
        default:
            return html_writer::span(get_string("noaction", "consentform"));
            break;
    }
} // end function
//*******************************
