<?php
// This file is part of mod_grouptool for Moodle - http://moodle.org/
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
 * Privacy class for requesting user data.
 *
 * @package    mod_consentform
 * @author     Thomas Niedermaier
 * @copyright  2022 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_consentform\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\metadata\provider as metadataprovider;
use core_privacy\local\request\userlist;
use \core_privacy\local\request\core_userlist_provider;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\plugin\provider as pluginprovider;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

// Global variable $CFG is always set, but with this little wrapper PHPStorm won't give wrong error messages!
if (isset($CFG)) {
    require_once($CFG->dirroot . '/mod/consentform/locallib.php');
}

/**
 * Privacy class for requesting user data.
 *
 * @package    mod_consentform
 * @author     Thomas Niedermaier
 * @copyright  2022 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadataprovider, pluginprovider, core_userlist_provider {
    /**
     * Provides meta data that is stored about a user with mod_consentform
     *
     * @param  collection $collection A collection of meta data items to be added to.
     * @return  collection Returns the collection of metadata.
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table(
            'consentform_state',
            [
                'userid' => 'privacy:metadata:userid',
                'state' => 'privacy:metadata:state',

            ],
            'privacy:metadata:consentform_state'
        );

        return $collection;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $params = [
            'modulename' => 'consentform',
            'contextid' => $context->id,
            'contextlevel' => CONTEXT_MODULE
        ];

        $sql = "SELECT cs.userid
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {consentform_state} cs ON cm.id = cs.consentformcmid
                 WHERE ctx.id = :contextid AND ctx.contextlevel = :contextlevel";
        // Get all users who have participated in this consentform instance.
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Returns all of the contexts that has information relating to the userid.
     *
     * @param  int $userid The user ID.
     * @return contextlist an object with the contexts related to a userid.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {

        $params = [
            'modulename' => 'consentform',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid
        ];

        $sql = "SELECT ctx.id
                 FROM {course_modules} cm
                 JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                 JOIN {consentform_state} cs ON cm.id = cs.consentformcmid
                 JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                WHERE (
                      cs.userid = :userid
                      )";
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Write out the user data filtered by contexts.
     *
     *
     * @param approved_contextlist $contextlist contexts that we are writing data out from.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $contexts = $contextlist->get_contexts();

        if (empty($contexts)) {
            return;
        }

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT c.id AS contextid, cf.id AS consentformid, cm.id AS cmid
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {consentform} cf ON cf.id = cm.instance
                 WHERE c.id {$contextsql}";

        $consentforms = $DB->get_records_sql($sql, $contextparams);

        $user = $contextlist->get_user();

        foreach ($consentforms as $consentform) {
            $context = \context_module::instance($consentform->cmid);

            // Check that the context is a module context.
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $consentformdata = helper::get_context_data($context, $user);

            writer::with_context($context)->export_data([], $consentformdata);

            static::export_states($context, $consentform, $user);

        }
    }

    /**
     * Fetches all of the user's states and adds them to the export
     *
     * @param  \context $context
     * @param  $consentform
     * @param  \stdClass $user
     * @param  array $path Current directory path that we are exporting to.
     * @throws \dml_exception
     */
    protected static function export_states(\context $context, $consentform, \stdClass $user) {
        global $DB;

        // Fetch all states of participants who have participated in this consentform instance.
        $params = [
            'userid' => $user->id,
            'consentform' => $consentform->consentformid
        ];

        $sql = "
                  SELECT cs.id, cs.userid, cs.timestamp, CASE cs.state WHEN 1 THEN :agreed WHEN 0 THEN :revoked WHEN -1 THEN :refused END as :state
                  FROM {consentform_states} cs
                  WHERE
                      cs.id = :consentform
                      AND (
                      cs.userid = :userid
                      )

              ";

        $rs = $DB->get_recordset_sql($sql, $params);

        foreach ($rs as $id => $cur) {
            writer::with_context($context)->export_data(['user state ' . $id], $cur);
        }

        $rs->close();
    }

    /**
     * Delete all use data which matches the specified context.
     *
     * @param \context $context The module context.
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_MODULE) {
            // Apparently we can't trust anything that comes via the context.
            $sql = "SELECT cs.consentformcmid
                    FROM {consentform} cf
                    JOIN {course_modules} cm ON cf.id = cm.instance AND cf.course = cm.course
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {consentform_state} cs ON cs.consentformcmid = cm.id
                    JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextmodule
                    WHERE ctx.id = :contextid";
            $params = ['modulename' => 'consentform', 'contextmodule' => CONTEXT_MODULE, 'contextid' => $context->id];
            $id = $DB->get_field_sql($sql, $params);
            // If we have a count over zero then we can proceed.
            if ($id > 0) {
                // Get all the state records of this consentform instance.
                $stateids = $DB->get_fieldset_select('consentform_state', 'id', 'consentformcmid = :consentformcmid', ['consentformcmid' => $id]);

                // Delete all state records of this instance.
                $DB->delete_records_list('consentform_state', 'id', $stateids);
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        $contextids = $contextlist->get_contextids();

        if (empty($contextids) || $contextids === []) {
            return;
        }

        list($ctxsql, $ctxparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');

        // Apparently we can't trust anything that comes via the context.
        $sql = "SELECT ctx.id AS ctxid, cm.cmid
                    FROM {consentform} cf
                    JOIN {course_modules} cm ON cf.id = cm.instance AND cf.course = cm.course
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextmodule
                    WHERE ctx.id " . $ctxsql;
        $params = ['modulename' => 'consentform', 'contextmodule' => CONTEXT_MODULE];
        if (!$records = $DB->get_records_sql($sql, $params + $ctxparams)) {
            return;
        }

        // Get all consentform instances of context.
        $consentformids = [];
        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $consentformids[] = $records[$context->id]->cmid;
        }
        if (empty($consentformids)) {
            return;
        }

        list($select, $params) = $DB->get_in_or_equal($consentformids);
        $csids = $DB->get_fieldset_select('consentform_state', 'id', 'consentformcmid ' . $select, $params);
        if (empty($csids)) {
            return;
        }
        // Delete all state records of this user.
        list($csidssql, $csidsparams) = $DB->get_in_or_equal($csids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('consentform_state',
            "(userid = :userid) AND csid " . $csidssql,
            $csidsparams + ['userid' => $user->id]);

    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_MODULE) {
            // Apparently we can't trust anything that comes via the context.
            $sql = "SELECT cf.id as id, cm.id as consentformcmid
                    FROM {consentform} cf
                    JOIN {course_modules} cm ON cf.id = cm.instance AND cf.course = cm.course
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextmodule
                    WHERE ctx.id = :contextid";
            $params = ['modulename' => 'consentform', 'contextmodule' => CONTEXT_MODULE, 'contextid' => $context->id];
            $consentform = $DB->get_record_sql($sql, $params);
            // If we have an id over zero then we can proceed.
            if (!empty($consentform) && $consentform->id > 0) {
                $userids = $userlist->get_userids();
                if (count($userids) <= 0) {
                    return;
                }
                // Get state records of this consentform instance.
                $csids = $DB->get_fieldset_select('consentform_state', 'id', 'consentformcmid = ?', [$consentform->consentformcmid]);
                list($csidssql, $csidsparams) = $DB->get_in_or_equal($csids, SQL_PARAMS_NAMED);

                list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
                // Delete all record states of these users in these slots.
                $DB->delete_records_select('consentform_state', "id " . $csidssql . " AND userid " . $usersql,
                    $csidsparams + $userparams);
            }
        }
    }

}
