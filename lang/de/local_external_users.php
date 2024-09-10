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
 * Plugin strings are defined here.
 *
 * @package     local_external_users
 * @category    string
 * @copyright   2022 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'External Users';
$string['verify_redirect'] = 'Sie müssen zuerst zugelassen werden!';
$string['upload_picture_redirect'] = 'Laden Sie bitte ein Nutzerfoto hoch!';

$string['pending_header'] = 'In Bearbeitung';
$string['already_verified_header'] = 'Bereits zugelassen';
$string['rejected_header'] = "Abgelehnt";
$string['rejection_control_header'] = "Begründung der Ablehnung";
$string['rejection_control_mailonly'] = "Benachrichtigung des Benutzers per E-Mail";
$string['rejection_control_maildelete'] = "Benachrichtigung des Benutzers per E-Mail und Löschung des Benutzers";
$string['rejection_control_additional_comment'] = "Ergänzender Kommentar";
$string['reject'] = "Ablehnen";

$string['username'] = 'Benutzername';
$string['firstname'] = 'Vorname';
$string['lastname'] = 'Nachname';
$string['manage'] = 'Verwaltung';
$string['approve'] = 'Zulassen';
$string['approvelimited'] = 'Zulassung für ein Semester (bis ';
$string['approvelimited2'] = 'Zulassung für ein Semester +1 (bis ';

$string['approved'] = 'Zugelassen';
$string['pending'] = 'Im Onboarding (noch keine Dateien hochgeladen)';
$string['rejected'] = "Abgelehnt";
$string['waiting'] = 'In Bearbeitung';

$string['revoke'] = 'Widerruf';
$string['files'] = 'Dateien';
$string['download'] = 'Download';

$string['form_submit'] = "Absenden";
$string['form_subject'] = "Betreff";
$string['form_body'] = "Beschreibung";

$string['phone'] = "Telefon";
$string['mail'] = "E-Mail";

$string['pending_msg'] = "Ihr Antrag ist in Bearbeitung!";
$string['success'] = "Erfolg!";
$string['pdf_error'] = "PDF-Dokument ist nicht gültig!";
$string['redirect'] = "Weiterleitung";

$string['onboarding_description'] = "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.";
// MAIL Notification.
$string['rejection_subject'] = "Personal information is missing or invalid";

$string['external_users:manage'] = "Verwalte Externe Nutzer Einstellungen";
$string['external_users:verification'] = "Zulassung von Externen Nutzern";

$string['usericon'] = '<i class="fa fa-user" aria-hidden="true"></i>';

$string['yes'] = "Ja";
$string['no'] = "Nein";
$string['limited'] = "Befristet";

$string['approveduntil'] = "Zugelassen bis";
$string['uploadedfiles'] = "Hochgeladene Dateien";
$string['birthdate'] = "Geburtsdatum";

$string['event_approved'] = 'Externer Nutzer zugelassen';
$string['event_limitedapproved'] = 'Externer Nutzer befristet zugelassen';
$string['event_rejected'] = 'Externer Nutzer abgewiesen';
$string['event_revoked'] = 'Externer Nutzer widerrufen';
$string['event_submit'] = 'Externer Nutzer eingereicht';
$string['event_deactivated'] = 'Externer Nutzer deaktiviert/gelöscht';

$string['form_image'] = 'Foto/Scan Ihres Ausweisdokuments';
$string['form_document'] = 'Studienbestätigung oder Abschlusszeugnis';

$string['pending_onboarding'] = 'Nutzer befindet sich noch im Onboarding!';

$string['reviewsubject'] = 'Neuer externer Nutzer Antrag: ';
$string['reviewbody'] = 'Ein externer Nutzer hat einen Antrag eingereicht und wartet auf eine Freischaltung.<b>Username: ';

$string['affiliation'] = 'Akademische Zugehörigkeit';
$string['back_label'] = 'Zurück zur Übersicht';
