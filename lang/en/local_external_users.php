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
$string['verify_redirect'] = 'You must first be verified!';
$string['pending_header'] = 'Pending verification';
$string['already_verified_header'] = 'Already verified';
$string['rejected_header'] = "Rejected";
$string['rejection_control_header'] = "Select a rejection reason";
$string['rejection_control_mailonly'] = "Notify user by E-Mail only";
$string['rejection_control_maildelete'] = "Notify user by E-Mail and delete user";
$string['rejection_control_additional_comment'] = "Additional comment";
$string['reject'] = "Reject";


$string['username'] = 'Username';
$string['firstname'] = 'Firstname';
$string['lastname'] = 'Lastname';
$string['manage'] = 'Manage';
$string['approve'] = 'Approve';
$string['approvelimited'] = 'Approve for a single semester (until ';
$string['approvelimited2'] = 'Approve for a single semester +1 (until ';

$string['approved'] = 'Approved';
$string['pending'] = 'Onboarding (no filed uploaded yet)';
$string['rejected'] = "Rejected";
$string['waiting'] = 'Waiting for approval';

$string['revoke'] = 'Revoke';
$string['files'] = 'Files';
$string['download'] = 'Download';

$string['form_submit'] = "Submit";
$string['form_subject'] = "Subject";
$string['form_body'] = "Body";

$string['phone'] = "Telephone";
$string['mail'] = "E-Mail";

$string['pending_msg'] = "Your application is pending!";
$string['success'] = "Success!";
$string['pdf_error'] = "PDF File not valid!";
$string['redirect'] = "Redirecting";

$string['onboarding_description'] = "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.";
// MAIL Notification.
$string['rejection_subject'] = "Personal information is missing or invalid";

$string['external_users:manage'] = "Manage external user settings";
$string['external_users:verification'] = "Verify external user";

$string['usericon'] = '<i class="fa fa-user" aria-hidden="true"></i>';

$string['yes'] = "Yes";
$string['no'] = "No";
$string['limited'] = "Limited";

$string['approveduntil'] = "Verified until";
$string['uploadedfiles'] = "Uploaded files";
$string['birthdate'] = "Date of Birth";

$string['event_approved'] = 'External user approved';
$string['event_limitedapproved'] = 'External user limited approved';
$string['event_rejected'] = 'External user rejected';
$string['event_revoked'] = 'External user revoked';
$string['event_submit'] = 'External user submitted';

$string['form_image'] = 'Photo/scan of your ID document';
$string['form_document'] = 'Study confirmation or certificate of completion';

$string['pending_onboarding'] = 'User has not completed onboarding yet!';

$string['reviewsubject'] = 'External user submitted application form: ';
$string['reviewbody'] = 'An external user submitted their application and awaits approval.<b>Username: ';
