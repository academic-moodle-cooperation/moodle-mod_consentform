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
 * @copyright  2020 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/completionlib.php');

function consentform_generate_table_content($course, $cmidcontroller) {
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
    $a->consentform = get_string("modulename", "consentform");
    foreach ($coursemodules as $cmid => $cminfo) {
        $msg = "";
        if ($cminfo->modname != 'consentform' && !$cminfo->deletioninprogress) {
            if ($availabilityjson = $cminfo->availability) {
                $availability = json_decode($availabilityjson);
                if (isset($availability->op)) {
                    if ($availability->op <> "&") {
                        $msg = "&nbsp;&nbsp;" . html_writer::start_span("warning") .
                            get_string("wrongoperator", "consentform", $a) . html_writer::end_span();
                    }
                }
            }
            $sectioni = $cminfo->sectionnum;
            if ($sectioni != $sectionibefore) {
                if (!($sectioninfo = $modinfo->get_section_info($sectioni))) { // If this section doesn't exist.
                    print_error('unknowncoursesection', 'error', null, $course->fullname);
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
                    $checked = consentform_find_entry_availability($cmidcontrolled, $cmidcontroller);
                    $cell = new html_table_cell(
                        html_writer::checkbox("selectcoursemodule[]", $cmid, $checked, '',
                        array('class' => "selectcoursemodule section$sectioni"))
                    );
                    $cell->attributes['class'] = 'consentform_activitytable_checkboxcolumn';
                    $row->cells[] = $cell;
                    $viewurl = new moodle_url('/course/modedit.php', array('update' => $cmid));
                    $activitylink = html_writer::empty_tag('img', array('src' => $cminfo->get_icon_url(),
                            'class' => 'iconlarge activityicon', 'alt' => $cminfo->modfullname,
                            'title' => $cminfo->modfullname, 'role' => 'presentation')) .
                            html_writer::tag('span', $cminfo->name, array('class' => 'instancename'));
                    $row->cells[] = new html_table_cell(
                        html_writer::start_div('activity') . html_writer::link($viewurl, $activitylink) .
                        $msg .
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


function consentform_generate_table_header() {
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

function consentform_render_table(html_table $table, $printfooter = true, $overrideevenodd = false) {
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
 * Find completion entry in course_modules.
 *
 * @param $cmidcontrolled  course module id of this CO instance.
 * @param $cmidcontroller  id of course module which relies on this CO instance.
 * @return bool $found      if entry is found.
 * @throws dml_exception
 */
function consentform_find_entry_availability($cmidcontrolled, $cmidcontroller) {
    global $DB;

    $found = false;

    $conditions = $DB->get_field('course_modules', 'availability', array('id' => $cmidcontrolled));
    $conditions = json_decode($conditions);

    if (isset($conditions->c)) {
        foreach ($conditions->c as $condition) {
            if ($condition->type == 'completion') {
                if ($condition->cm == $cmidcontroller) {
                    $found = true;
                    break;
                }
            }
        }
    }

    return $found;
}

/**
 * Make condition entry in course_modules.
 *
 * @param $courseid         id of this course
 * @param $cmidcontrolled  course module id of this CF instance.
 * @param $cmidcontroller  id of course module which relies on this CF instance.
 * @return bool
 * @throws dml_exception
 */
function consentform_make_entry_availability($courseid, $cmidcontrolled, $cmidcontroller) {
    global $DB;

    $availabilityjson = $DB->get_field('course_modules', 'availability', ['id' => $cmidcontrolled]);
    $newrestriction = new stdClass();
    $newrestriction->type = "completion";
    $newrestriction->cm = $cmidcontroller;
    $newrestriction->e = EXPECTEDCOMPLETIONVALUE;
    $availability = json_decode($availabilityjson);
    if (!isset($availability->op)) {
        $availability->op = "&";
    }
    $availability->c[] = $newrestriction;
    $availability->showc[] = true;
    $availabilityjson = json_encode($availability);
    $DB->set_field('course_modules', 'availability',
        $availabilityjson, ['id' => $cmidcontrolled]);
    consentform_update_caches($courseid);

    return true;
}

/**
 * Delete condition entry in course_modules.
 *
 * @param $courseid         id of this course
 * @param $cmidcontrolled  course module id of this CF instance.
 * @param $cmidcontroller  id of course module which relies on this CF instance.
 * @return bool
 * @throws dml_exception
 */
function consentform_delete_entry_availability($courseid, $cmidcontrolled, $cmidcontroller) {
    global $DB;

    $found = -1;
    if ($conditionsjson = $DB->get_field('course_modules', 'availability', array('id' => $cmidcontrolled))) {
        $conditionscreturn = array();
        $showreturn = array();
        $conditions = json_decode($conditionsjson);
        $indx = 0;
        foreach ($conditions->c as $condition) {
            if ($condition->type == 'completion') {
                if ($condition->cm == $cmidcontroller) {
                    $found = $indx;
                }
            } else {
                $conditionscreturn[] = $condition;
                $showreturn[] = $conditions->showc[$indx];
            }
            $indx++;
        }
    }

    if ($found >= 0) {
        $obj = new stdClass();
        $obj->op = $conditions->op;
        $obj->c = $conditionscreturn;
        $obj->showc = $showreturn;
        $conditions = json_encode($obj);

        $updaterecord = new stdClass();
        $updaterecord->id = $cmidcontrolled;
        $updaterecord->availability = $conditions;
        if ($ok = $DB->update_record('course_modules', $updaterecord)) {
            consentform_update_caches($courseid);
        }
    }
    return true;
}

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

    $consentform = consentform_getinstance($cmid);

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
 * Build the record for saving the user's agreemnent/ revocation
 *
 * @param $id       record id
 * @param $userid   user id of participant
 * @param $agreed   2 agreed, 0 revoked
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

function consentform_get_completionstate($userid) {
    global $DB;
    $fields = $DB->get_record('consentform', array('id' => $instanceid), 'timestamp, state');
    if ($instanceid = $DB->get_field('course_modules','instance', array('id' => $cmid))) {
        $consentform = $DB->get_record('consentform', array('id' => $instanceid));
        return $consentform;
    }
    return false;
}

function consentform_update_completionstate($cmid, $agreed) {
    $course = get_course_and_cm_from_cmid($cmid)[0];
    $cm = get_coursemodule_from_id(false, $cmid);
    // Update completion state.
    $completion = new completion_info($course);
    $completion->update_state($cm, $agreed);
    return true;
}

function consentform_update_caches($courseid) {
    rebuild_course_cache($courseid, false);
}

function consentform_getinstance($cmid) {
    global $DB;
    if ($instanceid = $DB->get_field('course_modules','instance', array('id' => $cmid))) {
        $consentform = $DB->get_record('consentform', array('id' => $instanceid));
        return $consentform;
    }
    return false;
}
