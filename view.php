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
 * Prints a particular instance of confidential
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_confidential
 * @copyright  2018 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace confidential with the name of your module and remove this line.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once('locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... confidential instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('confidential', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $confidential  = $DB->get_record('confidential', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $confidential  = $DB->get_record('confidential', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $confidential->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('confidential', $confidential->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
//$context = context_course::instance($cm->course);

$event = \mod_confidential\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $confidential);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/confidential/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($confidential->name));
$PAGE->set_heading(format_string($course->fullname));

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($confidential->intro) {
    echo $OUTPUT->box(format_module_intro('confidential', $confidential, $cm->id), 'generalbox mod_introbox', 'confidentialintro');
}

if (!$CFG->enableavailability) {

    echo $OUTPUT->heading(get_string("noavailability", "mod_confidential"));

} else {
    if (has_capability('mod/confidential:submit', $context, null, false)) {

        $table = new html_table();
        $table->id = 'coursemodulestable';
        $table->attributes['class'] = 'generaltable boxaligncenter overview';
        $table->head = confidential_generate_table_header();
        $table->data = confidential_generate_table_content($course, $cm->id);
        $table->align = array('center', 'left');

        echo confidential_render_table($table);

        $jsparams = array('nixxxx' => 'nix');
        $PAGE->requires->js_call_amd('mod_confidential/checkboxclicked', 'init', array($jsparams));

    } else {
        echo $OUTPUT->heading('Student');
    }

}

// Finish the page.
echo $OUTPUT->footer();
