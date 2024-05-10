// This file is part of mod_consentform for Moodle - http://moodle.org/
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
 * Check all or no checkboxes at once
 *
 * @package
 * @subpackage mod_consentform
 * @copyright 2019 Thomas Niedermaier (thomas.niedermaier@meduniwien.ac.at)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module mod_consentform/checkboxcontroller
  */
define(['jquery'], function($) {

    /**
     * @constructor
     * @alias module:mod_consentform/checkboxcontroller
     */
    var Checkboxcontroller = function() {
        // Table ID!
        this.table = $('#consentform_activitytable');
    };

    Checkboxcontroller.prototype.updateCheckboxes = function(e) {
        e.preventDefault();

        var type = e.data.type;
        var subtype = e.data.subtype;
        var checkboxes;

        if (subtype == 'section') {
            var classes = e.currentTarget.className.toString();
            var idx = classes.replace("co_section_all", "").replace("co_section_none", "").replace("section", "").replace(" ", "");
            var selector = 'input.section' + idx + ':checkbox';
            checkboxes = $(selector, e.data.inst.table);
        } else {
            checkboxes = $('td input:checkbox', e.data.inst.table);
        }

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

        $('.co_all').on('click', null, {inst: this, type: 'all', subtype: 'all'}, this.updateCheckboxes);
        $('.co_none').on('click', null, {inst: this, type: 'none', subtype: 'all'}, this.updateCheckboxes);

        $('.co_section_all').on(
            'click', null, {inst: this, type: 'all', subtype: 'section'},
            this.updateCheckboxes);
        $('.co_section_none').on(
            'click', null, {inst: this, type: 'none', subtype: 'section'},
            this.updateCheckboxes);

        $('#consentform_activitytable').css('visibility', 'visible');

        return true;
    };

    return instance;
});