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
require('../../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');
require_once('../classes/verification_form.php');
require_once('../classes/common.php');

$context = \context_system::instance();
$PAGE->set_context($context);

$pageurl = new \moodle_url('/local/external_users/views/manage.php');
$PAGE->set_url($pageurl);

$PAGE->set_title(get_string('pluginname', 'local_external_users'));
$PAGE->set_heading(get_string('pluginname', 'local_external_users'));
$PAGE->set_pagelayout('standard');
require_capability('local/external_users:manage', $context);

echo $OUTPUT->header();

echo \html_writer::tag("h3", get_string('pending_header', 'local_external_users'));
$externalusers = get_users_for_verification();

$tableheaders = array(get_string('username', 'local_external_users'),
get_string('firstname', 'local_external_users'),
get_string('lastname', 'local_external_users'),
get_string('manage', 'local_external_users'));

$pendingtable = new \html_table();
$pendingtable->attributes['class'] = 'table table-striped';
$pendingtable->head = $tableheaders;
$pendingtable->data = array();

foreach ($externalusers as $user) {
    $actionurl = new \moodle_url("/local/external_users/views/profile.php", array('id' => $user->id));
    $pendingtable->data[] = array(format_string($user->username), format_string($user->firstname),
    format_string($user->lastname), \html_writer::link($actionurl, "Link"));
}
echo \html_writer::table($pendingtable);

echo \html_writer::tag("h3", get_string('already_verified_header', 'local_external_users'));

$verifiedusers = get_users_already_verified();

$approvedtable = new \html_table();
$approvedtable->attributes['class'] = 'table table-striped';
$approvedtable->head = $tableheaders;
$approvedtable->data = array();

foreach ($verifiedusers as $user) {
    $actionurl = new \moodle_url("/local/external_users/views/profile.php", array('id' => $user->id));
    $approvedtable->data[] = array(format_string($user->username), format_string($user->firstname),
    format_string($user->lastname), \html_writer::link($actionurl, "Link"));
}
echo \html_writer::table($approvedtable);
echo $OUTPUT->footer();
