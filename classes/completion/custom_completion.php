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

declare(strict_types=1);

namespace mod_consentform\completion;

use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for the consentform activity.
 *
 * Class for defining mod_consentform's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given consentform instance and a user.
 *
 * @package   mod_consentform
 * @author    Thomas Niedermaier
 * @copyright 2020, Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        if ($rule === 'completionagree') {
            $status = $DB->record_exists(
                'consentform_state',
                [
                    'consentformcmid' => $this->cm->id,
                    'userid' => $this->userid,
                    'state' => \CONSENTFORM_STATUS_AGREED,
                ]
            );
            return $status ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }

        if ($rule === 'completionresponded') {
            $status = $DB->record_exists_select(
                'consentform_state',
                'consentformcmid = :cmid AND userid = :userid AND state IN (:agreed, :refused)',
                [
                    'cmid' => $this->cm->id,
                    'userid' => $this->userid,
                    'agreed' => \CONSENTFORM_STATUS_AGREED,
                    'refused' => \CONSENTFORM_STATUS_REFUSED,
                ]
            );
            return $status ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }

        return parent::get_state($rule);
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completionagree',
            'completionresponded',
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionagree' => get_string('completionagree', 'consentform'),
            'completionresponded' => get_string('completionresponded', 'consentform'),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionagree',
            'completionresponded',
        ];
    }
}
