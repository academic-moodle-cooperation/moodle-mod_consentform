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


/**
 * English strings for consentform
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_consentform
 * @copyright  2020 Thomas Niedermaier <thomas.niedermaier@meduniwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Einverständniserklärung';
$string['modulenameplural'] = 'Einverständniserklärungen';
$string['modulename_help'] = 'Mit diesem Modul machen Sie den Zugang zu anderen Aktivitäten und Arbeitsmaterialien dieses Kurses von einer Einverständniserklärung abhängig, welche Sie in diesem Modul in den Moduleigenschaften definieren.';
$string['description'] = 'Beschreibung';
$string['consentform:addinstance'] = 'Einverständniserklärung hinzufügen';
$string['consentform:submit'] = 'Einverständniserklärung bearbeiten';
$string['consentform:view'] = 'Einverständniserklärung ansehen';
$string['consentformname'] = 'Name';
$string['consentform'] = 'Einverständniserklärung';
$string['pluginadministration'] = 'Einverständniserklärung Verwaltung';
$string['pluginname'] = 'Einverständniserklärung';
$string['confirmationtext'] = 'Text der Einverständniserklärungs';
$string['modules'] = 'Aktivitäten und Arbeitsmaterialien';
$string['dependent'] = 'Folgende Module werden von diesem Modul kontrolliert';
$string['noavailability'] = 'Dieses Modul benötigt das Feature "Verfügbarkeit", welches in diesem Moodle nicht aktiviert ist. Wenden Sie sich an Ihre/n Moodle-Administrator/in.';
$string['nocompletion'] = 'Dieses Modul benötigt das Feature "Abschlussverfolgung", welches in diesem Moodle nicht aktiviert ist. Wenden Sie sich an Ihre/n Moodle-Administrator/in.';
$string['nocompletioncourse'] = 'Dieses Modul benötigt die Aktivierung des Features "Abschlussverfolgung" in diesem Kurs, welche derzeit nicht gegeben ist.';
$string['nocompletionmodule'] = 'Dieses Modul benötigt die Aktivierung des Features "Abschlussverfolgung", welche derzeit nicht gegeben ist.';
$string['agree'] = 'Ich stimme zu';
$string['disagree'] = 'Ich stimme nicht zu';
$string['choice'] = 'Treffen Sie Ihre Wahl: ';
$string['msgagreed'] = 'Sie haben dieser Einverständniserklärung zugestimmt.';
$string['msgdisagreed'] = 'Sie haben dieser Einverständniserklärung NICHT zugestimmt.';
$string['msgagreedfailure'] = 'Ihre Eingabe konnte nicht gespeichert werden. Bitte probieren Sie es noch einmal.';
$string['eventagreementagree'] = "Benutzer/in hat zugestimmt";
$string['eventagreementagreedesc'] = 'Der/die Benutzer/in mit der ID {$a->userid} hat der Einverständniserklärung des Moduls {$a->contextinstanceid} zugestimmt.';
$string['eventagreementdisagree'] = "Benutzer/in hat NICHT zugestimmt";
$string['eventagreementagreedesc'] = 'Der/die Benutzer/in mit der ID {$a->userid} hat der Einverständniserklärung des Moduls {$a->contextinstanceid} NICHT zugestimmt.';
$string['revocation'] = 'Widerrufen';
$string['optiondisagree'] = 'Widerrufsoption';
$string['optiondisagreedesc'] = 'Widerrufsoption';
$string['optiondisagree_help'] = 'Erlauben Sie Benutzern, die bereits zugestimmt haben, Ihre Einverständniserklärung wieder zurückzuziehen.';
$string['usegrade'] = 'Bewertung verwenden';
$string['usegradedesc'] = 'Bewertung verwenden';
$string['usegrade_help'] = 'Bei jeder Zustimmung eines/r Teilnehmer/in wird der Wert 1 für diese/n Teilnehmer/in für dieses Modul in die Bewertungen geschrieben.';
$string['agreementlogentry'] = 'Sie haben am {$a} zugestimmt.';
$string['disagreementlogentry'] = 'Sie haben am {$a} NICHT zugestimmt.';
$string['privacy:null_reason'] = 'Dieses Plugin speichert keine Userdaten. Es steuert lediglich die Sichtbarkeit von Modulen abhängig von einer Einverständniserklärung.';
$string['backbutton'] = 'Zurück zur Kursübersicht';
$string['wrongoperator'] = 'Dieses Modul hat NICHT eine UND-Verknüpfung bei den Voraussetzungen. Diese {$a->consentform} ist daher bei diesem Modul u.U. wirkungslos!';
$string['nocompletiontitle'] = 'Abschlussverfolgung nicht aktiviert';
$string['resetconsentform'] = 'Alle Daten löschen';
$string['resetok'] = 'Alle Daten gelöscht';