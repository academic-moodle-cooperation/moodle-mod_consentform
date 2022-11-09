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
 * Remove module description
 *
 * @package
 * @subpackage mod_consentform
 * @copyright 2022 Thomas Niedermaier (thomas.niedermaier@meduniwien.ac.at)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module mod_consentform/removedescription
  */
define(['jquery'], function($) {

    /**
     * @constructor
     * @alias module:mod_consentform/removedescription
     */
    var Removedescription = function() {};

    var instance = new Removedescription();

    instance.init = function() {
        $( document ).ready(function() {
            debugger;
            $('#intro').remove();
        });
        return true;
    };

    return instance;
});