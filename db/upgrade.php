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
 * Upgrade script for local_external_users plugin.
 *
 * @package    local_external_users
 * @copyright  2025 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute upgrade steps for local_external_users.
 *
 * @param int $oldversion Old plugin version.
 * @return bool Always true.
 * @throws ddl_exception
 * @throws upgrade_exception
 * @throws downgrade_exception
 * @throws dml_exception
 */
function xmldb_local_external_users_upgrade($oldversion) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/db/upgradelib.php');

    $dbmanager = $DB->get_manager();

    if ($oldversion < 2022030115) {
        $table = new xmldb_table('local_external_users_files');

        $fields = [
            new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null),
            new xmldb_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
            new xmldb_field('component', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null),
            new xmldb_field('filearea', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null),
            new xmldb_field('filepath', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null),
            new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
            new xmldb_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null),
        ];

        foreach ($fields as $field) {
            if (!$dbmanager->field_exists($table, $field)) {
                $dbmanager->add_field($table, $field);
            }
        }

        $index = new xmldb_index('primary', XMLDB_INDEX_NOTUNIQUE, ['id']);
        if (!$dbmanager->index_exists($table, $index)) {
            $dbmanager->add_index($table, $index);
        }

        if (!$dbmanager->table_exists($table)) {
            $dbmanager->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2022030115, 'local', 'external_users');
    }

    if ($oldversion < 2024020900) {
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
        if (!$DB->record_exists('user_info_field', ['shortname' => $affiliationfield['shortname']])) {
            $DB->insert_record('user_info_field', (object)$affiliationfield);
        }

        upgrade_plugin_savepoint(true, 2024020900, 'local', 'external_users');
    }

    return true;
}
