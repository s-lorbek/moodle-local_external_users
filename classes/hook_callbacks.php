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

namespace local_external_users;

use local_external_users\common;
use context_system;
use moodle_url;
use DateTime;
use core\output\html_writer;

/**
 * Class hook_callbacks
 *
 * @package    local_external_users
 * @copyright  2025 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks
{
    /**
     * Core hook to intercept page requests and redirect users to the verification page if needed.
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function onload(\core\hook\output\before_http_headers $hook): void
    {
        global $PAGE;

        if (!isloggedin()) {
            return;
        }

        if (get_user_preferences('auth_forcepasswordchange') && !\core\session\manager::is_loggedinas()) {
            return;
        }

        if (static::check_policy_agreements()) {
            return;
        }

        $externalstatus = static::is_external_user();
        if ($externalstatus->isexternal) {
            $common = new common();
            if (
                $common->check_redirect_excludes($PAGE->url) ||
                strpos($PAGE->url->out_as_local_url(), '/local/external_users/verify.php') !== false
            ) {
                return;
            }
            $allowbrowsing = get_config("local_external_users", "allowbrowsing");
            $ispending = $externalstatus->ispending;
            if ($allowbrowsing && $ispending) {
                return;
            }
            if (static::redirect_if_unverified($externalstatus)) {
                return;
            }
        }

        static::display_user_files($externalstatus->isexternal);
    }

    /**
     * Checks if the current user is marked as an external user and gets their verification status.
     *
     * @return object { isexternal: bool, verified_status: string|null }
     */
    protected static function is_external_user(): object
    {
        global $USER, $DB;

        $data = (object)['isexternal' => false, 'verified_status' => null, 'ispending' => false];
        $sql = "SELECT f.id, f.shortname, d.data
            FROM {user_info_field} f
            JOIN {user_info_data} d ON d.fieldid = f.id
            WHERE d.userid = :userid AND f.shortname IN ('external_user', 'external_user_verified', 'external_user_pending')";

        $rs = $DB->get_records_sql($sql, ['userid' => $USER->id]);
        $fields = [];
        foreach ($rs as $item) {
            $fields[$item->shortname] = $item->data;
        }

        if (isset($fields['external_user'])) {
            $data->isexternal = (bool)$fields['external_user'];
        }
        if (isset($fields['external_user_verified'])) {
            $data->verified_status = $fields['external_user_verified'];
        }
        if (isset($fields['external_user_pending'])) {
            $data->ispending = (bool)$fields['external_user_pending'];
        }

        return $data;
    }

    /**
     * Checks if the user needs to agree to site policies and returns true if redirection should be blocked.
     *
     * @return bool
     */
    protected static function check_policy_agreements(): bool
    {
        global $USER, $DB;

        if (get_config('core', 'sitepolicyhandler') == "tool_policy" && !$USER->policyagreed) {
            $activepolicies = $DB->get_records('tool_policy_versions', ['archived' => 0, 'optional' => 0]);
            if (count($activepolicies) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Redirects the unverified external user to the verification page based on status or expiry date.
     *
     * @param string|null $externalverified The verification status field value.
     * @return bool True if a redirection occurred.
     */
    protected static function redirect_if_unverified(?object $externalstatus): bool
    {
        global $USER;

        $url = new \moodle_url('/local/external_users/verify.php');
        $redirectmessage = get_string('verify_redirect', 'local_external_users');

        if (!empty($externalstatus->verified_status) && strlen($externalstatus->verified_status) > 2) {
            $limited = \DateTime::createFromFormat('!d.m.Y', $externalstatus->verified_status);
            if ($limited instanceof \DateTime && $limited >= new \DateTime('today')) {
                return false;
            }
        }

        if ($externalstatus->verified_status === '1') {
            return false;
        }

        if (get_config("local_external_users", "allowbrowsing") && $externalstatus->ispending) {
            $tariff = get_config("local_external_users", "allowbrowsing_tariff");
            if (!isset($USER->profile['eduPersonScopedAffiliation']) || $USER->profile['eduPersonScopedAffiliation'] !== $tariff) {
                $userrecord = get_complete_user_data('id', $USER->id);
                if ($userrecord) {
                    $userrecord->profile_field_eduPersonScopedAffiliation = $tariff;
                    $userrecord->profile_field_external_user_comment = "browsing";
                    profile_save_data($userrecord);
                }
            }
            return false;
        }

        redirect($url, $redirectmessage, 10);
        return true;
    }



    /**
     * Injects the external user files and comments table onto the user profile page for admins.
     *
     * @param bool $isexternal True if the currently viewed user is an external user.
     */
    protected static function display_user_files(bool $isexternal): void
    {
        global $PAGE, $DB, $OUTPUT;

        if (!str_contains($PAGE->url->out(), "/user/profile.php")) {
            return;
        }

        $context = context_system::instance();
        if (!has_capability('local/external_users:manage', $context)) {
            return;
        }

        $userid = optional_param('id', -1, PARAM_INT);
        if ($userid <= 0) {
            return;
        }

        $data = [];
        $userfiles = $DB->get_records("local_external_users_files", ['userid' => $userid]);
        foreach ($userfiles as $file) {
            $actionurl = \moodle_url::make_pluginfile_url(
                $file->contextid,
                $file->component,
                $file->filearea,
                $file->userid,
                $file->filepath,
                $file->filename,
                false
            );
            $data[] = html_writer::link($actionurl, $file->filename);
        }

        $comment = $DB->get_field_sql(
            "SELECT uid.data " .
                "FROM {user_info_data} uid " .
                "INNER JOIN {user_info_field} uif ON (uid.fieldid = uif.id) " .
                "WHERE uid.userid = :userid AND uif.shortname = 'external_user_comment'",
            ["userid" => $userid]
        );

        $content = [
            'data' => $data,
            'sectiontitle' => get_string("pluginname", "local_external_users") . " " . get_string("files", "local_external_users"),
            'comment' => $comment,
        ];

        $filetable = $OUTPUT->render_from_template("local_external_users/profilefiles", $content);

        $PAGE->requires->js_call_amd('local_external_users/profilefiles', "append", [$filetable]);
    }
}
