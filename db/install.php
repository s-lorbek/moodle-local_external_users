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
 * Installation callback for local_external_users.
 *
 * @package    local_external_users
 * @copyright  2022 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Perform installation operations for local_external_users.
 *
 * @throws dml_exception
 */
function xmldb_local_external_users_install(): void {
    global $DB;

    $externaluserfield = [
        'shortname' => 'external_user',
        'name' => 'Is External User',
        'description' => '',
        'datatype' => 'text',
        'descriptionformat' => FORMAT_HTML,
        'categoryid' => 1,
        'sortorder' => 1,
        'required' => 0,
        'locked' => 0,
        'dataformat' => 'plaintext',
    ];

    $verifiedfield = [
        'shortname' => 'external_user_verified',
        'name' => 'Verified',
        'description' => '',
        'datatype' => 'text',
        'descriptionformat' => FORMAT_HTML,
        'categoryid' => 1,
        'sortorder' => 1,
        'required' => 0,
        'locked' => 0,
        'dataformat' => 'plaintext',
    ];

    $commentfield = [
        'shortname' => 'external_user_comment',
        'name' => 'Comment',
        'description' => '',
        'datatype' => 'text',
        'descriptionformat' => FORMAT_HTML,
        'categoryid' => 1,
        'sortorder' => 1,
        'required' => 0,
        'locked' => 0,
        'dataformat' => 'plaintext',
    ];

    $pendingfield = [
        'shortname' => 'external_user_pending',
        'name' => 'Pending',
        'description' => '',
        'datatype' => 'text',
        'descriptionformat' => FORMAT_HTML,
        'categoryid' => 1,
        'defaultdata' => '',
        'sortorder' => 1,
        'required' => 0,
        'locked' => 0,
        'dataformat' => 'plaintext',
    ];

    $affiliationfield = [
        'shortname' => 'external_user_affiliation',
        'name' => 'Academic Affiliation',
        'description' => '',
        'datatype' => 'text',
        'descriptionformat' => FORMAT_HTML,
        'categoryid' => 1,
        'defaultdata' => '',
        'sortorder' => 1,
        'required' => 0,
        'locked' => 0,
        'dataformat' => 'plaintext',
    ];

    // Check if the field already exists.
    if (!$DB->record_exists('user_info_field', ['shortname' => $externaluserfield['shortname']])) {
        $DB->insert_record('user_info_field', (object)$externaluserfield);
    }
    if (!$DB->record_exists('user_info_field', ['shortname' => $verifiedfield['shortname']])) {
        $DB->insert_record('user_info_field', (object)$verifiedfield);
    }
    if (!$DB->record_exists('user_info_field', ['shortname' => $commentfield['shortname']])) {
        $DB->insert_record('user_info_field', (object)$commentfield);
    }
    if (!$DB->record_exists('user_info_field', ['shortname' => $pendingfield['shortname']])) {
        $DB->insert_record('user_info_field', (object)$pendingfield);
    }
    if (!$DB->record_exists('user_info_field', ['shortname' => $affiliationfield['shortname']])) {
        $DB->insert_record('user_info_field', (object)$affiliationfield);
    }
}
