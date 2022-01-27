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
 * Internal library of functions for module consentform
 *
 * All the consentform specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Generate rows of coursemodules list table
 *
 * @param $course dataset of course
 * @param $cmidcontroller course module id of this consentform instance
 * @return array|void array of table rows
 *
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function consentform_generate_coursemodulestable_content($course, $cmidcontroller) {
    global $PAGE;

    $modinfo = get_fast_modinfo($course);
    $coursemodules = $modinfo->get_cms();
    $sections = $modinfo->get_section_info_all();
    $context = $PAGE->cm->context;

    $rows = array();
    $sectionibefore = "";
    $usercanviewsection = true;
    $cmindex = 0;
    $a = new stdClass();
    $a->course = $course->fullname;
    foreach ($coursemodules as $cmid => $cminfo) {
        if ($cminfo->modname != 'consentform' && !$cminfo->deletioninprogress) {
            $sectioni = $cminfo->sectionnum;
            if ($sectioni != $sectionibefore) {
                if (!($sectioninfo = $modinfo->get_section_info($sectioni))) { // If this section doesn't exist.
                    throw new moodle_exception('unknowncoursesection', 'error', null, $a);
                    return;
                }
                if (!$sectioninfo->uservisible) {
                    $usercanviewsection = false;
                } else {
                    if ($sectioni != 0) {
                        $usercanviewsection = true;
                        $row = new html_table_row();
                        $sectionname = $sections[$sectioni]->name;
                        $sectionname = $sectionname ? $sectionname : get_string("section", "moodle") . " " . (string)($sectioni);

                        $nourl = $PAGE->url . "#";
                        $cell = new html_table_cell($sectionname . '&nbsp;&nbsp;' .
                            \html_writer::link($nourl, get_string('all', 'moodle'),
                                ['class' => "co_section_all section$sectioni"]).' / '.
                            \html_writer::link($nourl, get_string('none', 'moodle'),
                                ['class' => "co_section_none section$sectioni"]));
                        $cell->attributes['class'] = "consentform_activitytable_checkboxcolumn$sectioni";
                        $cell->colspan = "2";

                        $row->cells[] = $cell;
                        $row->attributes['class'] = "consentform_activitytable_sectionrow";
                        $rows[] = $row;
                    }
                }
                $sectionibefore = $sectioni;
            }
            if ($usercanviewsection) {
                $modname = $cminfo->modname;
                if (has_capability("mod/$modname:addinstance", $context)) {
                    $row = new html_table_row();
                    $cmidcontrolled = $cmid;
                    $cfcontrolled = consentform_find_entry_availability($cmidcontrolled, $cmidcontroller);
                    $checked = $cfcontrolled <= 0 ? 0 : 1;
                    $checkboxattributes = array('class' => "selectcoursemodule section$sectioni");
                    if ($cfcontrolled == 2 || $cfcontrolled == -1) {
                        $checkboxattributes['disabled'] = "disabled";
                    }
                    $cell = new html_table_cell(
                        html_writer::checkbox("selectcoursemodule[]", $cmid, $checked, '',
                            $checkboxattributes)
                    );
                    if ($cfcontrolled == 2) {
                        $cell->text .= consentform_geticon_userentry();
                    } else if ($cfcontrolled == -1) {
                        $cell->text .= consentform_geticon_userentry_negative();
                    }
                    $cell->attributes['class'] = 'consentform_activitytable_checkboxcolumn';
                    $row->cells[] = $cell;
                    $viewurl = new moodle_url('/course/modedit.php', array('update' => $cmid));
                    $activitylink = html_writer::empty_tag('img', array('src' => $cminfo->get_icon_url(),
                            'class' => 'iconlarge activityicon', 'alt' => $cminfo->modfullname,
                            'title' => $cminfo->modfullname, 'role' => 'presentation')) .
                            html_writer::tag('span', $cminfo->name, array('class' => 'instancename'));
                    $row->cells[] = new html_table_cell(
                        html_writer::start_div('activity').html_writer::link($viewurl, $activitylink).
                        html_writer::end_div()
                    );
                    $row->attributes['class'] = "consentform_activitytable_activityrow";
                    $rows[] = $row;
                    $cmindex++;
                }
            } // End if user can view.

        } // End if not this cmid.
    }  // End foreach.

    return $rows;
}

