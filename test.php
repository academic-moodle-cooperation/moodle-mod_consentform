<?php
/**
 * Created by PhpStorm.
 * User: thoma
 * Date: 20.08.2019
 * Time: 13:53
 */

require_once(__DIR__ . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Get the params.
$ischecked = required_param('ischecked', PARAM_BOOL);  // Is the checkbox clicked or not?
$cmid_controlled = required_param('value', PARAM_INT);  // The ID of the cmid of the controlled module.
$cmid_controller = required_param('cmid', PARAM_INT);  // The ID of this confidential module (= the controller).


// Update
$ret = "?";
if ($ret = is_numeric($cmid_controlled)) {
    if ($ischecked) {
        if (!$ret = confidential_find_entry_availability($cmid_controlled, $cmid_controller)) {
            $ret = confidential_make_entry_availability($cmid_controlled, $cmid_controller);
        }
    } else {
        if (confidential_find_entry_availability($cmid_controlled, $cmid_controller)) {
            $ret = confidential_delete_entry_availability($cmid_controlled, $cmid_controller);
        }
    }
}
echo json_encode($ret);
