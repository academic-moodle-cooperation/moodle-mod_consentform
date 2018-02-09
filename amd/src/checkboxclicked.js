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
 * @package mod
 * @subpackage confidential
 * @copyright 2018 Thomas Niedermaier (thomas.niedermaier@meduniwien.ac.at)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add or remove the restriction of a course module when checkbox is clicked
 */


define(
    ['jquery', 'core/config', 'core/log'], function($, config, log) {

        /**
     * @constructor
     * @alias module:mod_confidential/checkboxclicked
     */
        var Checkboxclicked = function() {
            this.nixxxx = "";
        };

        var instance = new Checkboxclicked();

        instance.init = function (param) {

            instance.nixxxx = param.nixxxx;

            // What happens when a course module checkbox is clicked.
            function checkboxclicked() {
                var ischecked = $(this).is(':checked');
                var value = $(this).val();
                transmitcheckboxclicked(ischecked, value);
            }

            $('.selectcoursemodule').on('click', checkboxclicked);

            function transmitcheckboxclicked(ischecked, value) {
                var cfg = {
                    method : 'get',
                    url : config.wwwroot + '/mod/confidential/setcontrol.php',
                    data: {
                        'sesskey': config.sesskey,
                        'ischecked': encodeURI(ischecked),
                        'value': encodeURI(value)
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        log.info("before transmit checkboxclicked isclicked: " + ischecked + " value: " + value, "confidential");
                    },
                    success: function(ret) {
                        var retjs = JSON.parse(ret);
                        log.info(retjs.status);
                        if (ret.status=="OK") {
                            log.info("transmit checkboxclicked OK", "confidential");
                        } else {
                            log.error("transmit checkboxclicked - DB UPDATE - FAILED", "confidential");
                        }
                    },
                    error: function() {
                        log.error("transmit checkboxclicked FAILED", "confidential");
                    }
                };

                $.ajax(cfg);
            }
        };

        return instance;

    }
);