/**
 * @return string html string of warning icon, if a not regular user entry for consentform was found in
 * course module's availability
 * @throws coding_exception
 */
function consentform_geticon_userentry() {
    global $OUTPUT;

    $attributes = array();
    $attributes['data-toggle'] = "tooltip";
    $string = get_string("warninguserentry", "mod_consentform");
    $attributes['title'] = $string;
    $icon = $OUTPUT->pix_icon('i/incorrect', $string, 'moodle', $attributes);

    return $icon;
}

/**
 * @return string html string of warning icon, if a negative consentform-like entry was found in
 * course module's availability
 * @throws coding_exception
 */
function consentform_geticon_userentry_negative() {
    global $OUTPUT;

    $attributes = array();
    $attributes['data-toggle'] = "tooltip";
    $string = get_string("warninguserentry", "mod_consentform");
    $attributes['title'] = $string;
    $icon = $OUTPUT->pix_icon('i/invalid', $string, 'moodle', $attributes);

    return $icon;
}

/**
 * Returns header of course modules list table
 *
 * @return array table header row
 * @throws coding_exception
 */
function consentform_generate_coursemodulestable_header() {
    global $PAGE;

    $header = array();
    $nourl = $PAGE->url . "#";
    $cell = new html_table_cell(
        \html_writer::link($nourl, get_string('all', 'moodle'), ['class' => 'co_all']).' / '.
        \html_writer::link($nourl, get_string('none', 'moodle'), ['class' => 'co_none']));
    $cell->header = true;
    $header[] = $cell;
    $cell = new html_table_cell(get_string('dependent', 'consentform'));
    $cell->header = true;
    $header[] = $cell;

    return $header;
}

/**
 * Returns the coursemodules list table html
 *
 * @param html_table $table
 * @param bool $printfooter
 * @param false $overrideevenodd
 * @return string
 */
