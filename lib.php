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
 * Library of interface functions and constants for module consentform
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the consentform specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('EXPECTEDCOMPLETIONVALUE', 1);
define('GRADEVALUETOWRITE', 1);
define('CONSENTFORM_STATUS_AGREED', 1);
define('CONSENTFORM_STATUS_REVOKED', 0);
define('CONSENTFORM_STATUS_REFUSED', -1);
define('CONSENTFORM_STATUS_NOACTION', 2);
define('CONSENTFORM_ALL', 99);
define('CONSENTFORM_NOTIMESTAMP', '-');

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function consentform_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the consentform into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $consentform Submitted data from the form in mod_form.php
 * @param mod_consentform_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted consentform record
 */
function consentform_add_instance(stdClass $consentform, mod_consentform_mod_form $mform = null) {
    global $DB, $CFG;

    $consentform->timecreated = time();

    $consentform->id = $DB->insert_record('consentform', $consentform);

    if ($consentform->confirmincourseoverview) {
        $iframeparms = array();
        $url = $CFG->wwwroot."/mod/consentform/confirmation.php?id=".$consentform->id;
        $iframeparms["src"] = $url;
        $iframeparms["scrolling"] = "no";
        $iframeparms["onload"] = "this.style.height=this.contentWindow.document.documentElement.scrollHeight + 'px';";
        $iframeparms["frameborder"] = "0";
        $iframeparms["style"] = "min-width:450px;";
        $html = html_writer::tag("iframe", null, $iframeparms);
        $consentformintro = $html;
        $DB->set_field("consentform", "intro", $consentformintro, array("id" => $consentform->id));
    }

    consentform_grade_item_update($consentform);

    return $consentform->id;
}

/**
 * Updates an instance of the consentform in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $consentform An object from the form in mod_form.php
 * @param mod_consentform_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function consentform_update_instance(stdClass $consentform, mod_consentform_mod_form $mform = null) {
    global $DB;

    $consentform->timemodified = time();
    $consentform->id = $consentform->instance;

    $result = $DB->update_record('consentform', $consentform);

    consentform_grade_item_update($consentform);

    return $result;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every consentform event in the site is checked, else
 * only consentform events belonging to the course specified are checked.
 * This is only required if the module is generating calendar events.
 *
 * @param int $courseid Course ID
 * @return bool
 * @throws dml_exception
 */
function consentform_refresh_events($courseid = 0) {
    global $DB;

    if ($courseid == 0) {
        if (!$consentforms = $DB->get_records('consentform')) {
            return true;
        }
    } else {
        if (!$consentforms = $DB->get_records('consentform', array('course' => $courseid))) {
            return true;
        }
    }

    return true;
}

/**
 * Removes an instance of the consentform from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 * @throws dml_exception
 * @throws coding_exception
 */
