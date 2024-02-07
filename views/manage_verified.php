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

// @codingStandardsIgnoreStart
use context_system;
use html_table;
use html_writer;
use moodle_url;
use function get_string;

require('../../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/datalib.php');
require_once('../classes/verification_form.php');
require_once('../classes/common.php');

$context = context_system::instance();
$PAGE->set_context($context);

$pageurl = new moodle_url('/local/external_users/views/manage_verified.php');
$PAGE->set_url($pageurl);

$common = new common();

$PAGE->set_title(get_string('pluginname', 'local_external_users'));
$PAGE->set_heading(get_string('pluginname', 'local_external_users'));
$PAGE->set_pagelayout('standard');
require_capability('local/external_users:manage', $context);

echo $OUTPUT->header();

$dashboarddata = [];
$tableheaders = [get_string('username', 'local_external_users'),
    get_string('firstname', 'local_external_users'),
    get_string('lastname', 'local_external_users'),
    get_string('manage', 'local_external_users'),
    "Moodle Link"];

$verifiedusers = $common->get_users_already_verified();
$approvedtable = new html_table();
$approvedtable->head = $tableheaders;
$approvedtable->id = 'sortabletableapproved';

foreach ($verifiedusers as $user) {
    $actionurl = new moodle_url(
        "/local/external_users/views/profile.php",
        ['id' => $user->id]
    );
    $approvedtable->data[] = [
        html_writer::link($actionurl, format_string($user->username)),
        format_string($user->firstname),
        format_string($user->lastname),
        html_writer::link($actionurl, "Link"),
        html_writer::link(
            new moodle_url(
                "/user/profile.php",
                ["id" => $user->id]
            ),
            get_string("usericon", "local_external_users")
        )];
}
$dashboarddata["table"] = html_writer::table($approvedtable);
$dashboarddata["title"] = get_string('approved', 'local_external_users');
$dashboarddata["count"] = strval(count($verifiedusers));
$dashboarddata["table-id"] = $approvedtable->id;

echo $OUTPUT->render_from_template(
    "local_external_users/dashboard_table",
    $dashboarddata
);
echo $OUTPUT->footer();