function consentform_render_coursemodulestable(html_table $table, $printfooter = true, $overrideevenodd = false) {
    // Prepare table data and populate missing properties with reasonable defaults.
    if (!empty($table->align)) {
        foreach ($table->align as $key => $aa) {
            if ($aa) {
                $table->align[$key] = 'text-align:'. fix_align_rtl($aa) .';';  // Fix for RTL languages.
            } else {
                $table->align[$key] = null;
            }
        }
    }
    if (!empty($table->size)) {
        foreach ($table->size as $key => $ss) {
            if ($ss) {
                $table->size[$key] = 'width:'. $ss .';';
            } else {
                $table->size[$key] = null;
            }
        }
    }
    if (!empty($table->wrap)) {
        foreach ($table->wrap as $key => $ww) {
            if ($ww) {
                $table->wrap[$key] = 'white-space:nowrap;';
            } else {
                $table->wrap[$key] = '';
            }
        }
    }
    if (!empty($table->head)) {
        foreach ($table->head as $key => $val) {
            if (!isset($table->align[$key])) {
                $table->align[$key] = null;
            }
            if (!isset($table->size[$key])) {
                $table->size[$key] = null;
            }
            if (!isset($table->wrap[$key])) {
                $table->wrap[$key] = null;
            }

        }
    }
    if (empty($table->attributes['class'])) {
        $table->attributes['class'] = 'generaltable';
    }

    // Explicitly assigned properties override those defined via $table->attributes.
    $table->attributes['class'] = trim($table->attributes['class']);
    $attributes = array_merge(
        $table->attributes, array(
            'id'            => $table->id,
            'summary'       => $table->summary,
        )
    );
    $output = html_writer::start_tag('table', $attributes) . "\n";

    $countcols = 0;

    $headfoot = $printfooter ? array('thead', 'tfoot') : array('thead');

    if (!empty($table->head)) {
        foreach ($headfoot as $tag) {
            $countcols = count($table->head);

            $output .= html_writer::start_tag($tag, array()) . "\n";
            $output .= html_writer::start_tag('tr', array()) . "\n";
            $keys = array_keys($table->head);
            $lastkey = end($keys);

            foreach ($table->head as $key => $heading) {
                // Convert plain string headings into html_table_cell objects.
                if (!($heading instanceof html_table_cell)) {
                    $headingtext = $heading;
                    $heading = new html_table_cell();
                    $heading->text = $headingtext;
                    $heading->header = true;
                }

                if ($heading->header !== false) {
                    $heading->header = true;
                }

                if ($heading->header && empty($heading->scope)) {
                    $heading->scope = 'col';
                }

                $heading->attributes['class'] .= ' header c' . $key;
                if (isset($table->headspan[$key]) && $table->headspan[$key] > 1) {
                    $heading->colspan = $table->headspan[$key];
                    $countcols += $table->headspan[$key] - 1;
                }

                if ($key == $lastkey) {
                    $heading->attributes['class'] .= ' lastcol';
                }
                if (isset($table->colclasses[$key])) {
                    $heading->attributes['class'] .= ' ' . $table->colclasses[$key];
                }
                $heading->attributes['class'] = trim($heading->attributes['class']);
                $attributes = array_merge(
                    $heading->attributes, array(
                        'style'     => $table->align[$key] . $table->size[$key] . $heading->style,
                        'scope'     => $heading->scope,
                        'colspan'   => $heading->colspan,
                    )
                );

                $tagtype = 'td';
                if ($heading->header === true) {
                    $tagtype = 'th';
                }
                $output .= html_writer::tag($tagtype, $heading->text, $attributes) . "\n";
            }
            $output .= html_writer::end_tag('tr') . "\n";
            $output .= html_writer::end_tag($tag) . "\n";
        }

        if (empty($table->data)) {
            // For valid XHTML strict every table must contain either a valid tr
            // or a valid tbody... both of which must contain a valid td.
            $output .= html_writer::start_tag('tbody', array('class' => 'empty'));
            $output .= html_writer::tag('tr', html_writer::tag('td', '', array('colspan' => count($table->head))));
            $output .= html_writer::end_tag('tbody');
        }
    }

    if (!empty($table->data)) {
        $oddeven    = 1;
        $keys       = array_keys($table->data);
        $lastrowkey = end($keys);
        $output .= html_writer::start_tag('tbody', array());

        foreach ($table->data as $key => $row) {
            if (($row === 'hr') && ($countcols)) {
                $output .= html_writer::tag(
                    'td', html_writer::tag('div', '', array('class' => 'tabledivider')),
                    array('colspan' => $countcols)
                );
            } else {
                // Convert array rows to html_table_rows and cell strings to html_table_cell objects.
                if (!($row instanceof html_table_row)) {
                    $newrow = new html_table_row();

                    foreach ($row as $item) {
                        $cell = new html_table_cell();
                        $cell->text = $item;
                        $newrow->cells[] = $cell;
                    }
                    $row = $newrow;
                }

                $oddeven = $oddeven ? 0 : 1;
                if (isset($table->rowclasses[$key])) {
                    $row->attributes['class'] .= ' ' . $table->rowclasses[$key];
                }

                if (!$overrideevenodd) {
                    $row->attributes['class'] .= ' r' . $oddeven;
                }

                if ($key == $lastrowkey) {
                    $row->attributes['class'] .= ' lastrow';
                }

                if (!isset($row->attributes['name'])) {
                    $row->attributes['name'] = '';
                }

                $output .= html_writer::start_tag(
                        'tr',
                        array('class' => trim($row->attributes['class']),
                            'style' => $row->style, 'id' => $row->id, 'name' => trim($row->attributes['name']))
                    )
                    . "\n";
                $keys2 = array_keys($row->cells);
                $lastkey = end($keys2);

                $gotlastkey = false; // Flag for sanity checking.
                foreach ($row->cells as $key => $cell) {
                    if ($gotlastkey) {
                        // This should never happen. Why do we have a cell after the last cell?
                        mtrace("A cell with key ($key) was found after the last key ($lastkey)");
                    }

                    if (!($cell instanceof html_table_cell)) {
                        $mycell = new html_table_cell();
                        $mycell->text = $cell;
                        $cell = $mycell;
                    }

                    if (($cell->header === true) && empty($cell->scope)) {
                        $cell->scope = 'row';
                    }

                    if (isset($table->colclasses[$key])) {
                        $cell->attributes['class'] .= ' ' . $table->colclasses[$key];
                    }

                    $cell->attributes['class'] .= ' cell c' . $key;
                    if ($key == $lastkey) {
                        $cell->attributes['class'] .= ' lastcol';
                        $gotlastkey = true;
                    }
                    $tdstyle = '';
                    $tdstyle .= isset($table->align[$key]) ? $table->align[$key] : '';
                    $tdstyle .= isset($table->size[$key]) ? $table->size[$key] : '';
                    $tdstyle .= isset($table->wrap[$key]) ? $table->wrap[$key] : '';
                    $cell->attributes['class'] = trim($cell->attributes['class']);
                    $tdattributes = array_merge(
                        $cell->attributes, array(
                            'style' => $tdstyle . $cell->style,
                            'colspan' => $cell->colspan,
                            'rowspan' => $cell->rowspan,
                            'id' => $cell->id,
                            'abbr' => $cell->abbr,
                            'scope' => $cell->scope,
                        )
                    );
                    $tagtype = 'td';
                    if ($cell->header === true) {
                        $tagtype = 'th';
                    }
                    $output .= html_writer::tag($tagtype, $cell->text, $tdattributes) . "\n";
                }
            }
            $output .= html_writer::end_tag('tr') . "\n";
        }
        $output .= html_writer::end_tag('tbody') . "\n";
    }
    $output .= html_writer::end_tag('table') . "\n";
    $output = html_writer::tag('div', $output, array('style' => 'overflow: auto; width: 100%'));
    return $output;
}


