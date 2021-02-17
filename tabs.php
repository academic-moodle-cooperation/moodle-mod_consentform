<?php
// Get sum agreed, refused (when acitivated) and revoked (when activated).
$sumagreed = $DB->count_records("consentform_state", array ("consentformcmid" => $cm->id, "state" => CONSENTFORM_STATUS_AGREED));
$sumagreed = $sumagreed ? $sumagreed : 0;
if ($consentform->optionrefuse) {
    $sumrefused = $DB->count_records("consentform_state", array("consentformcmid" => $cm->id, "state" => CONSENTFORM_STATUS_REFUSED));
    $sumrefused = $sumrefused ? $sumrefused : 0;
} else {
    $sumrefused = 0;
}
if ($consentform->optionrevoke) {
    $sumrevoked = $DB->count_records("consentform_state", array("consentformcmid" => $cm->id, "state" => CONSENTFORM_STATUS_REVOKED));
    $sumrevoked = $sumrevoked ? $sumrevoked : 0;
} else {
    $sumrevoked = 0;
}
// Get no actions.
$enrolledview = get_enrolled_users($context, 'mod/consentform:view', 0, 'u.id'); // All participants.
$enrolledsubmit = get_enrolled_users($context, 'mod/consentform:submit', 0, 'u.id'); // All trainers etc.
$enrolledview = array_diff_key($enrolledview, $enrolledsubmit); // All participants who are not trainers.
$sqlselect = "SELECT u.id ";
$sqlfrom   = "FROM {consentform_state} c INNER JOIN {user} u ON c.userid = u.id ";
$sqlwhere  = "WHERE (c.consentformcmid = $cm->id) ";
$query = "$sqlselect $sqlfrom $sqlwhere";
$userswithaction = $DB->get_records_sql($query); // All users with reaction.
$usersnoactions = array_diff_key($enrolledview, $userswithaction); // Reduce participants who are not trainers by action users.
$sumnoaction = count($usersnoactions);

$tabrow = array();
$tabrow[] = new tabobject(CONSENTFORM_STATUS_AGREED, $CFG->wwwroot.'/mod/consentform/listusers.php?id='.$id.'&amp;tab='.CONSENTFORM_STATUS_AGREED,
        get_string('titleagreed', 'consentform')." (".$sumagreed.")");
if ($consentform->optionrefuse) {
    $tabrow[] = new tabobject(CONSENTFORM_STATUS_REFUSED, $CFG->wwwroot . '/mod/consentform/listusers.php?id=' . $id . '&amp;tab=' . CONSENTFORM_STATUS_REFUSED,
        get_string('titlerefused', 'consentform') . " (" . $sumrefused . ")");
}
if ($consentform->optionrevoke) {
    $tabrow[] = new tabobject(CONSENTFORM_STATUS_REVOKED, $CFG->wwwroot . '/mod/consentform/listusers.php?id=' . $id . '&amp;tab=' . CONSENTFORM_STATUS_REVOKED,
        get_string('titlerevoked', 'consentform') . " (" . $sumrevoked . ")");
}
$tabrow[] = new tabobject(CONSENTFORM_STATUS_NOACTION, $CFG->wwwroot.'/mod/consentform/listusers.php?id='.$id.'&amp;tab='.CONSENTFORM_STATUS_NOACTION,
    get_string('titlenone', 'consentform')." (".$sumnoaction.")");

$tabrows = array();
$tabrows[] = $tabrow;     // Always put these at the top.

echo html_writer::start_div('consentformdisplay');
print_tabs($tabrows, $tab);
echo html_writer::end_div();

$download = false;
switch ($tab) {
    case CONSENTFORM_STATUS_AGREED:
        if ($sumagreed) {
            $download = true;
        }
        break;
    case CONSENTFORM_STATUS_REFUSED:
        if ($sumrefused) {
            $download = true;
        }
        break;
    case CONSENTFORM_STATUS_REVOKED:
        if ($sumrevoked) {
            $download = true;
        }
        break;
    case CONSENTFORM_STATUS_NOACTION:
        if ($sumnoaction) {
            $download = true;
        }
        break;
}
