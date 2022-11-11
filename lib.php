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
 * @package    local_external_users
 * @copyright  2022 Stephan Lorbek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function local_external_users_before_http_headers() {
    global $PAGE, $USER, $DB;
    $query = "SELECT data FROM {user_info_data} u INNER JOIN {user_info_field} f ON (u.fieldid = f.id) ".
    "WHERE u.userid = :userid AND f.shortname = :field";

    $params = array('userid' => $USER->id, 'field' => 'external_user');
    $external = 0;
    if ($DB->record_exists_sql($query, $params)) {
        $external = boolval($DB->get_fieldset_sql($query, $params)[0]);
    }

    $externalverified = false;
    $params = array('userid' => $USER->id, 'field' => 'external_user_verified');
    if ($DB->record_exists_sql($query, $params)) {
        $externalverified = boolval($DB->get_fieldset_sql($query, $params)[0]);
    }

    if ($external && !$externalverified && strpos($PAGE->url, "verification.php") == false) {
        $url = new \moodle_url('/local/external_users/views/verification.php');
        redirect($url, get_string('verify_redirect', 'local_external_users'), 10);
    }
}

function local_external_users_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB;

    // @codingStandardsIgnoreStart
    /*
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }
    */

    require_login();

    /*
    if ($filearea != 'attachment') {
      //  return false;
    }
    */

    $itemid = (int)array_shift($args);

    /*
    if ($itemid != 0) {
     //   return false;
    }
    */

    $fs = get_file_storage();

    $filename = array_pop($args);
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $args).'/';
    }

    $file = $fs->get_file($context->id, 'local_external_users', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
    // @codingStandardsIgnoreEnd
}

