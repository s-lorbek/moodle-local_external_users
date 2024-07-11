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
 * @throws dml_exception
 * @throws required_capability_exception
 * @throws coding_exception
 * @throws moodle_exception
 * @copyright  2022 Stephan Lorbek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    local_external_users
 */

function local_external_users_before_http_headers() {
    global $PAGE, $USER, $DB;
    $context = context_system::instance();

    $query = "SELECT data FROM {user_info_data} u INNER JOIN {user_info_field} f ON (u.fieldid = f.id) " .
        "WHERE u.userid = :userid AND f.shortname = :field";

    $params = ['userid' => $USER->id, 'field' => 'external_user'];
    $user = $DB->get_record("user", ['id' => $USER->id]);

    $external = 0;
    if ($DB->record_exists_sql($query, $params)) {
        $external = boolval($DB->get_fieldset_sql($query, $params)[0]);
    }

    $externalverified = false;
    $params = ['userid' => $USER->id, 'field' => 'external_user_verified'];
    if ($DB->record_exists_sql($query, $params)) {
        $externalverified = ($DB->get_fieldset_sql($query, $params)[0]);
    }

    $limited = DateTime::createFromFormat('d.m.Y', $externalverified);
    $url = new moodle_url('/local/external_users/views/verification.php');

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

/**
 * @throws require_login_exception
 * @throws coding_exception
 * @throws moodle_exception
 */
function local_external_users_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
) {
    global $DB;

    require_login();
    $itemid = (int)array_shift($args);

    $fs = get_file_storage();

    $filename = array_pop($args);
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $file = $fs->get_file(
        $context->id,
        'local_external_users',
        $filearea,
        $itemid,
        $filepath,
        $filename
    );
    if (!$file) {
        return false;
    }

    send_stored_file(
        $file,
        0,
        0,
        false,
        $options
    );
    return 0;
}
