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
require('../../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');
require_once('../classes/verification_form.php');
require_once('../classes/common.php');


$context = \context_system::instance();
$PAGE->set_context($context);

$pageurl = new \moodle_url('/local/external_users/views/manage.php');
$PAGE->set_url($pageurl);

$PAGE->set_title(get_string('pluginname', 'local_external_users'));
$PAGE->set_heading(get_string('pluginname', 'local_external_users'));
$PAGE->set_pagelayout('standard');
require_capability('local/external_users:manage', $context);
$userid = required_param('id', PARAM_INT);

echo $OUTPUT->header();

global $DB;
$userfiles = $DB->get_records('local_external_users_files', array('userid' => $userid));
$user = $DB->get_record("user", array('id' => $userid));
profile_load_data($user);
// User picture!
if ($user->picture != "0") {
    $userpic = new \user_picture($user);
    $userpic->size = 128;
    echo $OUTPUT->render($userpic);
}

$profiledata = [
    'firstname' => $user->firstname ,
    'middlename' => $user->middlename,
    'lastname' => $user->lastname,
    'username' => get_string('username', 'local_external_users') . ": " . $user->username,
    'mail' => get_string('mail', 'local_external_users') . ": " . $user->email,
    'phone' => get_string('phone', 'local_external_users') . ": " . $user->phone1,
    'eduScope' => "EduScope : "  . $user->profile_field_eduPersonScopedAffiliation,
    'firstaccess' => "First access : "  . date("d.m.Y", $user->firstaccess),
];
$userinfo = text_to_html($OUTPUT->render_from_template("local_external_users/profile", $profiledata));
echo $userinfo;

$filetable = new \html_table();
$filetable->attributes['class'] = 'table table-striped';
$filetable->head = array(get_string('files', 'local_external_users'), get_string('download', 'local_external_users'));
$filetable->data = array();

foreach ($userfiles as $file) {
    $actionurl = \moodle_url::make_pluginfile_url($file->contextid, $file->component, $file->filearea,
    $file->userid, $file->filepath, $file->filename, false);
    $filetable->data[] = array(format_string($file->filearea), \html_writer::link($actionurl, $file->filename));
};

echo \html_writer::table($filetable);

$options = \html_writer::start_tag("div", array());
$options .= \html_writer::tag("input" , "",
    array("id" => "option1", "type" => "radio", "name" => "reject", "value" => 0, "checked" => ""));
$options .= \html_writer::tag("label" , "Send E-Mail to user", array("for" => "option1"));
$options .= \html_writer::end_tag("div");

$options .= \html_writer::start_tag("div", array());
$options .= \html_writer::tag("input" , "", array("id" => "option2", "type" => "radio", "name" => "reject", "value" => 1));
$options .= \html_writer::tag("label" , "Send E-Mail to user and delete user", array("for" => "option2"));
$options .= \html_writer::end_tag("div");

$action = is_user_verified($userid);

$profilecontrol = [
    'legend' => "Select a rejection reason",
    'options' => $options,
    'action' => !$action,
    'userid' => $userid,
    'btn1_style' => !$action ? "primary" : "warning",
];

if (is_user_verified($userid)) {
    $profilecontrol['revoke'] = get_string('revoke', 'local_external_users');
} else {
    $profilecontrol['approve'] = get_string('approve', 'local_external_users');
    $profilecontrol['approve-limited'] = get_string('approvelimited', 'local_external_users');;

}

$controls = text_to_html($OUTPUT->render_from_template("local_external_users/profile_control", $profilecontrol));
echo $controls;

echo $OUTPUT->footer();
