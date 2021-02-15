<?php

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
$righttoview = count_enrolled_users($context, 'mod/consentform:view');
$righttoview = $righttoview ? $righttoview : 0;
$righttosubmit = count_enrolled_users($context, 'mod/consentform:submit');
$righttosubmit = $righttosubmit ? $righttosubmit : 0;
$sumnoaction = $righttoview - $righttosubmit - $sumagreed - $sumrevoked - $sumrefused;

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

