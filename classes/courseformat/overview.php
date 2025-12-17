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
 * Overview integration for consentform.
 *
 * @package    mod_consentform
 * @author     Clemens Marx
 * @copyright  2025, Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_consentform\courseformat;

use cm_info;
use core\output\renderer_helper;
use core\output\action_link;
use core\output\local\properties\button;
use core\output\local\properties\text_align;
use core_courseformat\local\overview\overviewitem;
use core_courseformat\activityoverviewbase;
use moodle_url;

/**
 * Provides data for the course overview tab.
 */
class overview extends activityoverviewbase {
    /** @var renderer_helper Helper for core renderers. */
    protected readonly renderer_helper $rendererhelper;

    /**
     * Constructor.
     *
     * @param cm_info $cm Course module info
     * @param renderer_helper $rendererhelper Helper for core renderers
     */
    public function __construct(
        cm_info $cm,
        renderer_helper $rendererhelper,
    ) {
        global $CFG;

        require_once($CFG->dirroot . '/mod/consentform/lib.php');
        $this->rendererhelper = $rendererhelper;
        parent::__construct($cm);
    }

    #[\Override]
    public function get_actions_overview(): ?overviewitem {
        if (!$this->is_teacher()) {
            return null;
        }

        return new overviewitem(
            name: get_string('actions'),
            value: get_string('manage', 'mod_consentform'),
            content: new action_link(
                url: new moodle_url('/mod/consentform/listusers.php', ['id' => $this->cm->id]),
                text: get_string('manage', 'mod_consentform'),
                attributes: ['class' => button::BODY_OUTLINE->classes()],
            ),
            textalign: text_align::CENTER,
        );
    }

    /**
     * Determine whether current user is in a grading/teacher role for this cm.
     */
    private function is_teacher(): bool {
        return has_capability('mod/consentform:submit', $this->context);
    }

    /**
     * Determine whether current user is a participant (no grading capability).
     */
    private function is_student(): bool {
        return has_capability('mod/consentform:view', $this->context)
            && !$this->is_teacher();
    }

    /**
     * Resolve current group filter (0 = all groups).
     */
    private function get_groupid_for_filter(): int {
        if (!$this->needs_filtering_by_groups()) {
            return 0;
        }
        return groups_get_activity_group($this->cm, true) ?? 0;
    }

    /**
     * Aggregate counts for teacher view.
     *
     * @return array{agreed:int,refused:int,revoked:int,noaction:int,total:int}
     */
    private function get_participant_counts(): array {
        global $DB;

        $groupid = $this->get_groupid_for_filter();
        $context = $this->context;

        // Participants = can view, but not submit (teachers filtered out).
        $enrolledview = get_enrolled_users($context, 'mod/consentform:view', $groupid, 'u.id', null, 0, 0, true);
        $enrolledsubmit = get_enrolled_users($context, 'mod/consentform:submit', $groupid, 'u.id');
        $participants = array_diff_key($enrolledview, $enrolledsubmit);

        if (empty($participants)) {
            return ['agreed' => 0, 'refused' => 0, 'revoked' => 0, 'noaction' => 0, 'total' => 0];
        }

        [$usersql, $params] = $DB->get_in_or_equal(array_keys($participants), SQL_PARAMS_NAMED, 'uid');
        $sql = "SELECT state, COUNT(*) AS num FROM {consentform_state} " .
            "WHERE consentformcmid = :cmid AND userid {$usersql} GROUP BY state";
        $params['cmid'] = $this->cm->id;
        $counts = $DB->get_records_sql_menu($sql, $params);

        $agreed = $counts[CONSENTFORM_STATUS_AGREED] ?? 0;
        $refused = $counts[CONSENTFORM_STATUS_REFUSED] ?? 0;
        $revoked = $counts[CONSENTFORM_STATUS_REVOKED] ?? 0;
        $total = \count($participants);
        $noaction = max($total - ($agreed + $refused + $revoked), 0);

        return [
            'agreed' => $agreed,
            'refused' => $refused,
            'revoked' => $revoked,
            'noaction' => $noaction,
            'total' => $total,
        ];
    }

