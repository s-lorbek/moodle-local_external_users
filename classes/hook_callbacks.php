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

/**
 * Class hook_callbacks
 *
 * @package    local_external_users
 * @copyright  2025 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Core hook to intercept page requests and redirect users to the verification page if needed.
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function onload(\core\hook\output\before_http_headers $hook): void {
        global $PAGE, $USER, $DB;
        $context = context_system::instance();

        $query = "SELECT data FROM {user_info_data} u INNER JOIN {user_info_field} f ON (u.fieldid = f.id) " .
            "WHERE u.userid = :userid AND f.shortname = :field";

        $params = ['userid' => $USER->id, 'field' => 'external_user'];

        $external = 0;
        if ($DB->record_exists_sql($query, $params)) {
            $external = boolval($DB->get_fieldset_sql($query, $params)[0]);
        }

        if (!$external) {
            return;
        }
        $externalverified = false;
        $params = ['userid' => $USER->id, 'field' => 'external_user_verified'];
        if ($DB->record_exists_sql($query, $params)) {
            $externalverified = ($DB->get_fieldset_sql($query, $params)[0]);
        }

        if (
            isloggedin()
            && (get_config('core', 'sitepolicyhandler') == "tool_policy" && !$USER->policyagreed)
        ) {
            return;
        }

        $limited = DateTime::createFromFormat('d.m.Y', $externalverified);
        $url = new moodle_url('/local/external_users/views/verification.php');

        if (check_redirect_excludes($PAGE->url)) {
            return;
        }

        if ($external && !strpos($PAGE->url, "verification.php")) {
            if ($limited !== false) {
                $currentdate = new DateTime();
                if ($limited < $currentdate) {
                    redirect(
                        $url,
                        get_string('verify_redirect', 'local_external_users'),
                        10
                    );
                    return;
                }
            } else if ($externalverified != 1) {
                redirect(
                    $url,
                    get_string('verify_redirect', 'local_external_users'),
                    10
                );
                return;
            }
        }

        if (
            strpos($PAGE->url, "/user/profile.php")
            && has_capability('local/external_users:manage', $context)
        ) {
            global $OUTPUT;
            $userid = optional_param('id', "-1", PARAM_INT);
            $userfiles = $DB->get_records("local_external_users_files", ['userid' => $userid]);

            $data = [];
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
                $data[] = \html_writer::link($actionurl, $file->filename);
            }

            $comment = $DB->get_record_sql(
                "SELECT uid.data " .
                    "FROM {user_info_data} uid " .
                    "INNER JOIN {user_info_field} uif ON (uid.fieldid = uif.id) " .
                    "WHERE uid.userid = :userid AND uif.shortname LIKE 'external_user_comment'",
                ["userid" => $userid]
            );

            $comment = $comment->data ?? null;
            $content = ['data' => $data,
                'sectiontitle' => get_string(
                    "pluginname",
                    "local_external_users"
                ) . " " . get_string(
                    "files",
                    "local_external_users"
                ),
                'comment' => $comment];

            $filetable = $OUTPUT->render_from_template("local_external_users/profilefiles", $content);

            if ($userid != '-1') {
                $PAGE->requires->js_call_amd(
                    'local_external_users/profilefiles',
                    "append",
                    [$filetable]
                );
            }
        }
    }
}