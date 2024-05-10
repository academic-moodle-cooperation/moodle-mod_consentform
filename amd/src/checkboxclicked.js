// This file is part of Moodle - http://moodle.org/.
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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Add or remove the restriction of a course module when checkbox is clicked
 *
 * @package
 * @subpackage mod_consentform
 * @copyright 2019 Thomas Niedermaier (thomas.niedermaier@meduniwien.ac.at)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_consentform/checkboxclicked
 */
define(['jquery', 'core/config'], function($, config) {
        /**
         *  @constructor
         *  @alias module:mod_consentform/checkboxclicked
         */
         var Checkboxclicked = function() {
            this.cmid = "";
         };

         var instance = new Checkboxclicked();

         instance.init = function(param) {

            instance.cmid = param.cmid;

            /**
             * Response indicator
             *
             * @param {string} val
             * @param {bool} checked
             */
             function response(val, checked) {
                 if (checked) {
                     $(":checkbox[value=" + val + "]").parent().parent().css("background-color", "lightgreen");
                 } else {
                     $(":checkbox[value=" + val + "]").parent().parent().css("background-color", "lightgrey");
                 }
             }

           /**
            * Make ajax call to setcontrol.php page to store if the activity shall be controlled by
            * this consentform instance.
            *
            * @param {bool} ischecked
            * @param {string} value
            */
            function transmitcheckboxclicked(ischecked, value) {

                $.get(config.wwwroot + '/mod/consentform/setcontrol.php', {
                    sesskey: config.sesskey,
                    ischecked: encodeURI(ischecked),
                    value: encodeURI(value),
                    cmid: instance.cmid
                    }, 'json').done(function(data) {
                        if (data == 1 && ischecked) {
                            response(value, 1);
                        } else if (data == 2 && !ischecked) {
                            response(value, 0);
                        }
                    });
            }

           /**
            * Calls ajax-call function if checkbox is clicked
            */
            function checkboxclicked() {
                var ischecked = $(this).is(':checked');
                var value = $(this).val();
                transmitcheckboxclicked(ischecked, value);
            }

            $('.selectcoursemodule').on('click', checkboxclicked);

         };

        return instance;

    }
);