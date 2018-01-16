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
 * @copyright  2016 Your Name <your@email.address>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Does something really useful with the passed things
 *
 * @param array $things
 * @return object
 *function confidential_do_something_useful(array $things) {
 *    return new stdClass();
 *}
 */


function set_restriction($activity, $user, $course, $section) {
    global $DB;

    $restriction =  '{"op":"|","c":[{"type":"profile","sf":"idnumber","op":"isequalto","v":"01"}],"show":true}';

    $module = $DB->get_record('course_modules', array('course' => $course , 'section' => $sectionid ), '*', MUST_EXIST);

    $course_module = new stdClass();
    $course_module->id = $module->id;
    $course_module->course = $course;
    $course_module->section = $sectionid;
    $course_module->availability = $restriction;

    $res = $DB->update_record('course_modules', $course_module);

    if($res)
        rebuild_course_cache($course, true);

    return $res;
}