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
 *
 * @package   local_external_users
 * @copyright 2022 Stephan Lorbek
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_external_users;

// @codingStandardsIgnoreStart
use context_system;
use html_table;
use html_writer;
use moodle_url;
use function get_string;

require('../../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/datalib.php');
require_once('../classes/verification_form.php');
require_once('../classes/common.php');


$context = context_system::instance();
$PAGE->set_context($context);

$pageurl = new moodle_url('/local/external_users/views/manage.php');
$PAGE->set_url($pageurl);

$PAGE->set_title(get_string('pluginname', 'local_external_users'));
$PAGE->set_heading(get_string('pluginname', 'local_external_users'));
$PAGE->set_pagelayout('standard');
require_capability('local/external_users:manage', $context);
$userid = required_param('id', PARAM_INT);

echo $OUTPUT->header();

global $DB;
$userfiles = $DB->get_records('local_external_users_files',
    array('userid' => $userid));
$user = $DB->get_record("user", array('id' => $userid));
profile_load_data($user);


// User picture!
/*
if ($user->picture != "0") {
    $userpic = new \user_picture($user);
    $userpic->size = 128;
    echo $OUTPUT->render($userpic);
}
*/

$approveduntil = ($user->profile_field_external_user_verified != '0' &&
    $user->profile_field_external_user_verified != '1') ?
    $user->profile_field_external_user_verified : "-";

$verifiedfield = "";

switch ($user->profile_field_external_user_verified) {
    case "0":
        $verifiedfield = get_string('no',
            'local_external_users');
        break;
    case "1":
        $verifiedfield = get_string('yes',
            'local_external_users');
        break;
    case "-1":
        $verifiedfield = get_string('no',
            'local_external_users');
        break;
    default:
        $verifiedfield = get_string('limited',
            'local_external_users');
}

$profiledata = [
    'firstname' => $user->firstname,
    'middlename' => $user->middlename,
    'lastname' => $user->lastname,
    'mail' => get_string('mail', 'local_external_users') . ": " . $user->email,
    'verified' => get_string('approved',
            'local_external_users') . ": " . $verifiedfield,
    'verifiedtill' => get_string('approveduntil',
            'local_external_users') . ": " . $approveduntil,
    'eduScope' => "EduScope : " . $user->profile_field_eduPersonScopedAffiliation,
    'uploadedfiles_header' => get_string('uploadedfiles',
        'local_external_users'),
    'birthdate' => get_string('birthdate',
            'local_external_users') . ": " . date('d.m.Y',
            property_exists($user, 'profile_field_gebdat') ? $user->profile_field_gebdat : null),
];

$filetable = new html_table();
$filetable->attributes['class'] = 'table table-striped';
$filetable->head = array(get_string('download', 'local_external_users'));
$filetable->data = array();


if(!$DB->record_exists("local_external_users_files",
    array("userid" => $userid))) {
    echo $OUTPUT->notification(get_string('pending_onboarding', 'local_external_users'), 'notifymessage');
    $profiledata['userpiclink'] = "";
}
else {
    $userpic = $DB->get_record_sql("SELECT * FROM {local_external_users_files} WHERE userid = :userid and filearea LIKE 'userfileimage'",
        array("userid" => $userid));
    if (isset($userpic)) {
        $actionurl = moodle_url::make_pluginfile_url($userpic->contextid,
            $userpic->component, $userpic->filearea,
            $userpic->userid, $userpic->filepath, $userpic->filename, false);
        $profiledata['userpiclink'] = $actionurl;
    }
}


foreach ($userfiles as $file) {
    $actionurl = moodle_url::make_pluginfile_url($file->contextid,
        $file->component, $file->filearea,
        $file->userid, $file->filepath, $file->filename, false);
    $filetable->data[] = array(html_writer::link($actionurl, $file->filename));
}
$profiledata['filetable'] = html_writer::table($filetable);

$userinfo = text_to_html($OUTPUT->render_from_template("local_external_users/profile",
    $profiledata));
echo $userinfo;

$options = html_writer::start_tag("div", array());
$options .= html_writer::tag("input", "",
    array("id" => "option1", "type" => "radio", "name" => "reject", "value" => 0, "checked" => ""));
$options .= html_writer::tag("label",
    get_string('rejection_control_mailonly', 'local_external_users'),
    array("for" => "option1", "class" => "px-1"));
$options .= html_writer::end_tag("div");

$options .= html_writer::start_tag("div", array());
$options .= html_writer::tag("input", "",
    array("id" => "option2", "type" => "radio", "name" => "reject", "value" => 1));
$options .= html_writer::tag("label",
    get_string('rejection_control_maildelete', 'local_external_users'),
    array("for" => "option2", "class" => "px-1"));
$options .= html_writer::end_tag("div");

$action = is_user_verified($userid);

$profilecontrol = [
    'legend' => get_string('rejection_control_header', 'local_external_users'),
    'options' => $options,
    'action' => !$action,
    'userid' => $userid,
    'btn1_style' => !$action ? "primary" : "warning",
    'rejection_control_additional_comment' => get_string('rejection_control_additional_comment',
        'local_external_users'),
    'rejection_header' => get_string('reject', 'local_external_users'),
];

if (is_user_verified($userid)) {
    $profilecontrol['revoke'] = get_string('revoke', 'local_external_users');
} else {
    $profilecontrol['approve'] = get_string('approve', 'local_external_users');
    $profilecontrol['approve-limited'] = get_string('approvelimited',
            'local_external_users') . getEndOfSemester() . ")";
    $profilecontrol['approve-limited2'] = get_string('approvelimited2',
            'local_external_users') . getEndOfNextSemester() . ")";
}

$controls = text_to_html($OUTPUT->render_from_template("local_external_users/profile_control",
    $profilecontrol));
echo $controls;

echo $OUTPUT->footer();
