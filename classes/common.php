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

require_once('mail.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

function get_external_user_query() {
    return "SELECT ud.* FROM {user_info_data} u INNER JOIN {user_info_field} f ON (u.fieldid = f.id) ".
    "INNER JOIN {user} ud ON(u.userid = ud.id) ".
    "WHERE f.shortname = :verifiedfield ".
        "AND data LIKE :flag ".
        "AND ud.id IN (SELECT ui.userid ".
        "FROM {user_info_data} ui ".
        "INNER JOIN {user_info_field} muif ON(ui.fieldid  = muif.id) ".
        "WHERE muif.shortname LIKE :externalfield AND data LIKE '1')";
}

function get_user_files($filearea) {
    global $DB, $USER;
    return $DB->get_records_sql('SELECT id FROM {local_external_users_files} ' .
    'WHERE userid = :userid AND ' . $DB->sql_compare_text('filearea') . " LIKE '$filearea'", array('userid' => $USER->id));
}

function get_users_for_verification() {
    global $DB;
    $params = array('verifiedfield' => 'external_user_verified', 'externalfield' => 'external_user', 'flag' => '0');
    return $DB->get_records_sql(get_external_user_query(), $params);
}

function get_users_already_verified() {
    global $DB;
    $params = array('verifiedfield' => 'external_user_verified', 'externalfield' => 'external_user', 'flag' => '1');
    return $DB->get_records_sql(get_external_user_query(), $params);
}

function valid_pdf($file) {
    return preg_match("/^%PDF-/", $file);
}

function is_user_verified($userid) {
    global $DB;
    if(!$DB->record_exists("user", array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    profile_load_data($user);
    return intval($user->profile_field_external_user_verified);
}

function verify_user($userid, $tariff) {
    global $DB;
    if(!$DB->record_exists("user", array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    profile_load_data($user);
    $user->profile_field_external_user_verified = 1;
    $user->profile_field_eduPersonScopedAffiliation = $tariff;
    profile_save_data($user);
}

function revoke_user($userid) {
    global $DB;
    if(!$DB->record_exists("user", array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    $user->profile_field_external_user_verified = 0;
    $user->profile_field_eduPersonScopedAffiliation = "";
    profile_save_data($user);
}

function reject_user($userid, $action, $comment) {
    global $DB;
    if(!$DB->record_exists("user", array("id" => $userid)) && !is_external_user($userid)) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    $user->profile_field_external_user = 1;
    $user->profile_field_external_user_verified = 0;
    $user->profile_field_external_user_comment = $comment;
    profile_save_data($user);
    send(array($user), get_string('rejection_subject', 'local_external_users'), $comment);
    if($action == 1) {
        user_delete_user($user);
    }
}


function is_external_user($userid) {
    global $DB;
    if(!$DB->record_exists("user", array("id" => $userid))) {
        return -1;
    }
    $user = $DB->get_record("user", array("id" => $userid));
    return $user->profile_field_external_user;
}
