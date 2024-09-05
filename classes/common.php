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

use coding_exception;
use context_system;
use DateTime;
use dml_exception;
use html_writer;
use local_external_users\event\user_approved;
use local_external_users\event\user_deactivated;
use local_external_users\event\user_limitedapproved;
use local_external_users\event\user_rejected;
use local_external_users\event\user_revoked;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once('mail.php');
require_once('message.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

class common {
    /**
     * @throws dml_exception
     */
    public function get_user_files($userid): array {
        global $DB;
        return $DB->get_records(
            "local_external_users_files",
            ['userid' => $userid]
        );
    }

    /**
     * @throws dml_exception
     */
    public function get_users_for_verification(): array {
        global $DB;
        $dataset = $DB->get_records_sql("SELECT u.id, u.username, u.firstname, u.lastname, muid.data
    FROM {user} u JOIN {user_info_data} muid ON (u.id = muid.userid)
    WHERE u.auth = 'external' AND u.deleted = 0 AND muid.fieldid =
    (SELECT id FROM {user_info_field} WHERE shortname = 'external_user_verified') and muid.data = '0'");

        $datasetpending = self::get_pending_users_for_verification();
        foreach ($datasetpending as $key => $value) {
            if (array_key_exists($key, $dataset)) {
                unset($dataset[$key]);
            }
        }
        return $dataset;
    }

    /**
     * @throws dml_exception
     */
    public function get_pending_users_for_verification(): array {
        global $DB;
        return $DB->get_records_sql("SELECT u.id, u.username, u.firstname, u.lastname, muid.data
    FROM {user} u JOIN {user_info_data} muid ON (u.id = muid.userid)
    WHERE u.auth = 'external' AND u.deleted = 0 AND muid.fieldid =
    (SELECT id FROM {user_info_field} WHERE shortname = 'external_user_pending') and muid.data = '1'");
    }

    /**
     * @throws dml_exception
     */
    public function get_users_already_verified(): array {
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
            if (array_key_exists($key, $datasetlimited)) {
                $value->username .= " (L)";
            }
        }

        return $dataset;
    }

    /**
     * @throws dml_exception
     */
    public function get_users_rejected(): array {
        global $DB;
        return $DB->get_records_sql("SELECT u.id, u.username, u.firstname, u.lastname, muid.data
    FROM {user} u JOIN {user_info_data} muid ON (u.id = muid.userid)
    WHERE u.auth = 'external' AND u.deleted = 0 AND muid.fieldid =
    (SELECT id FROM {user_info_field} WHERE shortname = 'external_user_verified') and muid.data = '-1'");
    }

    public function valid_pdf($file) {
        return preg_match("/^%PDF-/", $file);
    }

    /**
     * @throws dml_exception
     */
    public function is_user_verified($userid): int {
        global $DB;
        if (
            !$DB->record_exists(
                "user",
                ["id" => $userid]
            ) && !self::is_external_user($userid)
        ) {
            return -1;
        }
        $user = $DB->get_record("user", ["id" => $userid]);
        profile_load_data($user);
        return intval($user->profile_field_external_user_verified);
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function verify_user($userid, $tariff): int {
        global $DB, $USER, $PAGE;
        if (
            !$DB->record_exists(
                "user",
                ["id" => $userid]
            ) && !self::is_external_user($userid)
        ) {
            return -1;
        }
        $user = $DB->get_record("user", ["id" => $userid]);
        profile_load_data($user);

        $event = user_approved::create([
            'relateduserid' => $userid,
            'context' => $PAGE->context,
            'objectid' => $USER->id,
            'other' => [
                'oldstatus' => $user->profile_field_external_user_verified,
                'userid' => $userid,
            ],
        ]);
        $event->trigger();

        $user->profile_field_external_user_verified = 1;
        $user->profile_field_external_user_pending = false;
        $user->profile_field_eduPersonScopedAffiliation = $tariff;
        $user->profile_field_external_user_comment =
            get_config("local_external_users", "discounturl");

        profile_save_data($user);

        send_message_to_user(
            $userid,
            get_config("local_external_users", "mailverificationsubject"),
            get_config("local_external_users", "mailverificationmessage"),
            ""
        );

        self::remove_user_files($userid);
        return 0;
    }

    /**
     * @throws dml_exception
     */
    private function remove_user_files($userid): void {
        global $DB;
        $fs = get_file_storage();
        $userfiles = self::get_user_files($userid);
        foreach ($userfiles as $file) {
            $fileitem = $fs->get_file(
                $file->contextid,
                $file->component,
                $file->filearea,
                $file->userid,
                $file->filepath,
                $file->filename
            );

            if ($fileitem) {
                $fileitem->delete();
                $DB->delete_records("local_external_users_files", ['userid' => $userid,
                    'id' => $file->id]);
            }
        }
    }

    /**
     * @throws dml_exception
     */
    public function getendofsemester() {
        $today = new DateTime();
        $currentmonth = (int)$today->format('n');

        if (!empty(get_config("local_external_users", "endofterm"))) {
            return get_config("local_external_users", "endofterm");
        }

        if ($currentmonth >= 3 && $currentmonth <= 9) {
            return $today->format("t.09.Y");
        } else {
            $nextyear = $today->format('Y') + 1;
            return "28.02.$nextyear";
        }
    }

    /**
     * @throws dml_exception
     */
    public function getendofnextsemester() {
        $today = new DateTime();

        $currentmonth = (int)$today->format('n');
        $currentyear = $today->format('Y');
        $nextyear = $currentyear + 1;

        if (!empty(get_config("local_external_users", "endofnextterm"))) {
            return get_config("local_external_users", "endofnextterm");
        }

        if ($currentmonth >= 3 && $currentmonth <= 9) {
            return "28.02.$nextyear";
        } else {
            return "30.09.$nextyear";
        }
    }


    /**
     * @throws dml_exception
     */
    public function getaffiliationoptions(): array {
        $liststring = get_config("local_external_users", "affiliations");
        return explode(',', $liststring);
    }

    /**
     * @throws dml_exception
     */
    public function setaffiliation($userid, $affiliation): bool {
        global $DB;
        $user = $DB->get_record("user", ["id" => $userid]);
        profile_load_data($user);
        $user->profile_field_external_user_affiliation = $affiliation;
        profile_save_data($user);
        return ($affiliation == $user->profile_field_external_user_affiliation);
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function limited_verify_user($userid, $tariff, $type): int {
        global $DB, $PAGE, $USER;
        if (
            !$DB->record_exists(
                "user",
                ["id" => $userid]
            ) && !self::is_external_user($userid)
        ) {
            return -1;
        }
        $user = $DB->get_record("user", ["id" => $userid]);
        profile_load_data($user);

        $limited = "";
        if ($type == "limited2") {
            $limited = self::getEndOfNextSemester();
        } else {
            $limited = self::getEndOfSemester();
        }

        $event = user_limitedapproved::create([
            'relateduserid' => $userid,
            'context' => $PAGE->context,
            'objectid' => $USER->id,
            'other' => [
                'oldstatus' => $user->profile_field_external_user_verified,
                'newstatus' => $limited . " - " . $tariff,
                'userid' => $userid,
            ],
        ]);
        $event->trigger();

        $user->profile_field_external_user_verified = $limited;

        $user->profile_field_external_user_pending = false;
        $user->profile_field_eduPersonScopedAffiliation = $tariff;
        $user->profile_field_external_user_comment =
            get_config("local_external_users", "discounturl");
        profile_save_data($user);

        $messageid = send_message_to_user(
            $userid,
            get_config("local_external_users", "mailverificationsubject"),
            get_config("local_external_users", "mailverificationmessage"),
            ""
        );
        return 0;
    }


    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function revoke_user($userid): int {
        global $DB, $USER, $PAGE;
        if (
            !$DB->record_exists(
                "user",
                ["id" => $userid]
            ) && !self::is_external_user($userid)
        ) {
            return -1;
        }
        $user = $DB->get_record("user", ["id" => $userid]);
        profile_load_data($user);

        $event = user_revoked::create([
            'relateduserid' => $userid,
            'context' => $PAGE->context,
            'objectid' => $USER->id,
            'other' => [
                'oldstatus' => $user->profile_field_external_user_verified,
                'userid' => $userid,
            ],
        ]);
        $event->trigger();

        $user->profile_field_external_user_verified = 0;
        $user->profile_field_external_user_pending = true;
        $user->profile_field_eduPersonScopedAffiliation = "";
        profile_save_data($user);
        return 0;
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function reject_user($userid, $action, $comment): int {
        global $DB, $USER, $PAGE;
        if (
            !$DB->record_exists(
                "user",
                ["id" => $userid]
            ) && !self::is_external_user($userid)
        ) {
            return -1;
        }
        $user = $DB->get_record("user", ["id" => $userid]);
        profile_load_data($user);

        $user->profile_field_external_user = 1;
        $user->profile_field_external_user_verified = -1;
        $user->profile_field_external_user_pending = false;
        $user->profile_field_external_user_comment = $comment;

        profile_save_data($user);
        $messageid = send_message_to_user(
            $userid,
            get_config("local_external_users", "mailrejectionsubject"),
            get_config("local_external_users", "mailrejectionmessage"),
            get_string(
                'rejection_control_additional_comment',
                'local_external_users'
            ) .
            ": " . $comment
        );

        $event = user_rejected::create([
            'relateduserid' => $userid,
            'context' => $PAGE->context,
            'objectid' => $USER->id,
            'other' => [
            'oldstatus' => $user->profile_field_external_user_verified,
            'userid' => $userid,
            'messageid' => $messageid,
            ],
        ]);
        $event->trigger();

        if ($action == 1) {
            user_delete_user($user);
            $deactivatedevent = user_deactivated::create([
                'relateduserid' => $userid,
                'context' => $PAGE->context,
                'objectid' => $USER->id,
                'other' => [
                    'oldstatus' => $user->profile_field_external_user_verified,
                    'userid' => $userid,
                    'messageid' => $messageid,
                ],
            ]);
            $deactivatedevent->trigger();
        }
        return 0;
    }


    /**
     * @throws dml_exception
     */
    public function is_external_user($userid) {
        global $DB;
        if (!$DB->record_exists("user", ["id" => $userid])) {
            return -1;
        }
        $user = $DB->get_record("user", ["id" => $userid]);
        profile_load_data($user);
        return $user->profile_field_external_user;
    }

    /**
     * @throws dml_exception
     */
    public function storefiletodb($mform, $user, $fileelement, $ispdf, $mandatory): bool {
        global $DB;

        if (!$mandatory) {
            return true;
        }

        $name = $mform->get_new_filename($fileelement);
        $filecontent = $mform->get_file_content($fileelement);

        if ($ispdf && !self::valid_pdf($filecontent)) {
            return false;
        }
        $mform->save_stored_file(
            $fileelement,
            context_system::instance()->id,
            'local_external_users',
            $fileelement,
            $user->id,
            '/',
            $name,
            true
        );

        $file = ['contextid' => context_system::instance()->id,
            'component' => 'local_external_users',
            'filearea' => $fileelement,
            'filepath' => '/',
            'userid' => $user->id,
            'filename' => $name];

        $DB->insert_record('local_external_users_files', $file);
        return true;
    }

    /**
     * @throws moodle_exception
     */
    public function get_user_file_table($userid): array {
        $userfiles = self::get_user_files($userid);
        $data = [];

        foreach ($userfiles as $file) {
            $actionurl = \moodle_url::make_pluginfile_url(
                $file->contextid,
                $file->component,
                $file->filearea,
                $file->userid,
                $file->filepath,
                $file->filename
            );
            $data[] = html_writer::link($actionurl, $file->filename);
        }
        return $data;
    }

    public function create_dummy_user($fullname, $email): stdClass {
        $user = new stdClass();
        $user->email = $email;
        $user->firstname = $fullname;
        $user->lastname = '';
        $user->maildisplay = true;
        $user->mailformat = 0; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
        $user->id = -99;
        $user->firstnamephonetic = '';
        $user->lastnamephonetic = '';
        $user->middlename = '';
        $user->alternatename = '';
        return $user;
    }

    public function parse_string_to_array($string): array {
        $string = preg_replace('/\s+/', '', $string);
        return explode(',', $string);
    }
}