/**
 * Find consentform completion entry in availability of course_module
 *
 * @param $cmidcontrolled  course module id of this consentform instance
 * @param $cmidcontroller  id of course module which relies on this consentform instance
 * @return bool $ret 0...not found, 1...consentform entry found, 2...user entry found
 * @throws dml_exception
 */
function consentform_find_entry_availability($cmidcontrolled, $cmidcontroller) {
    global $DB;

    $ret = 0;
    $availability = $DB->get_field('course_modules', 'availability', array('id' => $cmidcontrolled));
    $availability = json_decode($availability);
    if (isset($availability->c) && isset($availability->op)) {
        if (count($availability->c) > 0) {
            $condition = $availability->c[0];
        } else {
            return $ret;
        }
        // Genuine consentform condition?
        if (isset($condition->type) && $condition->type == 'completion' && $availability->op == "&") {
            if ($condition->cm == $cmidcontroller) {
                $ret = 1;
            }
        }
        // Negative user entry?
        if (isset($condition->type) && $condition->type == 'completion' && $availability->op == "!&") {
            if ($condition->cm == $cmidcontroller) {
                $ret = -1;
            }
        }
        // Otherwise user condition anywhere in availability?
        if (!$ret) {
            if (consentform_find_entry_availability_anywhere($availability->c, $cmidcontrolled, $cmidcontroller)) {
                $ret = 2;
            }
        }
    }

    return $ret;
}

/**
 * Find completion entry anywhere in availability of course module (recursive)
 *
 * @param $conditions condition or condiionlist of availability
 * @param $cmidcontrolled  course module id of this consentform instance
 * @param $cmidcontroller  id of course module which relies on this consentform instance
 * @return bool $ret 0...not found, 1...consentform entry found, 2...user entry found
 * @throws dml_exception
 */
