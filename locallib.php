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
 * @param object $course dataset of course
 * @param int $cmidcontroller course module id of this consentform instance
 * @param bool $locked if freezing is active
 * @return array|void array of table rows
 *
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function consentform_generate_coursemodulestable_content($course, $cmidcontroller, $locked) {
    global $PAGE;

    $modinfo = get_fast_modinfo($course);
    $coursemodules = $modinfo->get_cms();
    $sections = $modinfo->get_section_info_all();

    $rows = [];
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
                    $usercanviewsection = true;
                    $row = new html_table_row();
                    $sectionname = $sections[$sectioni]->name;
                    if (!$sectionname && $sectioni == 0) {
                        $sectionname = get_string("general", "moodle");
                    }
                    $sectionname = $sectionname ?? get_string("topic", "moodle") . " " . (string)($sectioni);

                    $nourl = $PAGE->url . "#";
                    $cell = new html_table_cell('<strong>' .$sectionname . '</strong>&nbsp;&nbsp;' .
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
                $sectionibefore = $sectioni;
            }
            if ($usercanviewsection) {
                if ($cminfo->uservisible) {
                    $row = new html_table_row();
                    $cmidcontrolled = $cmid;
                    $cfcontrolled = consentform_find_entry_availability($cmidcontrolled, $cmidcontroller);
                    $checked = $cfcontrolled <= 0 ? 0 : 1;
                    $checkboxattributes = ['class' => "selectcoursemodule section$sectioni"];
                    if ($cfcontrolled == 2 || $cfcontrolled == -1 || $locked) {
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
                    $viewurl = new moodle_url('/course/modedit.php', ['update' => $cmid]);
                    $activitylink = html_writer::empty_tag('img', ['src' => $cminfo->get_icon_url(),
                            'class' => 'iconlarge activityicon', 'alt' => $cminfo->modfullname,
                            'title' => $cminfo->modfullname, 'role' => 'presentation']) .
                            html_writer::tag('span', format_string ($cminfo->name), ['class' => 'leftmargin']);
                    $row->cells[] = new html_table_cell(
                        html_writer::start_div().html_writer::link($viewurl, $activitylink).
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
 * Returns string of warning icon, if a not regular user entry for consentform was found in course module's availability
 *
 * @return string html
 * @throws coding_exception
 */
function consentform_geticon_userentry() {
    global $OUTPUT;

    $attributes = [];
    $attributes['data-toggle'] = "tooltip";
    $string = get_string("warninguserentry", "mod_consentform");
    $attributes['title'] = $string;
    $icon = $OUTPUT->pix_icon('i/incorrect', $string, 'moodle', $attributes);

    return $icon;
}

/**
 * Returns string of warning icon, if a negative consentform-like entry was found in course module's availability
 *
 * @return string html
 * @throws coding_exception
 */
function consentform_geticon_userentry_negative() {
    global $OUTPUT;

    $attributes = [];
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

    $header = [];
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
        $table->attributes, [
            'id'            => $table->id,
            'summary'       => $table->summary,
        ]
    );
    $output = html_writer::start_tag('table', $attributes) . "\n";

    $countcols = 0;

    $headfoot = $printfooter ? ['thead', 'tfoot'] : ['thead'];

    if (!empty($table->head)) {
        foreach ($headfoot as $tag) {
            $countcols = count($table->head);

            $output .= html_writer::start_tag($tag, []) . "\n";
            $output .= html_writer::start_tag('tr', []) . "\n";
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
                    $heading->attributes, [
                        'style'     => $table->align[$key] . $table->size[$key] . $heading->style,
                        'scope'     => $heading->scope,
                        'colspan'   => $heading->colspan,
                    ]
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
            $output .= html_writer::start_tag('tbody', ['class' => 'empty']);
            $output .= html_writer::tag('tr', html_writer::tag('td', '', ['colspan' => count($table->head)]));
            $output .= html_writer::end_tag('tbody');
        }
    }

    if (!empty($table->data)) {
        $oddeven    = 1;
        $keys       = array_keys($table->data);
        $lastrowkey = end($keys);
        $output .= html_writer::start_tag('tbody', []);

        foreach ($table->data as $key => $row) {
            if (($row === 'hr') && ($countcols)) {
                $output .= html_writer::tag(
                    'td', html_writer::tag('div', '', ['class' => 'tabledivider']),
                    ['colspan' => $countcols]
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
                        ['class' => trim($row->attributes['class']),
                            'style' => $row->style, 'id' => $row->id, 'name' => trim($row->attributes['name'])]
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
                        $cell->attributes, [
                            'style' => $tdstyle . $cell->style,
                            'colspan' => $cell->colspan,
                            'rowspan' => $cell->rowspan,
                            'id' => $cell->id,
                            'abbr' => $cell->abbr,
                            'scope' => $cell->scope,
                        ]
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
    $output = html_writer::tag('div', $output, ['style' => 'overflow: auto; width: 100%']);
    return $output;
}


/**
 * Find consentform completion entry in availability of course_module
 *
 * @param int $cmidcontrolled  course module id of this consentform instance
 * @param int $cmidcontroller  id of course module which relies on this consentform instance
 * @return bool $ret 0...not found, 1...consentform entry found, 2...user entry found
 * @throws dml_exception
 */
function consentform_find_entry_availability($cmidcontrolled, $cmidcontroller) {
    global $DB;

    $ret = 0;
    if ($availability = $DB->get_field('course_modules', 'availability', ['id' => $cmidcontrolled])) {
        $availability = json_decode($availability ?? '');
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
    }
    return $ret;
}

/**
 * Find completion entry anywhere in availability of course module (recursive)
 *
 * @param array $conditions condition or condiionlist of availability
 * @param int $cmidcontrolled  course module id of this consentform instance
 * @param int $cmidcontroller  id of course module which relies on this consentform instance
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
 * @param int $courseid        id of this course
 * @param int $cmidcontrolled  id of course module which relies on this CF instance
 * @param int $cmidcontroller  course module id of this CF instance
 * @return bool
 * @throws dml_exception
 */
function consentform_make_entry_availability($courseid, $cmidcontrolled, $cmidcontroller) {
    global $DB;

    $availabilityjsonstring = $DB->get_field('course_modules', 'availability', ['id' => $cmidcontrolled]);
    if ($availabilityjsonstring == '{"op":"&","c":[],"showc":[]}') {
        $availabilityjsonstring = "";
    }
    $availabilityold = json_decode($availabilityjsonstring ?? '');
    $availabilitynew = new stdClass();
    $availabilitynew->op = "&";
    $availabilitynew->c = [];
    $availabilitynew->showc = [];
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
 * @param int $courseid        id of this course
 * @param int $cmidcontrolled  id of course module which relies on this CF instance
 * @param int $cmidcontroller  course module id of this CF instance
 * @return bool
 * @throws dml_exception
 */
function consentform_delete_entry_availability($courseid, $cmidcontrolled, $cmidcontroller) {
    global $DB;

    $availabilityjsonstring = $DB->get_field('course_modules', 'availability', ['id' => $cmidcontrolled]);
    $availability = json_decode($availabilityjsonstring ?? '');
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
                            $subcondition->showc = [];
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
                        $newcondition->c = [$subcondition];
                        $newcondition->showc = [true];
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
 * @param int $status agreement/refusal/revocation
 * @param int $userid user's id
 * @param int $cmid id of this instance's coursemodule
 * @return bool
 * @throws dml_exception
 */
function consentform_save_agreement($status, $userid, $cmid) {
    global $DB;

    if ($id = $DB->get_field(
        'consentform_state', 'id', ['consentformcmid' => $cmid, 'userid' => $userid])) {
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

    $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $cmid]);
    $consentform = $DB->get_record('consentform', ['id' => $instanceid]);

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
 * @param int $id       record id
 * @param int $userid   user id of participant
 * @param int $agreed   1 agreed, 0 revoked, -1 refused
 * @param int $cmid     course module id
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
 * Enter grade value for all agreements when usegrade has been switched on.
 *
 * @param object $consentform data of mod_form
 * @return bool
 * @throws dml_exception
 */
function consentform_usegradechange_writegrades($consentform) {
    global $DB;

    $records = $DB->get_records('consentform_state',
        ["consentformcmid" => $consentform->coursemodule, "state" => CONSENTFORM_STATUS_AGREED]);
    foreach ($records as $record) {
        consentform_set_user_grade($consentform, $record->userid, GRADEVALUETOWRITE);
    }

    rebuild_course_cache($consentform->course, false);

    return true;
}

/**
 * Update completions of this consentform course module
 *
 * @param int $cmid course module id of this consentform instance
 * @param int $agreed agreement/refusal/revocation
 * @param int $userid optional userid, otherwise current user
 * @return bool
 * @throws coding_exception
 * @throws moodle_exception
 */
function consentform_update_completionstate($cmid, $agreed, $userid = 0) {
    global $USER;

    if (!$userid) {
        $userid = $USER->id;
    }
    $course = get_course_and_cm_from_cmid($cmid)[0];
    $cm = get_coursemodule_from_id(false, $cmid);
    $cminfo = new completion_info($course);
    $current = $cminfo->get_data($cm, false, $userid);
    $current->completionstate = $agreed;
    $current->timemodified    = time();
    $cminfo->internal_set_data($cm, $current);

    return true;
}

/**
 * Database query to get list of course participants
 *
 * @param string $sortkey which user's field should be used to sort list
 * @param string $sortorder sort order of sorting (asc/desc)
 * @param int $tab which list of users (only agreed/refused/etc..)
 * @param context $context used by system function to get enrolled users of course
 * @param stdClass $cm
 * @return array list of participants, id,lastname,firstname,email(,status)
 * @throws coding_exception
 * @throws dml_exception
 */
function consentform_get_listusers($sortkey, $sortorder, $tab, $context, $cm) {
    global $DB;

    $sqlsortkey = consentform_get_sqlsortkey($sortkey);
    $sqlsortorder = $sortorder;
    if ($sqlsortkey != "timestamp" && $sqlsortkey != "state") {
        $orderby = $sqlsortkey . ' ' . $sqlsortorder;
    } else {
        $orderby = null;
    }

    // Participants with no action. Only with capability view.
    if ($tab == CONSENTFORM_STATUS_NOACTION) {
        $enrolledview = get_enrolled_users($context, 'mod/consentform:view', 0,
            'u.id, u.lastname, u.firstname, u.email', $orderby, 0, 0, true);
        $enrolledsubmit = get_enrolled_users($context, 'mod/consentform:submit', 0,
            'u.id, u.lastname, u.firstname, u.email', $orderby);
        $sqlselect = "SELECT u.id, u.lastname, u.firstname, u.email, 2 as state ";
        $sqlfrom = "FROM {consentform_state} c INNER JOIN {user} u ON c.userid = u.id ";
        $sqlwhere = "WHERE (c.consentformcmid = :cmid) ";
        $sqlorderby = "ORDER BY $sqlsortkey $sqlsortorder";
        $query = "$sqlselect $sqlfrom $sqlwhere $sqlorderby";
        $params = ['cmid' => $cm->id];
        $withaction = $DB->get_records_sql($query, $params);
        $listusers = array_diff_key($enrolledview, $enrolledsubmit, $withaction);
        foreach ($listusers as &$row) {
            $row->timestamp = CONSENTFORM_NOTIMESTAMP;
            $row->state = CONSENTFORM_STATUS_NOACTION;
        }
    } else if ($tab == CONSENTFORM_ALL) { // All course participants. Only with capability view.
        $enrolledview = get_enrolled_users($context, 'mod/consentform:view', 0,
            'u.id, u.lastname, u.firstname, u.email', $orderby, 0, 0, true);
        $enrolledsubmit = get_enrolled_users($context, 'mod/consentform:submit', 0,
            'u.id, u.lastname, u.firstname, u.email', $orderby);
        $listusers = array_diff_key($enrolledview, $enrolledsubmit);
        foreach ($listusers as &$row) {
            if ($fields = $DB->get_record('consentform_state',
                ['userid' => $row->id, 'consentformcmid' => $cm->id], 'timestamp, state')) {
                $row->timestamp = $fields->timestamp;
                $row->state = $fields->state;
            } else {
                $row->timestamp = CONSENTFORM_NOTIMESTAMP;
                $row->state = CONSENTFORM_STATUS_NOACTION;
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
        if ($sqlsortkey == "state") {
            if ($sqlsortorder == "DESC") {
                usort($listusers, function($a, $b) {
                    return strcmp($b->state, $a->state);
                });
            } else {
                usort($listusers, function($a, $b) {
                    return strcmp($a->state, $b->state);
                });
            }
        }
    } else { // Participants with action. Only with capability view.
        $sqlenrolled = get_enrolled_sql($context, '', 0, true);
        $enrolled = $DB->get_records_sql($sqlenrolled[0], $sqlenrolled[1]);
        $sqlselect = "SELECT u.id, u.lastname, u.firstname, u.email, c.timestamp, c.state ";
        $sqlfrom = "FROM {consentform_state} c INNER JOIN {user} u ON c.userid = u.id ";
        $sqlwhere = "WHERE (c.consentformcmid = :cmid AND c.state = :tab) ";
        $sqlorderby = "ORDER BY $sqlsortkey $sqlsortorder";
        $query = "$sqlselect $sqlfrom $sqlwhere $sqlorderby";
        $params = ['cmid' => $cm->id, 'tab' => $tab];
        $listusers = $DB->get_records_sql($query, $params);
        $listusers = array_intersect_key($listusers, $enrolled);
        $enrolledsubmit = get_enrolled_users($context, 'mod/consentform:submit', 0,
            'u.id, u.lastname, u.firstname, u.email', $orderby);
        $listusers = array_diff_key($listusers, $enrolledsubmit);
    }
    return $listusers;
}

/**
 * Calculate the SQL sortkey to be used by the SQL statements later.
 *
 * @param string $sortkey
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
        case "state":
            $sqlsortkey = "state";
            break;
    }
    return $sqlsortkey;
}

/**
 * Returns list of participants as html
 *
 * @param array $listusers list of participants id, firstname, lasthame, email(,status)
 * @param int $cmid course module id of this consentform instance
 * @param string $sortkey participants field used for sorting
 * @param string $sortorder asc/desc
 * @param int $tab which users, only agreed/refused/revoked etc.
 * @return string html of participants list
 * @throws coding_exception
 */
function consentform_display_participants($listusers, $cmid, $sortkey, $sortorder, $tab) {

    $index = 0;

    $table = new html_table();
    $table->head = [
        "",
        consentform_participantstable_headercolumn("lastname", get_string('lastname'),
            $sortkey, $sortorder, $cmid, $tab),
        consentform_participantstable_headercolumn("firstname", get_string('firstname'),
            $sortkey, $sortorder, $cmid, $tab),
        consentform_participantstable_headercolumn("email", get_string('email'),
            $sortkey, $sortorder, $cmid, $tab),
        consentform_participantstable_headercolumn("timestamp", get_string('timestamp', 'consentform'),
            $sortkey, $sortorder, $cmid, $tab),
        consentform_participantstable_headercolumn("state", get_string('status'),
            $sortkey, $sortorder, $cmid, $tab),
    ];
    $table->align = [
        'right',
        'left',
        'left',
        'left',
        'center',
        'center',
    ];

    foreach ($listusers as $row) {

        $index++;
        switch ($row->state) {
            case CONSENTFORM_STATUS_AGREED:
                $state = html_writer::span(get_string("agreed", "consentform"), "agreed");
                break;
            case CONSENTFORM_STATUS_REVOKED:
                $state = html_writer::span(get_string("revoked", "consentform"), "revoked");
                break;
            case CONSENTFORM_STATUS_REFUSED:
                $state = html_writer::span(get_string("refused", "consentform"), "refused");
                break;
            case CONSENTFORM_STATUS_NOACTION:
            default:
                $state = html_writer::span(get_string("noaction", "consentform"));
                break;
        }
        $table->data[]  = [
            $index,
            $row->lastname,
            $row->firstname,
            $row->email,
            $row->timestamp != CONSENTFORM_NOTIMESTAMP ? userdate($row->timestamp) : CONSENTFORM_NOTIMESTAMP,
            $state,
        ];

    }  // For each user row.

    if ($index == 0) {
        $html = html_writer::tag('p', get_string('listempty', 'consentform'),
            ['class' => 'alert-warning', 'style' => 'margin-top:0.5em;']);
    } else {
        $html = html_writer::table($table);
    }

    return $html;

}

/**
 * Returns participants list table header column as html
 *
 * @param string $column lastname, firstname, email, timestamp
 * @param string $columntitle html title of column
 * @param string $sortkey field used to sort list
 * @param string $sortorder asc/desc
 * @param int $cmid course module id of instance
 * @param int $tab which users, only agreed/refused/revoked etc.
 * @return string html of column cell
 * @throws coding_exception
 */
function consentform_participantstable_headercolumn($column, $columntitle, $sortkey, $sortorder, $cmid, $tab) {
    global $OUTPUT;

    $urlinit  = '/mod/consentform/listusers.php?';
    $icon = "";

    if ($column == $sortkey) {
        if ($sortorder == "DESC") {
            $icon = $OUTPUT->image_icon('t/sort_desc', get_string('sort'), 'moodle', [
                'style' => 'cursor:pointer;margin-left:2px;nowrap']);
            $url = new moodle_url($urlinit, ['sortkey' => $column, 'id' => $cmid, 'sesskey' => sesskey(),
                'tab' => $tab, 'sortorder' => 'ASC']);
        } else {
            $icon = $OUTPUT->image_icon('t/sort_asc', get_string('sort'), 'moodle', [
                'style' => 'cursor:pointer;margin-left:2px;nowrap']);
            $url = new moodle_url($urlinit, ['sortkey' => $column, 'id' => $cmid, 'sesskey' => sesskey(),
                'tab' => $tab, 'sortorder' => 'DESC']);
        }
    } else {
        $url = new moodle_url($urlinit, ['sortkey' => $column, 'id' => $cmid, 'sesskey' => sesskey(),
            'tab' => $tab, 'sortorder' => 'ASC']);
    }

    $linkstr = html_writer::link($url, $columntitle . $icon);

    return $linkstr;

}

/**
 * Get log entry of last agreement/refusal/revocation of this user.
 *
 * @param int $cmid    coursemodule id
 * @param int $userid  user id
 * @param int $status  agreed or revoked or refused
 * @return string  returns logentry.
 * @throws coding_exception
 * @throws dml_exception
 */
function consentform_get_agreementlogentry($cmid, $userid, $status) {
    global $DB, $OUTPUT;

    if ($timestamp = $DB->get_field('consentform_state', 'timestamp',
        ['consentformcmid' => $cmid, 'userid' => $userid])) {
        if ($status == CONSENTFORM_STATUS_AGREED) {
            return $OUTPUT->notification(get_string('agreementlogentry', 'consentform', userdate($timestamp)),
                'success', false);
        } else {
            if ($status == CONSENTFORM_STATUS_REVOKED) {
                return $OUTPUT->notification(get_string('revokelogentry', 'consentform', userdate($timestamp)),
                    'warning', false);
            } else if ($status == CONSENTFORM_STATUS_REFUSED) {
                return $OUTPUT->notification(get_string('refuselogentry', 'consentform', userdate($timestamp)),
                    'error', false);
            }
        }
    }

    return "";
}

/**
 * Output header without the intro because it is used for course view confirmation.
 *
 * @param int $id of the consentform instance
 * @param string $alternatetext to display instead of intro (optional)
 * @return bool all is good
 * @throws dml_exception
 */
function consentform_showheaderwithoutintro($id, $alternatetext = "") {
    global $DB, $OUTPUT;
    $intro = $DB->get_field('consentform', 'intro', ['id' => $id]);
    $DB->set_field('consentform', 'intro', $alternatetext, ['id' => $id]);
    echo $OUTPUT->header();
    $DB->set_field('consentform', 'intro', $intro, ['id' => $id]);
    return true;
}

/**
 * Tells user that course module list is deactivated.
 *
 * @param int $id of the consentform instance
 * @return bool all is good
 */
function consentform_shownocoursemodulelistinfo($id) {
    global $OUTPUT;
    $link = new moodle_url('/course/modedit.php', ['update' => $id]);
    $linktext = get_string("linktexttomodulesettings", "mod_consentform");
    $outstr = $OUTPUT->notification(get_string("nocoursemoduleslist_help", "mod_consentform")." ".
        html_writer::link($link, $linktext), 'info', false);
    return $outstr;
}

/**
 * Checks if Moodle completion, course completion and module completion is activated.
 *
 * @param int $id of the consentform instance
 * @param object $context of the consentform instance
 * @param object $course of the consentform instance
 * @param int $cmcompletion flag if module completion is on
 * @return string $nocompletion if not empty: completion is not ok
 */
function consentform_checkcompletion($id, $context, $course, $cmcompletion) {
    global $CFG;
    $nocompletion = "";
    if (!$CFG->enablecompletion) {
        if (has_capability('mod/consentform:submit', $context, null, false) || is_siteadmin()) {
            $link = "https://docs.moodle.org/en/Activity_completion_settings#Required_site_settings";
            $linktext = get_string("nocompletionlinktext", "mod_consentform");
            $nocompletion .= html_writer::div(get_string("nocompletion", "mod_consentform")." ".html_writer::link($link,
                    $linktext, ['target' => '_blank']));
        } else {
            $nocompletion .= html_writer::div(get_string("nocompletion", "mod_consentform"));
        }
    }
    if (!$course->enablecompletion) {
        if (has_capability('mod/consentform:submit', $context, null, false) || is_siteadmin()) {
            $link = new moodle_url('/course/edit.php', ['id' => $course->id]);
            $linktext = get_string("nocompletioncourselinktext", "mod_consentform");
            $nocompletion .= html_writer::div(get_string("nocompletioncourse", "mod_consentform")." ".
                html_writer::link($link, $linktext));
        } else {
            $nocompletion .= html_writer::div(get_string("nocompletioncourse", "mod_consentform"));
        }
    }
    if (!$cmcompletion) {
        if (has_capability('mod/consentform:submit', $context, null, false) || is_siteadmin()) {
            $link = new moodle_url('/course/modedit.php', ['update' => $id]);
            $linktext = get_string("nocompletionmodulelinktext", "mod_consentform");
            $nocompletion .= html_writer::div(get_string("nocompletionmodule", "mod_consentform")." ".
                html_writer::link($link, $linktext));
        } else {
            $nocompletion .= html_writer::div(get_string("nocompletionmodule", "mod_consentform"));
        }
    }
    return $nocompletion;
}

/**
 * Statistics user reactions stati.
 *
 * @param object $coursecontext context course
 * @param int $cmid ID of course module
 * @return array stats: sumagreed, sumrefused, sumrevoked, sumnoaction, sumall
 * @throws dml_exception
 */
function consentform_statistics_listusers($coursecontext, $cmid) {
    global $DB;

    // All active participants.
    $enrolledview = get_enrolled_users($coursecontext, 'mod/consentform:view', 0, 'u.id', null, 0, 0, true);
    // All trainers and admins.
    $enrolledsubmit = get_enrolled_users($coursecontext, 'mod/consentform:submit', 0, 'u.id');
    // All participants who are not trainers.
    $enrolled = array_diff_key($enrolledview, $enrolledsubmit);

    // Get all users with action.
    $sqlselect = "SELECT u.id ";
    $sqlfrom   = "FROM {consentform_state} c INNER JOIN {user} u ON c.userid = u.id ";
    $sqlwhere  = "WHERE (c.consentformcmid = :cmid) ";
    $query = "$sqlselect $sqlfrom $sqlwhere";
    $userswithaction = $DB->get_records_sql($query, ['cmid' => $cmid]);

    // Get sum users without action.
    $usersnoactions = array_diff_key($enrolled, $userswithaction);
    $sumnoaction = count($usersnoactions);

    // Get sum ALL.
    $sumall = count($enrolled);

    // Get sum agreed.
    $sqlwhere2 = "AND c.state = ".CONSENTFORM_STATUS_AGREED;
    $query = "$sqlselect $sqlfrom $sqlwhere $sqlwhere2";
    $usersagreed = $DB->get_records_sql($query, ['cmid' => $cmid]);
    $usersagreed = array_intersect_key($enrolled, $usersagreed);
    $sumagreed = count($usersagreed);

    // Get sum refused.
    $sqlwhere2 = "AND c.state = ".CONSENTFORM_STATUS_REFUSED;
    $query = "$sqlselect $sqlfrom $sqlwhere $sqlwhere2";
    $usersrefused = $DB->get_records_sql($query, ['cmid' => $cmid]);
    $usersrefused = array_intersect_key($enrolled, $usersrefused);
    $sumrefused = count($usersrefused);

    // Get sum revoked.
    $sqlwhere2 = "AND c.state = ".CONSENTFORM_STATUS_REVOKED;
    $query = "$sqlselect $sqlfrom $sqlwhere $sqlwhere2";
    $usersrevoked = $DB->get_records_sql($query, ['cmid' => $cmid]);
    $usersrevoked = array_intersect_key($enrolled, $usersrevoked);
    $sumrevoked = count($usersrevoked);

    return [$sumagreed, $sumrefused, $sumrevoked, $sumnoaction, $sumall];
}

/**
 * This gets an array with default options for the editor
 *
 * @param object $context
 * @return array the options
 */
function consentform_get_editor_options($context) {
    global $CFG;
    return ['subdirs' => 1,
        'maxbytes' => $CFG->maxbytes,
        'maxfiles' => -1,
        'changeformat' => 1,
        'context' => $context,
        'noclean' => 1,
        'trusttext' => 0];
}
