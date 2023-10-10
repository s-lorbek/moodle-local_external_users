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
    $dataset = $DB->get_records_sql("SELECT u.id, u.username, u.firstname, u.lastname, muid.data
    FROM {user} u JOIN {user_info_data} muid ON (u.id = muid.userid)
    WHERE u.auth = 'external' AND u.deleted = 0 AND muid.fieldid =
    (SELECT id FROM {user_info_field} WHERE shortname = 'external_user_verified') and muid.data = '0'");

    $datasetpending = $DB->get_records_sql("SELECT u.id
    FROM {user} u JOIN {user_info_data} muid ON (u.id = muid.userid)
    WHERE u.auth = 'external' AND u.deleted = 0 AND muid.fieldid =
    (SELECT id FROM {user_info_field} WHERE shortname = 'external_user_pending') and muid.data = '0'");

    foreach ($dataset as $key => $value) {
        if(array_key_exists($key, $datasetpending))
            $dataset[$key]->username .= ' <i class="fa fa-hourglass" aria-hidden="true"></i>';
    }
    return $dataset;
}

function get_users_already_verified()
{
    global $DB;
    $dataset = $DB->get_records_sql("SELECT u.id, u.username, u.firstname, u.lastname, muid.data
    FROM {user} u JOIN {user_info_data} muid ON (u.id = muid.userid)
    WHERE u.auth = 'external' AND u.deleted = 0 AND muid.fieldid =
    (SELECT id FROM {user_info_field} WHERE shortname = 'external_user_verified') and muid.data <> '0' and muid.data <> '-1'");

    $datasetlimited = $DB->get_records_sql("SELECT u.id, u.username, u.firstname, u.lastname, muid.data
    FROM {user} u JOIN {user_info_data} muid ON (u.id = muid.userid)
    WHERE u.auth = 'external' AND u.deleted = 0 AND muid.fieldid =
    (SELECT id FROM {user_info_field} WHERE shortname = 'external_user_verified') and muid.data <> '1'");

    foreach ($dataset as $key => $value) {
        if(array_key_exists($key, $datasetlimited))
            $dataset[$key]->username .= " (L)";
    }

    return $dataset;
}

function get_users_rejected()
{
    global $DB;
    $dataset = $DB->get_records_sql("SELECT u.id, u.username, u.firstname, u.lastname, muid.data
    FROM {user} u JOIN {user_info_data} muid ON (u.id = muid.userid)
    WHERE u.auth = 'external' AND u.deleted = 0 AND muid.fieldid =
    (SELECT id FROM {user_info_field} WHERE shortname = 'external_user_verified') and muid.data = '-1'");
    return $dataset;
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
    global $DB, $USER, $PAGE;
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
    $user->profile_field_external_user_comment =
        get_config("local_external_users", "discounturl");

    profile_save_data($user);
}

function getEndOfSemester()
{
    $today = new DateTime();
    $currentMonth = (int)$today->format('n');

    if (!empty(get_config("local_external_users", "endofterm"))) {
        return get_config("local_external_users", "endofterm");
    }

    if ($currentMonth >= 3 && $currentMonth <= 9) {
        return $today->format("t.09.Y");
    } else {
        $nextYear = $today->format('Y') + 1;
        return "28.02.$nextYear";
    }
}

function getEndOfNextSemester()
{
    $today = new DateTime();

    $currentMonth = (int)$today->format('n');
    $currentYear = $today->format('Y');
    $nextYear = $currentYear + 1;

    if (!empty(get_config("local_external_users", "endofnextterm"))) {
        return get_config("local_external_users", "endofnextterm");
    }

    if ($currentMonth >= 3 && $currentMonth <= 9) {
        return "28.02.$nextYear";
    } else {
        return "30.09.$nextYear";
    }
}

function limited_verify_user($userid, $tariff, $type)
{
    global $DB, $PAGE, $USER;
    if (!$DB->record_exists("user",
            array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    profile_load_data($user);

    $limited = "";
    if ($type == "limited2")
        $limited = getEndOfNextSemester();
    else
        $limited = getEndOfSemester();

    $event = user_limitedapproved::create(array(
        'relateduserid' => $userid,
        'context' => $PAGE->context,
        'objectid' => $USER->id,
        'other' => array(
            'oldstatus' => $user->profile_field_external_user_verified,
            'newstatus' => $limited . " - " . $tariff,
            'userid' => $userid,
        )
    ));
    $event->trigger();

    $user->profile_field_external_user_verified = $limited;

    $user->profile_field_external_user_pending = false;
    $user->profile_field_eduPersonScopedAffiliation = $tariff;
    $user->profile_field_external_user_comment =
        get_config("local_external_users", "discounturl");
    profile_save_data($user);
}


function revoke_user($userid)
{
    global $DB, $USER, $PAGE;
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
    $user->profile_field_external_user_pending = true;
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




