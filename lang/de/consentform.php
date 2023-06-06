<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for component 'consentform', language 'de', version '4.0'.
 *
 * @package     consentform
 * @category    string
 * @copyright   1999 Martin Dougiamas and contributors
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['agree'] = 'Ich stimme zu';
$string['agreed'] = 'zugestimmt';
$string['agreementlogentry'] = 'Sie haben am {$a} zugestimmt.';
$string['backbuttonlist'] = 'Zurück zur Modulliste';
$string['choice'] = 'Treffen Sie Ihre Wahl:';
$string['configurations'] = 'Konfiguration Einverständniserklärung';
$string['confirmationtext'] = 'Text der Einverständniserklärung';
$string['confirmincourseoverview'] = 'Zustimmung in Kursübersicht';
$string['confirmincourseoverview_help'] = 'Die Zustimmung wird auf der Kursübersichtsseite abgefragt. Diese Option können Sie nur beim Anlegen dieses Moduls wählen.';
$string['confirmincourseoverviewdesc'] = 'Die Zustimmung wird auf der Kursübersichtsseite abgefragt.';
$string['consentform'] = 'Einverständniserklärung';
$string['consentform:addinstance'] = 'Einverständniserklärung hinzufügen';
$string['consentform:submit'] = 'Einverständniserklärung bearbeiten';
$string['consentform:view'] = 'Einverständniserklärung ansehen';
$string['consentformname'] = 'Name';
$string['consentformname_help'] = 'Geben Sie dieser Instanz einen Namen.';
$string['deletetestaction'] = 'Eigenen Testversuch löschen';
$string['deletetesterrormessage'] = 'Ihr Testversuch konnte nicht gelöscht werden. Ein technischer Fehler ist aufgetreten.';
$string['deletetestmessage'] = 'Ihr Testversuch wurde gelöscht';
$string['dependencies'] = 'Administration';
$string['dependent'] = 'Ohne Einverständniserklärung nicht betretbare Kursmodule';
$string['description'] = 'Beschreibung';
$string['downloadbuttonlabel'] = 'Herunterladen';
$string['eventagreementagree'] = 'Nutzer/in hat zugestimmt';
$string['eventagreementagreedesc'] = 'Nutzer/in mit der ID {$a->userid} hat der Einverständniserklärung des Moduls {$a->contextinstanceid} zugestimmt.';
$string['eventagreementrefuse'] = 'Nutzer/in hat NICHT zugestimmt';
$string['eventagreementrefusedesc'] = 'Nutzer/in mit der ID {$a->userid} hat der Einverständniserklärung des Moduls {$a->contextinstanceid} NICHT zugestimmt.';
$string['eventagreementrevoke'] = 'Nutzer/in hat die Zustimmung zurückgezogen';
$string['eventagreementrevokedesc'] = 'Nutzer/in mit der ID {$a->userid} hat die Zustimmung zur Einverständniserklärung des Moduls {$a->contextinstanceid} zurückgezogen.';
$string['linktexttomodulesettings'] = 'Zu den Einstellungen';
$string['listempty'] = 'Keine Einträge vorhanden.';
$string['listusers'] = 'Zustimmungen';
$string['modulelistlinktext'] = 'Abhängigkeiten Modulliste festlegen';
$string['modulename'] = 'Einverständniserklärung';
$string['modulename_help'] = 'Mit diesem Modul machen Sie den Zugang zu anderen Aktivitäten und Arbeitsmaterialien dieses Kurses von einer Einverständniserklärung abhängig, welche Sie in diesem Modul in den Moduleigenschaften definieren.';
$string['modulenameplural'] = 'Einverständniserklärungen';
$string['modules'] = 'Aktivitäten und Arbeitsmaterialien';
$string['msgagreed'] = 'Sie haben dieser Einverständniserklärung zugestimmt.';
$string['msgagreedfailure'] = 'Ihre Eingabe konnte nicht gespeichert werden. Bitte probieren Sie es noch einmal.';
$string['msgrefused'] = 'Sie haben dieser Einverständniserklärung NICHT zugestimmt.';
$string['msgrevoked'] = 'Sie haben Ihre Zustimmung zurückgezogen.';
$string['noaction'] = 'Keine Aktion';
$string['nocompletion'] = 'Die Einverständniserklärung benötigt das Feature "Abschlussverfolgung", welches in diesem Moodle nicht aktiviert ist. Wenden Sie sich an die Moodle-Administrator/innen';
$string['nocompletioncourse'] = 'Dieser KURS benötigt die Aktivierung des Features Abschlussverfolgung in den Kurseinstellungen, welche derzeit nicht gegeben ist.';
$string['nocompletionmodule'] = 'Dieses MODUL benötigt die Aktivierung des Features Abschlussverfolgung in den Moduleinstellungen, welche derzeit nicht gegeben ist.';
$string['nocompletiontitle'] = 'Abschlussverfolgung nicht aktiviert:';
$string['nocoursemoduleslist'] = 'Keine Kursmodul-Liste';
$string['nocoursemoduleslist_help'] = 'Diese Einverständniserklärung-Instanz zeigt keine Kursmodul-Liste zur Konfiguration der Abhängigkeiten von dieser Einverständniserklärung an. Sie kümmern sich selber um die Herstellung von Abhängigkeiten zu dieser Einverständniserklärung.';
$string['nocoursemoduleslistdesc'] = 'Keine Kursmodul-Auswahlliste. Wenn Sie die Abhängigkeiten selber einstellen möchten.';
$string['optionrefuse'] = 'Ablehnung';
$string['optionrefuse_help'] = 'Erlauben Sie Nutzer/innen, Ihre Einverständniserklärung explizit abzulehnen.';
$string['optionrefusedesc'] = 'Standard für die Option zur expliziten Ablehnung';
$string['optionrevoke'] = 'Widerrufsoption';
$string['optionrevoke_help'] = 'Erlauben Sie Nutzer/innen, die bereits zugestimmt haben, Ihre Einverständniserklärung wieder zurückzuziehen.';
$string['optionrevokedesc'] = 'Standard für die Option zum Widerruf';
$string['pluginadministration'] = 'Einverständniserklärung Verwaltung';
$string['pluginname'] = 'Einverständniserklärung';
$string['privacy:metadata:consentform_state'] = 'Information über den Zustimmungsstatus eines Users in dieser Einverständniserklärungs-Instanz.';
$string['privacy:metadata:state'] = 'Ein Wert für den Bestätigungs-Status dieses Users: 1 für zugestimmt, 0 für zurückgenommen, -1 für abgelehnt.';
$string['privacy:metadata:userid'] = 'Die User-ID eines Moodle-Users, der an dieser Einverständniserklärung teilgenommen hat.';
$string['refuse'] = 'Ich stimme nicht zu';
$string['refused'] = 'abgelehnt';
$string['refuselogentry'] = 'Sie haben am {$a} NICHT zugestimmt.';
$string['resetconsentform'] = 'Alle Aktions-Daten löschen';
$string['resetok'] = 'Alle Daten gelöscht';
$string['revocation'] = 'Widerrufen';
$string['revoke'] = 'Ich ziehe meine Zustimmung zurück';
$string['revoked'] = 'Zustimmung zurückgezogen';
$string['revokelogentry'] = 'Sie haben am {$a} Ihre Zustimmung zurückgezogen.';
$string['state'] = 'Status';
$string['textagreementbutton'] = 'Beschriftung Zustimmung-Button';
$string['textagreementbuttondesc'] = 'Standardwert für die Schaltfläche, mit der die Zustimmung gesendet wird.';
$string['textfields'] = 'Textfelder';
$string['textrefusalbutton'] = 'Beschriftung Ablehnung-Button';
$string['textrefusalbuttondesc'] = 'Standardwert für die Schaltfläche, mit der die Ablehnung gesendet wird.';
$string['textrevocationbutton'] = 'Beschriftung Widerruf-Button';
$string['textrevocationbuttondesc'] = 'Standardwert für die Schaltfläche, mit der der Widerruf gesendet wird.';
$string['timestamp'] = 'Datum';
$string['titleagreed'] = 'Zustimmungen';
$string['titleall'] = 'Alle';
$string['titlenone'] = 'Keine Aktion';
$string['titlerefused'] = 'Ablehnungen';
$string['titlerevoked'] = 'Widerrufe';
$string['usegrade'] = 'Bewertung verwenden';
$string['usegrade_help'] = 'Bei jeder Zustimmung von Teilnehmer/innen wird der Wert 1 für diese Person für dieses Modul in die Bewertungen geschrieben.';
$string['usegradedesc'] = 'Bewertung für den Export verwenden';
$string['warninguserentry'] = 'Achtung: Es wurde ein Einverständniserklärungs-Eintrag in den Voraussetzungen gefunden, der nicht von dieser Einverständniserklärung getätigt oder nachträglich verändert wurde. Überprüfen Sie dessen Wirksamkeit!';
$string['wrongoperator'] = 'Dieses Modul hat NICHT eine UND-Verknüpfung bei den Voraussetzungen. Diese {$a->consentform} ist daher bei diesem Modul u.U. wirkungslos!';
