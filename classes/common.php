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
    $query = "SELECT u.data FROM {user_info_data} u INNER JOIN {user_info_field} f ON (u.fieldid = f.id)
    WHERE f.shortname = :field AND u.userid = :userid";
    $params = array('field' => 'external_user_verified', 'userid' => $userid);
    return intval($DB->get_field_sql($query, $params));
}

function verify_user($userid) {
    global $DB;
    $value = 1;
    $query = "SELECT u.id FROM {user_info_data} u INNER JOIN {user_info_field} f ON (u.fieldid = f.id)
    WHERE f.shortname = :field AND u.data like '0' AND u.userid = :userid";
    $params = array('field' => 'external_user_verified', 'userid' => $userid);
    $fieldid = $DB->get_field_sql($query, $params);
    $DB->set_field("user_info_data", "data", $value, array("id" => $fieldid));
}

function revoke_user($userid) {
    global $DB;
    $value = 0;
    $query = "SELECT u.id FROM {user_info_data} u INNER JOIN {user_info_field} f ON (u.fieldid = f.id)
    WHERE f.shortname = :field AND u.data like '1' AND u.userid = :userid";
    $params = array('field' => 'external_user_verified', 'userid' => $userid);
    $fieldid = $DB->get_field_sql($query, $params);
    $DB->set_field("user_info_data", "data", $value, array("id" => $fieldid));
}
