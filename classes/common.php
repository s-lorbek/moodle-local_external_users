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

use context_system;
use DateTime;
use local_external_users\event\user_approved;
use local_external_users\event\user_limitedapproved;
use local_external_users\event\user_rejected;
use local_external_users\event\user_revoked;
use function user_delete_user;

defined('MOODLE_INTERNAL') || die();

require_once('mail.php');
require_once('message.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');


function get_user_files($userid)
{
    global $DB;
    return $DB->get_records("local_external_users_files",
        array('userid' => $userid));
}

function get_users_for_verification()
{
    global $DB;
    $dataset = $DB->get_records("user",
        array("auth" => "external", "deleted" => 0));
    $resultset = array();
    foreach ($dataset as $user) {
        profile_load_data($user);
        if ($user->profile_field_external_user_verified == '0') {
            if($user->profile_field_external_user_pending == '0') {
                $user->username .= ' <i class="fa fa-hourglass" aria-hidden="true"></i>';
            }
            $resultset[] = $user;
        }
    }
    return $resultset;
}

function get_users_already_verified()
{
    global $DB;
    $dataset = $DB->get_records("user",
        array("auth" => "external", "deleted" => 0));
    $resultset = array();
    foreach ($dataset as $user) {
        profile_load_data($user);
        if ($user->profile_field_external_user_verified != '0' && $user->profile_field_external_user_verified != '-1') {
            if($user->profile_field_external_user_verified != '1') {
                $user->username .= " (L)";
            }
            $resultset[] = $user;
        }
    }
    return $resultset;
}

function get_users_rejected()
{
    global $DB;
    $dataset = $DB->get_records("user",
        array("auth" => "external", "deleted" => 0));
    $resultset = array();
    foreach ($dataset as $user) {
        profile_load_data($user);
        if ($user->profile_field_external_user_verified == '-1') {
            $resultset[] = $user;
        }
    }
    return $resultset;
}

function valid_pdf($file)
{
    return preg_match("/^%PDF-/", $file);
}

function is_user_verified($userid)
{
    global $DB;
    if (!$DB->record_exists("user",
            array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    profile_load_data($user);
    return intval($user->profile_field_external_user_verified);
}

function verify_user($userid, $tariff)
{
    global $DB, $COURSE, $USER, $PAGE;
    if (!$DB->record_exists("user",
            array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    profile_load_data($user);

    $event = user_approved::create(array(
        'relateduserid' => $userid,
        'context' => $PAGE->context,
        'objectid' => $USER->id,
        'other' => array(
            'oldstatus' => $user->profile_field_external_user_verified,
            'userid' => $userid,
        )
    ));
    $event->trigger();

    $user->profile_field_external_user_verified = 1;
    $user->profile_field_external_user_pending = false;
    $user->profile_field_eduPersonScopedAffiliation = $tariff;
    profile_save_data($user);


}

function getEndOfSemester()
{
    $today = new DateTime();
    $enddate = $today;
    $currentmonth = $today->format('n');
    $summerterm = array(3, 4, 5, 6, 7, 8, 9);

    if (in_array($currentmonth, $summerterm)) {
        return $enddate->format("t.09.Y");
    } else {
        $enddate = $enddate->format("t.02.Y");
        $enddate = new DateTime("+12 months $enddate");
        return $enddate->format("t.m.Y");
    }
}

function getEndOfNextSemester()
{
    $today = new DateTime();
    $enddate = $today;
    $enddate = $enddate->format("t.02.Y");
    $enddate = new DateTime("+12 months $enddate");
    return $enddate->format("t.m.Y");
}

function limited_verify_user($userid, $tariff)
{
    global $DB, $PAGE, $USER;
    if (!$DB->record_exists("user",
            array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    profile_load_data($user);

    $limited = "";
    if ($tariff == "limited2")
        $limited = getEndOfNextSemester();
    else
        $limited = getEndOfSemester();

    $event = user_limitedapproved::create(array(
        'relateduserid' => $userid,
        'context' => $PAGE->context,
        'objectid' => $USER->id,
        'other' => array(
            'oldstatus' => $user->profile_field_external_user_verified,
            'newstatus' => $limited,
            'userid' => $userid,
        )
    ));
    $event->trigger();

    $user->profile_field_external_user_verified = $limited;

    $user->profile_field_external_user_pending = false;
    $user->profile_field_eduPersonScopedAffiliation = 'external';
    profile_save_data($user);
}


function revoke_user($userid)
{
    global $DB, $COURSE, $USER, $PAGE;
    if (!$DB->record_exists("user",
            array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    profile_load_data($user);

    $event = user_revoked::create(array(
        'relateduserid' => $userid,
        'context' => $PAGE->context,
        'objectid' => $USER->id,
        'other' => array(
            'oldstatus' => $user->profile_field_external_user_verified,
            'userid' => $userid,
        )
    ));
    $event->trigger();

    $user->profile_field_external_user_verified = 0;
    $user->profile_field_external_user_pending = false;
    $user->profile_field_eduPersonScopedAffiliation = "";
    //send_message_to_user($userid, null, "");
    profile_save_data($user);
}

function reject_user($userid, $action, $comment)
{
    global $DB, $COURSE, $USER, $PAGE;
    if (!$DB->record_exists("user",
            array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    profile_load_data($user);

    $event = user_rejected::create(array(
        'relateduserid' => $userid,
        'context' => $PAGE->context,
        'objectid' => $USER->id,
        'other' => array(
            'oldstatus' => $user->profile_field_external_user_verified,
            'userid' => $userid,
        )
    ));
    $event->trigger();

    $user->profile_field_external_user = 1;
    $user->profile_field_external_user_verified = -1;
    $user->profile_field_external_user_pending = false;
    $user->profile_field_external_user_comment = $comment;
    profile_save_data($user);
    //send(array($user), get_string('rejection_subject', 'local_external_users'), $comment);
    send_message_to_user($userid,
        get_config("local_external_users", "mailrejectionsubject"),
        get_config("local_external_users", "mailrejectionmessage"),
        get_string('rejection_control_additional_comment',
            'local_external_users') .
        ": " . $comment);
    if ($action == 1) {
        \user_delete_user($user);
    }
}


function is_external_user($userid)
{
    global $DB;
    if (!$DB->record_exists("user", array("id" => $userid))) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    profile_load_data($user);
    return $user->profile_field_external_user;
}

function storeFileToDB($mform, $user, $fileElement, $isPDF)
{
    global $DB;
    $name = $mform->get_new_filename($fileElement);
    $filecontent = $mform->get_file_content($fileElement);

    if ($isPDF && !valid_pdf($filecontent)) {
        return false;
    }
    $rec = $mform->save_stored_file($fileElement,
        context_system::instance()->id,
        'local_external_users',
        $fileElement,
        $user->id,
        '/',
        $name,
        true);

    $file = array('contextid' => context_system::instance()->id,
        'component' => 'local_external_users',
        'filearea' => $fileElement,
        'filepath' => '/',
        'userid' => $user->id,
        'filename' => $name);

    $DB->insert_record('local_external_users_files', $file);
    return true;
}

function get_user_file_table($userid) {
    global $OUTPUT;
    $userfiles = get_user_files($userid);
    $data = array();

    foreach ($userfiles as $file) {
        $actionurl = \moodle_url::make_pluginfile_url($file->contextid,
            $file->component, $file->filearea,
            $file->userid, $file->filepath, $file->filename, false);
        $data[] = \html_writer::link($actionurl, $file->filename);
    }

    $content = array('data' => $data);
    return $OUTPUT->render_from_template("local_external_users/profilefiles", $content);
}