function consentform_delete_instance($id) {
    global $DB;

    // Get cm and consentform.
    $cm = get_coursemodule_from_instance('consentform', $id);
    if (!$consentform = $DB->get_record('consentform', array('id' => $id))) {
        return false;
    }

    // Delete dataset in consentform and dependent entries in consentform_state.
    $DB->delete_records('consentform', array('id' => $consentform->id));
    $DB->delete_records('consentform_state', array('consentformcmid' => $cm->id));

    // Search for and remove restrictions introduced by this consentform instance in other course modules.
    $records = $DB->get_records('course_modules', array('course' => $consentform->course));
    foreach ($records as $record) {
        if ($conditions = json_decode($record->availability)) {
            $i = 0;
            foreach ($conditions->c as $conditionc) {
                if ($conditionc->type == 'completion' && $conditionc->cm == $cm->id) {
                    unset($conditions->c[$i]);
                    unset($conditions->showc[$i]);
                }
                $i++;
            }
            $conditions = json_encode($conditions);
            $updaterecord = new stdClass();
            $updaterecord->id = $record->id;
            $updaterecord->availability = $conditions;
            $DB->update_record('course_modules', $updaterecord);
        }
    }

    rebuild_course_cache($consentform->course, false);

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $consentform The consentform instance record
 * @return stdClass|null
 */
function consentform_user_outline($course, $user, $mod, $consentform) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $consentform the module instance record
 */
function consentform_user_complete($course, $user, $mod, $consentform) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in consentform activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function consentform_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link consentform_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function consentform_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link consentform_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function consentform_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function consentform_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function consentform_get_extra_capabilities() {
    return array();
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of consentform?
 *
 * This function returns if a scale is being used by one consentform
 * if it has support for grading and scales.
 *
 * @param int $consentformid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given consentform instance
 */
function consentform_scale_used($consentformid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('consentform', array('id' => $consentformid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of consentform.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any consentform instance
 */
function consentform_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('consentform', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given consentform instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $consentform instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function consentform_grade_item_update(stdClass $consentform, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($consentform->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = 1;
    $item['grademin']  = 0;

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    grade_update('mod/consentform', $consentform->course, 'mod', 'consentform',
            $consentform->id, 0, $grades, $item);
}

/**
 * Delete grade item for given consentform instance
 *
 * @param stdClass $consentform instance object
 * @return grade_item
 */
function consentform_grade_item_delete($consentform) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/consentform', $consentform->course, 'mod', 'consentform',
            $consentform->id, 0, null, array('deleted' => 1));
}

/**
 * Update consentform grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $consentform instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function consentform_update_grades(stdClass $consentform, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();

    grade_update('mod/consentform', $consentform->course, 'mod', 'consentform', $consentform->id, 0, $grades);
}

function consentform_set_user_grade($consentform, $userid, $agreed=true) {

    if ($userid) {

        $grade = new stdClass();
        $grade->userid = $userid;
        if ($agreed) {
            $grade->rawgrade = 1;
        } else {
            $grade->rawgrade = 0;
        }
        $time = time();
        $grade->dategraded = $time;
        $grade->datesubmitted = $time;

        return consentform_grade_item_update($consentform, $grade);

    } else {
        return false;
    }
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function consentform_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for consentform file areas
 *
 * @package mod_consentform
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function consentform_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the consentform file areas
 *
 * @package mod_consentform
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the consentform's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function consentform_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding consentform nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the consentform module instance
 * @param stdClass $course current course record
 * @param stdClass $module current consentform instance record
 * @param cm_info $cm course module information
 */
function consentform_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the consentform settings
 *
 * This function is called when the context for the page is a consentform module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $consentformnode consentform administration node
 */
function consentform_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $consentformnode=null) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Obtains the automatic completion state for this consentform instance for this user
 *
 * @param object $cm Course-module
 * @param int $userid User ID
 * @return bool|mixed
 * @throws dml_exception
 */
function consentform_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    if (isset($cm->id)) {
        $cmid = $cm->id;
    } else {
        $cmid = $cm;
    }
    if ($state = $DB->get_field(
        'consentform_state', 'state', array('consentformcmid' => $cmid, 'userid' => $userid))) {
        return $state;
    } else {
        return false;
    }
}

/**
 * Called by course/reset.php
 */
function consentform_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'consentformheader', get_string('modulenameplural', 'consentform'));
    $mform->addElement('checkbox', 'reset_consentform', get_string('resetconsentform','consentform'));
}

function consentform_reset_userdata($data) {
    global $DB;

    $componentstr = get_string('modulenameplural', 'consentform');
    $status = array();

    if (!empty($data->reset_consentform)) {
        $consentformmoduleid = $DB->get_field('modules', 'id', array('name' => 'consentform'));
        $cms = $DB->get_records('course_modules', array('course' => $data->courseid, 'module' => $consentformmoduleid));
        foreach($cms as $cm) {
            $DB->delete_records('consentform_state', array('consentformcmid'=> $cm->id));
            $consentform = $DB->get_record('consentform', array('id' => $cm->instance), '*');
            consentform_clear_completions($consentform, $cm);
            consentform_grade_item_delete($consentform);
        }
        $status[] = array('component' => $componentstr, 'item' => get_string('resetok', 'consentform'), 'error' => false);
    }
    return $status;
}

function consentform_reset_course_form_defaults($course) {
    return array('reset_consentform'=>1);
}