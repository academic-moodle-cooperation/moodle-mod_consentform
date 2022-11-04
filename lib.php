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
 * @copyright  2020 Thomas Niedermaier, Medical University of Vienna <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
 * See plugin_supports() for more info.
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
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ADMINISTRATION;
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
        $iframeparms["name"] = "consentformiframe$consentform->id";
        $html = html_writer::tag("iframe", null, $iframeparms);
        $consentformintro = $html;
        $DB->set_field("consentform", "intro", $consentformintro, array("id" => $consentform->id));
    }
    if ($consentform->usegrade) {
        consentform_grade_item_update($consentform);
    }

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

    if ($consentform->usegrade) {
        consentform_grade_item_update($consentform);
    } else {
        consentform_grade_item_delete($consentform);
    }

    return $result;
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

    consentform_grade_item_update($consentform);

    rebuild_course_cache($consentform->course, false);

    return true;
}

/* Gradebook API */

/**
 * Creates or updates grade item for the given consentform instance
 *
 * Needed by grade_update_mod_grades().
 *
 * @param stdClass $consentform instance object with extra cmidnumber and modname property
 * @param grade_item $grades reset grades in the gradebook
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
        $item['reset'] = true;
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
 * Needed by grade_update_mod_grades().
 *
 * @param stdClass $consentform instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @throws coding_exception
 */
function consentform_update_grades(stdClass $consentform, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();

    grade_update('mod/consentform', $consentform->course, 'mod', 'consentform', $consentform->id, 0, $grades);
}

/**
 * Set user's grade according to reaction
 *
 * @param object $consentform
 * @param int $userid
 * @param bool $agreed
 * @return false|void
 */
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

/**
 * Called by course/reset.php
 *
 * @param \moodle_form $mform
 * @throws coding_exception
 */
function consentform_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'consentformheader', get_string('modulenameplural', 'consentform'));
    $mform->addElement('checkbox', 'reset_consentform', get_string('resetconsentform', 'consentform'));
}

/**
 * Reset consentform state userdata
 *
 * @param object $data
 * @return array
 * @throws coding_exception
 * @throws dml_exception
 */
function consentform_reset_userdata($data) {
    global $DB;

    $componentstr = get_string('modulenameplural', 'consentform');
    $status = array();

    if (!empty($data->reset_consentform)) {
        $consentformmoduleid = $DB->get_field('modules', 'id', array('name' => 'consentform'));
        $cms = $DB->get_records('course_modules', array('course' => $data->courseid, 'module' => $consentformmoduleid));
        foreach ($cms as $cm) {
            $DB->delete_records('consentform_state', array('consentformcmid' => $cm->id));
            $consentform = $DB->get_record('consentform', array('id' => $cm->instance), '*');
            consentform_grade_item_delete($consentform);
        }
        $status[] = array('component' => $componentstr, 'item' => get_string('resetok', 'consentform'), 'error' => false);
    }
    return $status;
}

/**
 * Reset consentform instance
 *
 * @param object $course
 * @return int[]
 */
function consentform_reset_course_form_defaults($course) {
    return array('reset_consentform' => 1);
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $consentformnode The node to add module settings to
 */
function consentform_extend_settings_navigation(settings_navigation $settings, navigation_node $consentformnode) {

    if (has_capability('mod/consentform:submit', $settings->get_page()->cm->context)) {
        $url = new moodle_url('/mod/consentform/listusers.php', array('id' => $settings->get_page()->cm->id));
        $consentformnode->add(get_string('listusers', 'consentform'), $url, navigation_node::TYPE_SETTING, null, 'listusers', null);
    }

}
