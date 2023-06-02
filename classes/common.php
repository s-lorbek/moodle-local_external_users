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

use DateTime;

defined('MOODLE_INTERNAL') || die();

require_once('mail.php');
require_once('message.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

function get_user_files($filearea) {
    global $DB, $USER;
    return $DB->get_records_sql('SELECT id FROM {local_external_users_files} ' .
    'WHERE userid = :userid AND ' . $DB->sql_compare_text('filearea') . " LIKE '$filearea'", array('userid' => $USER->id));
}

function get_users_for_verification() {
    global $DB;
    $dataset = $DB->get_records("user", array("auth" => "external", "deleted" => 0));
    $resultset = array();
    foreach($dataset as $user) {
        profile_load_data($user);
        if($user->profile_field_external_user_verified == '0') {
            $resultset[] = $user;
        }
    }
    return $resultset;
}

function get_users_already_verified() {
    global $DB;
    $dataset = $DB->get_records("user", array("auth" => "external", "deleted" => 0));
    $resultset = array();
    foreach($dataset as $user) {
        profile_load_data($user);
        if($user->profile_field_external_user_verified != '0' && $user->profile_field_external_user_verified != '-1') {
            $resultset[] = $user;
        }
    }
    return $resultset;
}

function get_users_rejected() {
    global $DB;
    $dataset = $DB->get_records("user", array("auth" => "external", "deleted" => 0));
    $resultset = array();
    foreach($dataset as $user) {
        profile_load_data($user);
        if($user->profile_field_external_user_verified == '-1') {
            $resultset[] = $user;
        }
    }
    return $resultset;
}

function valid_pdf($file) {
    return preg_match("/^%PDF-/", $file);
}

function is_user_verified($userid) {
    global $DB;
    if (!$DB->record_exists("user", array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    profile_load_data($user);
    return intval($user->profile_field_external_user_verified);
}

function verify_user($userid, $tariff) {
    global $DB;
    if (!$DB->record_exists("user", array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    profile_load_data($user);
    $user->profile_field_external_user_verified = 1;
    $user->profile_field_external_user_pending = false;
    $user->profile_field_eduPersonScopedAffiliation = $tariff;
    profile_save_data($user);
}

function getEndOfSemester() {
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

function limited_verify_user($userid, $tariff) {
    global $DB;
    if (!$DB->record_exists("user", array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    profile_load_data($user);
    $user->profile_field_external_user_verified = getEndOfSemester();
    $user->profile_field_external_user_pending = false;
    $user->profile_field_eduPersonScopedAffiliation = 'external';
    profile_save_data($user);
}


function revoke_user($userid) {
    global $DB;
    if (!$DB->record_exists("user", array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    $user->profile_field_external_user_verified = 0;
    $user->profile_field_external_user_pending = true;
    $user->profile_field_eduPersonScopedAffiliation = "";
    send_message($userid, null, "");
    profile_save_data($user);
}

function reject_user($userid, $action, $comment) {
    global $DB;
    if (!$DB->record_exists("user", array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    $user->profile_field_external_user = 1;
    $user->profile_field_external_user_verified = -1;
    $user->profile_field_external_user_pending = false;
    $user->profile_field_external_user_comment = $comment;
    profile_save_data($user);
    //send(array($user), get_string('rejection_subject', 'local_external_users'), $comment);
    send_message($userid, null, $comment);
    if ($action == 1) {
        user_delete_user($user);
    }
}


function is_external_user($userid) {
    global $DB;
    if (!$DB->record_exists("user", array("id" => $userid))) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    return $user->profile_field_external_user;
}

function storeFileToDB($mform, $user, $fileElement, $isPDF){
    global $DB;
    $name = $mform->get_new_filename($fileElement);
    $filecontent = $mform->get_file_content($fileElement);

    if($isPDF && !valid_pdf($filecontent)){
        return false;
    }
    $rec = $mform->save_stored_file($fileElement,
        \context_system::instance()->id,
        'local_external_users',
        'userfile',
        $user->id,
        '/',
        $name,
        true);

    $file = array('contextid' => \context_system::instance()->id,
        'component' => 'local_external_users',
        'filearea' => 'userfile',
        'filepath' => '/',
        'userid' => $user->id,
        'filename' => $name);

    $DB->insert_record('local_external_users_files', $file);
    return true;
}