function consentform_find_entry_availability_anywhere($conditions, $cmidcontrolled, $cmidcontroller) {

    foreach ($conditions as $condition) {
        if (isset($condition->c)) { // If conditionlist.
            if (consentform_find_entry_availability_anywhere($condition->c, $cmidcontrolled, $cmidcontroller)) {
                return true;
            }
        } else {
            if ($condition->type == 'completion') {
                if ($condition->cm == $cmidcontroller) {
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * Insert condition entry in course_module x
 *
 * @param $courseid        id of this course
 * @param $cmidcontrolled  id of course module which relies on this CF instance
 * @param $cmidcontroller  course module id of this CF instance
 * @return bool
 * @throws dml_exception
 */
function consentform_make_entry_availability($courseid, $cmidcontrolled, $cmidcontroller) {
    global $DB;

    $availabilityjsonstring = $DB->get_field('course_modules', 'availability', ['id' => $cmidcontrolled]);
    $availabilityold = json_decode($availabilityjsonstring);
    $availabilitynew = new stdClass();
    $availabilitynew->op = "&";
    $availabilitynew->c = array();
    $availabilitynew->showc = array();
    $newcondition = new stdClass();
    $newcondition->type = "completion";
    $newcondition->cm = $cmidcontroller;
    $newcondition->e = EXPECTEDCOMPLETIONVALUE;
    $availabilitynew->c[] = $newcondition;
    $availabilitynew->showc[] = true;
    if ($availabilityold) {
        $availabilitynew->c[] = $availabilityold;
        $availabilitynew->showc[] = true;
    }
    $availabilityjsonstring = json_encode($availabilitynew);
    $DB->set_field('course_modules', 'availability', $availabilityjsonstring, ['id' => $cmidcontrolled]);
    rebuild_course_cache($courseid, false);

    return true;
}

/**
 * Delete condition entry in course_module x
 *
 * @param $courseid        id of this course
 * @param $cmidcontrolled  id of course module which relies on this CF instance
 * @param $cmidcontroller  course module id of this CF instance
 * @return bool
 * @throws dml_exception
 */
function consentform_delete_entry_availability($courseid, $cmidcontrolled, $cmidcontroller) {
    global $DB;

    $availabilityjsonstring = $DB->get_field('course_modules', 'availability', ['id' => $cmidcontrolled]);
    $availability = json_decode($availabilityjsonstring);
    $found = false;
    if (isset($availability->c)) {
        $conditionslength = count($availability->c);
        if ($conditionslength == 1) { // If it is the only condition.
            $condition = $availability->c[0];
            if (isset($condition->type) && $condition->type == 'completion') {
                if ($condition->cm == $cmidcontroller) {
                    $availabilityjsonstring = null;
                    $found = true;
                }
            }
        } else if ($conditionslength == 2) { // There have been conditions before.
            $condition = $availability->c[0];
            if (isset($condition->type) && $condition->type == 'completion') {  // Do only if first condition is CF.
                if ($condition->cm == $cmidcontroller) {
                    // The second condition(list) will remain.
                    $subcondition = $availability->c[1];
                    // Sanitize showc or show if conditionlist.
                    if (isset($subcondition->op) && ($subcondition->op == "&" || $subcondition->op == "!|")) {
                        // No showc.
                        if (!isset($subcondition->showc) || count($subcondition->showc) != count($subcondition->c)) {
                            $subcondition->showc = array();
                            foreach ($subcondition->c as $c) {
                                $subcondition->showc[] = true;
                            }
                        }
                        $diffshowc = count($subcondition->showc) - count($subcondition->c);
                        if ($diffshowc > 0) { // Too many showc.
                            for ($i = 0; $i < $diffshowc; $i++) {
                                array_pop($subcondition->showc);
                            }
                        } else if ($diffshowc < 0) { // Not enough showc.
                            for ($i = 0; $i < $diffshowc; $i++) {
                                $subcondition->showc[] = true;
                            }
                        }
                        $availabilityjsonstring = json_encode($subcondition);
                    } else if (isset($subcondition->op)) { // If OR: Check show.
                        if (!isset($subcondition->show)) {
                            $subcondition->show = true;
                        }
                        $availabilityjsonstring = json_encode($subcondition);
                    } else { // Second condition is not a list.
                        $newcondition = new stdClass();
                        $newcondition->op = "&";
                        $newcondition->c = array($subcondition);
                        $newcondition->showc = array(true);
                        $availabilityjsonstring = json_encode($newcondition);
                    }
                    $found = true;
                }
            }
        } else if ($conditionslength > 2) { // There have been conditions before.
            $condition = $availability->c[0];
            if (isset($condition->type) && $condition->type == 'completion') {
                if ($condition->cm == $cmidcontroller) {
                    array_shift($availability->c);
                    array_shift($availability->showc);
                    $availabilityjsonstring = json_encode($availability);
                    $found = true;
                }
            }
        }
    }
    if ($found) {
        $DB->set_field('course_modules', 'availability', $availabilityjsonstring, ['id' => $cmidcontrolled]);
        rebuild_course_cache($courseid, false);
        return true;
    } else {
        return false;
    }

}

/**
 * Save agreement/refusal/revocation as completion and in consentform
 *
 * @param $status agreement/refusal/revocation
 * @param $userid user's id
 * @param $cmid id of this instance's coursemodule
 * @return bool
 * @throws dml_exception
 */
function consentform_save_agreement($status, $userid, $cmid) {
    global $DB;

    if ($id = $DB->get_field(
        'consentform_state', 'id', array('consentformcmid' => $cmid, 'userid' => $userid))) {
        $record = consentform_completionstate_record($id, $userid, $status, $cmid);
        $DB->update_record('consentform_state', $record);
    } else {
        $record = consentform_completionstate_record(null, $userid, $status, $cmid);
        $DB->insert_record('consentform_state', $record);
    }

    if ($status == EXPECTEDCOMPLETIONVALUE) {
        consentform_update_completionstate($cmid, $status);
    } else {
        consentform_update_completionstate($cmid, 0);
    }

    $instanceid = $DB->get_field('course_modules', 'instance', array('id' => $cmid));
    $consentform = $DB->get_record('consentform', array('id' => $instanceid));

    if ($consentform->usegrade) {
        if ($status == CONSENTFORM_STATUS_AGREED) {
            consentform_set_user_grade($consentform, $userid, GRADEVALUETOWRITE);
        } else {
            consentform_set_user_grade($consentform, $userid, null);
        }
    }

    return true;
}

/**
 * Build the record for saving the user's agreement/refusal/revocation
 *
 * @param $id       record id
 * @param $userid   user id of participant
 * @param $agreed   1 agreed, 0 revoked, -1 refused
 * @param $cmid     course module id
 * @return stdClass record object for inser or update db
 */
function consentform_completionstate_record($id, $userid, $agreed, $cmid) {

    $record = new stdClass();
    if ($id) {
        $record->id = $id;
    }
    $record->state = $agreed;
    $record->userid = $userid;
    $record->consentformcmid = $cmid;
    $record->timestamp = time();

    return $record;
}

/**
 * Enter grade value for all agreements when usegrade has been switched to on.
 *
 * @param $cmid id of this instance's coursemodule
 * @return bool
 * @throws dml_exception
 */
function consentform_usegradechange_writegrades($cmid) {
    global $DB;

    $instanceid = $DB->get_field('course_modules', 'instance', array('id' => $cmid));
    $consentform = $DB->get_record('consentform', array('id' => $instanceid));

    $records = $DB->get_records('consentform_state', ["consentformcmid" => $cmid, "state" => CONSENTFORM_STATUS_AGREED]);
    foreach ($records as $record) {
        consentform_set_user_grade($consentform, $record->userid, GRADEVALUETOWRITE);
    }

    rebuild_course_cache($consentform->course, false);

    return true;
}

/**
 * Update completions of this consentform course module
 *
 * @param $cmid course module id of this consentform instance
 * @param $agreed agreement/refusal/revocation
 * @return bool
 * @throws coding_exception
 * @throws moodle_exception
 */
function consentform_update_completionstate($cmid, $agreed) {
    global $USER;

    $course = get_course_and_cm_from_cmid($cmid)[0];
    $cm = get_coursemodule_from_id(false, $cmid);
    $cminfo = new completion_info($course);
    $current = $cminfo->get_data($cm, false, $USER->id);
    $current->completionstate = $agreed;
    $current->timemodified    = time();
    $cminfo->internal_set_data($cm, $current);

    return true;
}

/**
 * Database query to get list of course participants
 *
 * @param $sortkey which user's field should be used to sort list
 * @param $sortorder sort order of sorting (asc/desc)
 * @param $tab which list of users (only agreed/refused/etc..)
 * @param $context used by system function to get enrolled users of course
 * @param $cm
 * @return array list of participants, id,lastname,firstname,email(,status)
 * @throws coding_exception
 * @throws dml_exception
 */
function consentform_get_listusers($sortkey, $sortorder, $tab, $context, $cm) {
    global $DB;

    $sqlsortkey = consentform_get_sqlsortkey($sortkey);
    $sqlsortorder = $sortorder;
    if ($sqlsortkey != "timestamp") {
        $orderby = $sqlsortkey . ' ' . $sqlsortorder;
    } else {
        $orderby = null;
    }

    // Participants with no action.
    if ($tab == CONSENTFORM_STATUS_NOACTION) {
        $enrolledview = get_enrolled_users($context, 'mod/consentform:view', 0,
            'u.id, u.lastname, u.firstname, u.email', $orderby, 0, 0, true);
        $enrolledsubmit = get_enrolled_users($context, 'mod/consentform:submit', 0,
            'u.id, u.lastname, u.firstname, u.email', $orderby);
        $sqlselect = "SELECT u.id, u.lastname, u.firstname, u.email ";
        $sqlfrom = "FROM {consentform_state} c INNER JOIN {user} u ON c.userid = u.id ";
        $sqlwhere = "WHERE (c.consentformcmid = $cm->id) ";
        $sqlorderby = "ORDER BY $sqlsortkey $sqlsortorder";
        $query = "$sqlselect $sqlfrom $sqlwhere $sqlorderby";
        $withaction = $DB->get_records_sql($query);
        $listusers = array_diff_key($enrolledview, $enrolledsubmit, $withaction);
        foreach ($listusers as &$row) {
            $row->timestamp = CONSENTFORM_NOTIMESTAMP;
            $row->state = get_string('noaction', 'consentform');
        }
    } else if ($tab == CONSENTFORM_ALL) { // All course participants.
        $enrolledview = get_enrolled_users($context, 'mod/consentform:view', 0,
            'u.id, u.lastname, u.firstname, u.email', $orderby, 0, 0, true);
        $enrolledsubmit = get_enrolled_users($context, 'mod/consentform:submit', 0,
            'u.id, u.lastname, u.firstname, u.email', $orderby);
        $listusers = array_diff_key($enrolledview, $enrolledsubmit);
        foreach ($listusers as &$row) {
            if ($fields = $DB->get_record('consentform_state',
                array('userid' => $row->id, 'consentformcmid' => $cm->id), 'timestamp, state')) {
                $row->timestamp = $fields->timestamp;
                $row->state = $fields->state;
            } else {
                $row->timestamp = CONSENTFORM_NOTIMESTAMP;
                $row->state = get_string('noaction', 'consentform');
            }
        }
        if ($sqlsortkey == "timestamp") {
            if ($sqlsortorder == "DESC") {
                usort($listusers, function($a, $b) {
                    return strcmp($b->timestamp, $a->timestamp);
                });
            } else {
                usort($listusers, function($a, $b) {
                    return strcmp($a->timestamp, $b->timestamp);
                });
            }
        }
    } else { // Participants with action.
        $sqlenrolled = get_enrolled_sql($context, '', 0, true);
        $enrolled = $DB->get_records_sql($sqlenrolled[0], $sqlenrolled[1]);
        $sqlselect = "SELECT u.id, u.lastname, u.firstname, u.email, c.timestamp, c.state ";
        $sqlfrom = "FROM {consentform_state} c INNER JOIN {user} u ON c.userid = u.id ";
        $sqlwhere = "WHERE (c.consentformcmid = $cm->id AND c.state = $tab) ";
        $sqlorderby = "ORDER BY $sqlsortkey $sqlsortorder";
        $query = "$sqlselect $sqlfrom $sqlwhere $sqlorderby";
        $listusers = $DB->get_records_sql($query);
        $listusers = array_intersect_key($listusers, $enrolled);
    }
    return $listusers;
}

/**
 * Calculate the SQL sortkey to be used by the SQL statements later.
 *
 * @param $sortkey
 * @return string
 */
function consentform_get_sqlsortkey($sortkey) {
    switch ($sortkey) {
        case "lastname":
            $sqlsortkey = "lastname";
            break;
        case "firstname":
            $sqlsortkey = "firstname";
            break;
        case "email":
            $sqlsortkey = "email";
            break;
        case "timestamp":
            $sqlsortkey = "timestamp";
            break;
    }
    return $sqlsortkey;
}

/**
 * Returns list of participants as html
 *
 * @param $listusers list of participants id, firstname, lasthame, email(,status)
 * @param $cmid course module id of this consentform instance
 * @param $sortkey participants field used for sorting
 * @param $sortorder asc/desc
 * @param $tab which users, only agreed/refused/revoked etc.
 * @return string html of participants list
 * @throws coding_exception
 */
function consentform_display_participants($listusers, $cmid, $sortkey, $sortorder, $tab) {

    $index = 0;
    $urlinit  = '/mod/consentform/listusers.php?';
    $urlinit .= 'id=' . $cmid;
    $urlinit .= '&sesskey=' . sesskey();
    $urlinit .= '&tab=' . $tab;

    foreach ($listusers as $row) {

        if ($index == 0) {

            $table = new html_table();
            $table->head = array(
                "",
                consentform_participantstable_headercolumn("lastname", get_string('lastname'),
                    $urlinit, $sortkey, $sortorder),
                consentform_participantstable_headercolumn("firstname", get_string('firstname'),
                    $urlinit, $sortkey, $sortorder),
                consentform_participantstable_headercolumn("email", get_string('email'),
                    $urlinit, $sortkey, $sortorder),
                consentform_participantstable_headercolumn("timestamp", get_string('timestamp', 'consentform'),
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
        switch ($row->state) {
            case "1":
                $state = html_writer::span(get_string("agreed", "consentform"), "agreed");
                break;
            case "0":
                $state = html_writer::span(get_string("revoked", "consentform"), "revoked");
                break;
            case "-1":
                $state = html_writer::span(get_string("refused", "consentform"), "refused");
                break;
            default:
                $state = html_writer::span(get_string("noaction", "consentform"));
                break;
        }
        $table->data[]  = array(
            $index,
            $row->lastname,
            $row->firstname,
            $row->email,
            $row->timestamp != CONSENTFORM_NOTIMESTAMP ? userdate($row->timestamp) : CONSENTFORM_NOTIMESTAMP,
            $state,
        );

    }  // for each row

    if ($index == 0) {
        $html = html_writer::tag('p', get_string('listempty', 'consentform'), array('class' => 'alert-warning'));
    } else {
        $html = html_writer::table($table);
    }

    return $html;

}

/**
 * Returns participants list table header column as html
 *
 * @param $column lastname, firstname, email, timestamp
 * @param $columntitle html title of column
 * @param $urlinit url for sorting
 * @param $sortkey field used to sort list
 * @param $sortorder asc/desc
 * @return string html of column cell
 * @throws coding_exception
 */
function consentform_participantstable_headercolumn($column, $columntitle, $urlinit, $sortkey, $sortorder) {
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

}
