// This file is part of mod_confidential for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * checkboxcontroller.js
 *
 * @package mod
 * @subpackage confidential
 * @copyright 2019 Thomas Niedermaier (thomas.niedermaier@meduniwien.ac.at)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module mod_confidential/checkboxcontroller
  */
define(['jquery', 'core/log'], function($, log) {

    /**
     * @constructor
     * @alias module:mod_confidential/checkboxcontroller
     */
    var Checkboxcontroller = function() {
        // Table ID!
        this.table = $('#confidential_activitytable');
    };

    /**
     * Function updateSummary() updates the displayed summary during submission edit
     *
     * @param {Event} e event object
     * @return {bool} true if everything's alright (no error handling by now)
     */
    Checkboxcontroller.prototype.updateCheckboxes = function(e) {
        e.preventDefault();
        //e.stopPropagation();

        var type = e.data.type;

        log.info('Update checkboxes (type = ' + type + ')');

        var checkboxes = $('td input:checkbox', e.data.inst.table);

        if (type == 'all') {
            checkboxes.each(function(idx, current) {
                if ($(current).prop('checked') == false) {
                    $(current).click();
                }
            });
        } else if (type == 'none') {
            checkboxes.each(function(idx, current) {
                if ($(current).prop('checked') == true) {
                    $(current).click();
                }
            });
        }

        return true;
    };

    var instance = new Checkboxcontroller();

    /**
     * Initializer registers event-listeners for each checkbox
     *
     * @return {bool} true if everything's ok (no error-handling implemented)
     */
    instance.init = function() {
        log.debug("Init checkboxcontroller for table " + this.table, 'confidential');

        $('.co_all').on('click', null, {inst: this, type: 'all'}, this.updateCheckboxes);
        $('.co_none').on('click', null, {inst: this, type: 'none'}, this.updateCheckboxes);

        return true;
    };

    return instance;
});