    /**
     * Fetch current user decision state (student view).
     */
    private function get_user_state(): int {
        global $DB, $USER;

        $groupid = $this->get_groupid_for_filter();
        $context = $this->context;

        if (!is_enrolled($context, $USER, 'mod/consentform:view', true)) {
            return CONSENTFORM_STATUS_NOACTION;
        }

        if ($groupid && !groups_is_member($groupid, $USER->id)) {
            return CONSENTFORM_STATUS_NOACTION;
        }

        $state = $DB->get_field(
            'consentform_state',
            'state',
            ['consentformcmid' => $this->cm->id, 'userid' => $USER->id]
        );

        return ($state === false || $state === null) ? CONSENTFORM_STATUS_NOACTION : (int) $state;
    }

    /**
     * Extra overview items for consentform (teacher or student specific).
     *
     * @return array
     */
    #[\Override]
    public function get_extra_overview_items(): array {
        if ($this->is_teacher()) {
            return $this->get_teacher_overview_items();
        }

        if ($this->is_student()) {
            return $this->get_student_overview_items();
        }

        return [];
    }

    /**
     * Build overview items for teachers.
     *
     * @return array<string, overviewitem>
     */
    private function get_teacher_overview_items(): array {
        $counts = $this->get_participant_counts();
        $instance = $this->cm->get_instance_record();

        $items = [];

        $items['agreed'] = new overviewitem(
            name: get_string('overviewagreed', 'mod_consentform'),
            value: $counts['agreed'],
            content: $this->format_count_of_total($counts['agreed'], $counts['total']),
            textalign: text_align::CENTER,
        );

        if (!empty($instance->optionrefuse)) {
            $items['refused'] = new overviewitem(
                name: get_string('overviewrefused', 'mod_consentform'),
                value: $counts['refused'],
                content: $this->format_count_of_total($counts['refused'], $counts['total']),
                textalign: text_align::CENTER,
            );
        }

        if (!empty($instance->optionrevoke)) {
            $items['revoked'] = new overviewitem(
                name: get_string('overviewrevoked', 'mod_consentform'),
                value: $counts['revoked'],
                content: $this->format_count_of_total($counts['revoked'], $counts['total']),
                textalign: text_align::CENTER,
            );
        }

        $items['noaction'] = new overviewitem(
            name: get_string('overviewnoaction', 'mod_consentform'),
            value: $counts['noaction'],
            content: $this->format_count_of_total($counts['noaction'], $counts['total']),
            textalign: text_align::CENTER,
        );

        return $items;
    }

    /**
     * Build overview items for students.
     *
     * @return array<string, overviewitem>
     */
    private function get_student_overview_items(): array {
        $state = $this->get_user_state();

        $statetext = match ($state) {
            CONSENTFORM_STATUS_AGREED => get_string('agreed', 'mod_consentform'),
            CONSENTFORM_STATUS_REFUSED => get_string('refused', 'mod_consentform'),
            CONSENTFORM_STATUS_REVOKED => get_string('revoked', 'mod_consentform'),
            default => get_string('noaction', 'mod_consentform'),
        };

        $items = [];

        $items['mydecision'] = new overviewitem(
            name: get_string('overviewmydecision', 'mod_consentform'),
            value: $state,
            content: $statetext,
            textalign: text_align::CENTER,
        );

        return $items;
    }

    /**
     * Format count-of-total string using core helper.
     *
     * @param int $count Counted number of students in a state
     * @param int $total Total number of students
     * @return string Formatted string
     */
    private function format_count_of_total(int $count, int $total): string {
        return get_string('count_of_total', 'moodle', ['count' => $count, 'total' => $total]);
    }
}
