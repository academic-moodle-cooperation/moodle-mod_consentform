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

$string['agree'] = 'Ich stimme zu';
$string['agreed'] = 'zugestimmt';
$string['agreementlogentry'] = 'Sie haben am {$a} zugestimmt.';
$string['backbutton'] = 'Zurück zur Kursübersicht';
$string['backbuttonlist'] = 'Zurück zur Modulliste';
$string['choice'] = 'Treffen Sie Ihre Wahl: ';
$string['configurations'] = 'Konfiguration Einverständniserklärung';
$string['confirmationtext'] = 'Text der Einverständniserklärung';
$string['confirmincourseoverview'] = 'Zustimmung in Kursübersicht';
$string['confirmincourseoverview_help'] = 'Die Zustimmung wird schon auf der Kursübersichtsseite abgefragt.';
$string['confirmincourseoverviewdesc'] = 'Zustimmung in Kursübersicht';
$string['consentform'] = 'Einverständniserklärung';
$string['consentform:addinstance'] = 'Einverständniserklärung hinzufügen';
$string['consentform:submit'] = 'Einverständniserklärung bearbeiten';
$string['consentform:view'] = 'Einverständniserklärung ansehen';
$string['consentformname'] = 'Name';
$string['consentformname_help'] = 'Geben Sie dieser Instanz einen Namen.';
$string['dependent'] = 'Ohne Einverständniserklärung nicht betretbare Kursmodule';
$string['description'] = 'Beschreibung';
$string['downloadbuttonlabel'] = 'Herunterladen';
$string['eventagreementagree'] = 'Benutzer/in hat zugestimmt';
$string['eventagreementagreedesc'] = 'Der/die Benutzer/in mit der ID {$a->userid} hat der Einverständniserklärung des Moduls {$a->contextinstanceid} zugestimmt.';
$string['eventagreementrefuse'] = "Benutzer/in hat NICHT zugestimmt";
$string['eventagreementrefusedesc'] = 'Der/die Benutzer/in mit der ID {$a->userid} hat der Einverständniserklärung des Moduls {$a->contextinstanceid} NICHT zugestimmt.';
$string['eventagreementrevoke'] = "Benutzer/in hat die Zustimmung zurückgezogen";
$string['eventagreementrevokedesc'] = 'Der/die Benutzer/in mit der ID {$a->userid} hat die Zustimmung zur Einverständniserklärung des Moduls {$a->contextinstanceid} zurückgezogen.';
$string['listempty'] = 'Keine Einträge vorhanden.';
$string['listusers'] = 'Reaktionen';
$string['modulename'] = 'Einverständniserklärung';
$string['modulename_help'] = 'Mit diesem Modul machen Sie den Zugang zu anderen Aktivitäten und Arbeitsmaterialien dieses Kurses von einer Einverständniserklärung abhängig, welche Sie in diesem Modul in den Moduleigenschaften definieren.';
$string['modulenameplural'] = 'Einverständniserklärungen';
$string['modules'] = 'Aktivitäten und Arbeitsmaterialien';
$string['msgagreed'] = 'Sie haben dieser Einverständniserklärung zugestimmt.';
$string['msgagreedfailure'] = 'Ihre Eingabe konnte nicht gespeichert werden. Bitte probieren Sie es noch einmal.';
$string['msgrefused'] = 'Sie haben dieser Einverständniserklärung NICHT zugestimmt.';
$string['msgrevoked'] = 'Sie haben Ihre Zustimmung zurückgezogen.';
$string['noaction'] = 'Keine Reaktion';
$string['nocompletion'] = 'Die Einverständniserklärung benötigt das Feature "Abschlussverfolgung", welches in diesem Moodle nicht aktiviert ist. Wenden Sie sich an Ihre/n Moodle-Administrator/in.';
$string['nocompletioncourse'] = 'Dieser KURS benötigt die Aktivierung des Features Abschlussverfolgung in den Kurseinstellungen, welche derzeit nicht gegeben ist.';
$string['nocompletionmodule'] = 'Dieses MODUL benötigt die Aktivierung des Features Abschlussverfolgung in den Moduleinstellungen, welche derzeit nicht gegeben ist.';
$string['nocompletiontitle'] = 'Abschlussverfolgung nicht aktiviert: ';
$string['nocoursemoduleslist'] = 'Keine Kursmodul-Liste';
$string['nocoursemoduleslist_help'] = 'Es wird keine Kursmodul-Liste zur Konfiguration der Abhängigkeiten verwendet';
$string['nocoursemoduleslistdesc'] = 'Keine Kursmodul-Auswahlliste. Wenn Sie die Abhängigkeiten selber einstellen möchten.';
$string['optionrefuse'] = 'Ablehnung';
$string['optionrefuse_help'] = 'Erlauben Sie Benutzern, Ihre Einverständniserklärung explizit abzulehnen.';
$string['optionrefusedesc'] = 'Standard für die Option zur expliziten Ablehnung';
$string['optionrevoke'] = 'Widerrufsoption';
$string['optionrevoke_help'] = 'Erlauben Sie Benutzern, die bereits zugestimmt haben, Ihre Einverständniserklärung wieder zurückzuziehen.';
$string['optionrevokedesc'] = 'Standard für die Option zum Widerruf';
$string['pluginadministration'] = 'Einverständniserklärung Verwaltung';
$string['pluginname'] = 'Einverständniserklärung';
$string['privacy:null_reason'] = 'Dieses Plugin speichert keine Userdaten. Es steuert lediglich die Sichtbarkeit von Modulen abhängig von einer Einverständniserklärung.';
$string['refuse'] = 'Ich stimme nicht zu';
$string['refused'] = 'abgelehnt';
$string['refuselogentry'] = 'Sie haben am {$a} NICHT zugestimmt.';
$string['resetconsentform'] = 'Alle Daten löschen';
$string['resetok'] = 'Alle Daten gelöscht';
$string['revocation'] = 'Widerrufen';
$string['revoke'] = 'Ich ziehe meine Zustimmung zurück';
$string['revoked'] = 'Zustimmung zurückgezogen';
$string['revokelogentry'] = 'Sie haben am {$a} Ihre Zustimmung zurückgezogen.';
$string['textagreementbutton'] = 'Beschriftung Zustimmungs-Button';
$string['textagreementbuttondesc'] = 'Standardwert für die Schaltfläche, mit der die Zustimmung gesendet wird.';
$string['textfields'] = 'Textfelder';
$string['textrefusalbutton'] = 'Beschriftung Ablehnungs-Button';
$string['textrefusalbuttondesc'] = 'Standardwert für die Schaltfläche, mit der die Ablehnung gesendet wird.';
$string['textrevocationbutton'] = 'Beschriftung Widerrufs-Button';
$string['textrevocationbuttondesc'] = 'Standardwert für die Schaltfläche, mit der der Widerruf gesendet wird.';
$string['timestamp'] = 'Datum';
$string['titleagreed'] = 'Zustimmungen';
$string['titlenone'] = 'Keine Aktion';
$string['titlerevoked'] = 'Widerrufe';
$string['titlerefused'] = 'Ablehnungen';
$string['usegrade'] = 'Bewertung verwenden';
$string['usegrade_help'] = 'Bei jeder Zustimmung eines/r Teilnehmer/in wird der Wert 1 für diese/n Teilnehmer/in für dieses Modul in die Bewertungen geschrieben.';
$string['usegradedesc'] = 'Bewertung für den Export verwenden';
$string['wrongoperator'] = 'Dieses Modul hat NICHT eine UND-Verknüpfung bei den Voraussetzungen. Diese {$a->consentform} ist daher bei diesem Modul u.U. wirkungslos!';