<?php

/*
 * This file is included from listusers.php
*/

$sortkey   = clean_param($sortkey, PARAM_ALPHA);// Sorted view: lastname | firstname | email | timestamp
$sortorder = clean_param($sortorder, PARAM_ALPHA);   // it defines the order of the sorting (ASC or DESC)

// Creating the SQL statement.

// Initialise some variables.
$sqlorderby = '';
$sqlsortkey = NULL;

// Calculate the SQL sortkey to be used by the SQL statements later.
switch ( $sortkey ) {
    case "lastname":
        $sqlsortkey = "lastname";
        break;
    case "firstname":
        $sqlsortkey = "firstname";
        break;
    case "email":
        $sqlsortkey = "email";
        break;
    case "timestamp":
        $sqlsortkey = "timestamp";
        break;
}
$sqlsortorder = $sortorder;

if ($tab == CONSENTFORM_STATUS_NOACTION) {
    $enrolledview = get_enrolled_users($context, 'mod/consentform:view', 0, 'u.id, u.lastname, u.firstname, u.email',$sqlsortkey.' '.$sqlsortorder);
    $enrolledsubmit = get_enrolled_users($context, 'mod/consentform:submit', 0, 'u.id, u.lastname, u.firstname, u.email',$sqlsortkey.' '.$sqlsortorder);
    $sqlselect = "SELECT u.id, u.lastname, u.firstname, u.email ";
    $sqlfrom   = "FROM {consentform_state} c INNER JOIN {user} u ON c.userid = u.id ";
    $sqlwhere  = "WHERE (c.consentformcmid = $cm->id) ";
    $sqlorderby = "ORDER BY $sqlsortkey $sqlsortorder";
    $query = "$sqlselect $sqlfrom $sqlwhere $sqlorderby";
    $withaction = $DB->get_records_sql($query);
    $sqlresult = array_diff_key($enrolledview, $enrolledsubmit, $withaction);
    foreach($sqlresult as &$row) {
        $row->timestamp = CONSENTFORM_NOTIMESTAMP;
        $row->state = get_string('noaction', 'consentform');
    }
} else if ($tab == CONSENTFORM_ALL) {
    $enrolledview = get_enrolled_users($context, 'mod/consentform:view', 0, 'u.id, u.lastname, u.firstname, u.email',$sqlsortkey.' '.$sqlsortorder);
    $enrolledsubmit = get_enrolled_users($context, 'mod/consentform:submit', 0, 'u.id, u.lastname, u.firstname, u.email',$sqlsortkey.' '.$sqlsortorder);
    $sqlresult = array_diff_key($enrolledview, $enrolledsubmit);
    foreach($sqlresult as &$row) {
        if ($fields = $DB->get_record('consentform_state', array('userid' => $row->id, 'consentformcmid' => $cm->id), 'timestamp, state')) {
            $row->timestamp = $fields->timestamp;
            $row->state = $fields->state;
        } else {
            $row->timestamp = CONSENTFORM_NOTIMESTAMP;
            $row->state = get_string('noaction', 'consentform');
        }
    }
} else {
    $sqlselect = "SELECT u.id, u.lastname, u.firstname, u.email, c.timestamp, c.state ";
    $sqlfrom   = "FROM {consentform_state} c INNER JOIN {user} u ON c.userid = u.id ";
    $sqlwhere  = "WHERE (c.consentformcmid = $cm->id AND c.state = $tab) ";
    $sqlorderby = "ORDER BY $sqlsortkey $sqlsortorder";
    $query = "$sqlselect $sqlfrom $sqlwhere $sqlorderby";
    $sqlresult = $DB->get_records_sql($query);
}

