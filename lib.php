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

use local_external_users\common;

/**
 *
 * @throws dml_exception
 * @throws required_capability_exception
 * @throws coding_exception
 * @throws moodle_exception
 * @throws \dml_exception
 * @copyright  2022 Stephan Lorbek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    local_external_users
 */

/**
 * Serves the local_external_users plugin files.
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return int|bool false if file not found, does not return if found - just send the file
 *
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

/**
 * parse_string_to_array
 *
 * Cleans string and constructs array from input
 * @param string $string
 * @return array
 */
function parse_string_to_array($string): array {
    $string = preg_replace('/\s+/', '', $string);
    return explode(',', $string);
}

/**
 * Checks if the given URL should be excluded from redirection.
 *
 * @param string $url The URL to check.
 * @return bool True if the URL should be excluded, false otherwise.
 * @throws dml_exception
 */
function check_redirect_excludes(string $url): bool {
    $excludes = parse_string_to_array(get_config("local_external_users", "redirect_excludes"));
    foreach ($excludes as $exclude) {
        if (str_contains($url, $exclude)) {
            return true;
        }
    }
    return false;
}
