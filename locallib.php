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
 * Internal library of functions for module confidential
 *
 * All the confidential specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_confidential
 * @copyright  2018 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/lib.php');


function confidential_generate_table_content($course, $thiscmid) {
    global $PAGE;

    $modinfo = get_fast_modinfo($course);
    $coursemodules = $modinfo->get_cms();
    $sections = $modinfo->get_section_info_all();
    $context = $PAGE->cm->context;

    $rows = array();
    $sectionibefore = "";
    $usercanviewsection = true;
    foreach($coursemodules as $cmid => $cminfo) {
        if ($cmid != $thiscmid && !$cminfo->deletioninprogress) {
            $sectioni = $cminfo->sectionnum;
            if ($sectioni != $sectionibefore) {
                if (!($sectioninfo = $modinfo->get_section_info($sectioni))) { // If this section doesn't exist.
                    print_error('unknowncoursesection', 'error', null, $course->fullname);
                    return;
                }
                if (!$sectioninfo->uservisible) {
                    $usercanviewsection = false;
                } else {
                    $usercanviewsection = true;
                    $row = new html_table_row();
                    $sectionname = $sections[$sectioni]->name;
                    $sectionname = $sectionname ? $sectionname : get_string("section", "moodle") . " " . (string)($sectioni);
                    $cell = $row->cells[] = new html_table_cell($sectionname);
                    $cell->colspan="2";
                    $cell->style="text-align:left;";
                    $rows[] = $row;
                }
                $sectionibefore = $sectioni;
            }
            if ($usercanviewsection) {
                $modname = $cminfo->modname;
                if (has_capability("mod/$modname:addinstance", $context)) {
                    $row = new html_table_row();
                    $checked = confidential_find_entry_availability($cmid, $thiscmid);
                    $row->cells[] = new html_table_cell(
                        html_writer::checkbox('selectcoursemodule' . (string)$cmid, $cmid, $checked, '', array('class' => 'selectcoursemodule'))
                    );
                    $viewurl = new moodle_url('/course/modedit.php', array('update' => $cmid));
                    $activitylink = html_writer::empty_tag('img', array('src' => $cminfo->get_icon_url(),
                            'class' => 'iconlarge activityicon', 'alt' => $cminfo->modfullname, 'title' => $cminfo->modfullname, 'role' => 'presentation')) .
                            html_writer::tag('span', $cminfo->name, array('class' => 'instancename'));
                    $row->cells[] = new html_table_cell(
                        html_writer::start_div('activity') . html_writer::link($viewurl, $activitylink) . html_writer::end_div()
                    );
                    $rows[] = $row;
                }
            } // End if user can view.

        } // End if not this cmid.
    }  // End foreach.

    return $rows;
}


function confidential_generate_table_header() {

    $header = array();
    $cell = new html_table_cell(get_string("dependent", 'confidential'));
    $cell->header = true;
    $header[] = $cell;
    $cell = new html_table_cell(get_string("modules", 'confidential'));
    $cell->header = true;
    $header[] = $cell;

    return $header;
}


function confidential_render_table(html_table $table, $printfooter = true, $overrideevenodd = false) {
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

function confidential_find_entry_availability($val, $cmid) {
    global $DB;

    $found = false;

    $conditions = $DB->get_field('course_modules', 'availability', array('id' => $val));
    $conditions = json_decode($conditions);

    if (isset($conditions->c)) {
        foreach($conditions->c as $condition) {
            if ($condition->type == 'completion') {
                if ($condition->cm == $cmid) {
                    $found = true;
                    break;
                }
            }
        }
    }

    return $found;
}

function confidential_make_entry_availability($val, $cmid) {
    global $DB;

    if ($conditions = $DB->get_field('course_modules', 'availability', array('id' => $val))) {
        $conditions = json_decode($conditions);
    } else {
        $conditions = new stdClass();
        $conditions->op = "&";
        $conditions->c = array();
        $conditions->showc = array();
    }

    $newcondition = new stdClass();
    $newcondition->type = 'completion';
    $newcondition->cm = $cmid;
    $newcondition->e = 1;

    $conditions->c[] = $newcondition;
    $conditions->showc[] = true;

    $conditions = json_encode($conditions);

    $updaterecord = new stdClass();
    $updaterecord->id = $val;
    $updaterecord->availability = $conditions;

    $ok = $DB->update_record('course_modules', $updaterecord);

    return $ok;
}

function confidential_delete_entry_availability($val, $cmid) {
    global $DB;

    $found = -1;
    if ($conditions = $DB->get_field('course_modules', 'availability', array('id' => $val))) {
        $conditions = json_decode($conditions);

        $indx = 0;
        foreach($conditions->c as $condition) {
            if ($condition->type == 'completion') {
                if ($condition->cm == $cmid) {
                    $found = $indx;
                    break;
                }
                $indx++;
            }
        }
    }

    if ($found >= 0) {
        unset($conditions->c[$found]);
        unset($conditions->showc[$found]);

        $conditions = json_encode($conditions);

        $updaterecord = new stdClass();
        $updaterecord->id = $val;
        $updaterecord->availability = $conditions;

        $ok = $DB->update_record('course_modules', $updaterecord);
        return $ok;
    } else {
        return false;
    }
}

