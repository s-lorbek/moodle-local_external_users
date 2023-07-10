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
use local_external_users\event\user_submit;
use moodle_url;

require('../../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/datalib.php');
require_once('../classes/verification_form.php');
require_once('../classes/common.php');

$context = context_system::instance();
$PAGE->set_context($context);

$pageurl = new moodle_url('/local/external_users/views/verification.php');
$PAGE->set_url($pageurl);

$PAGE->set_title(get_string('pluginname', 'local_external_users'));
$PAGE->set_heading(get_string('pluginname', 'local_external_users'));
$PAGE->set_pagelayout('standard');
//require_capability('local/external_users:verification', $context);

$mform = new verification_form();
echo $OUTPUT->header();
global $DB;

$user = $DB->get_record("user", array("id" => $USER->id));
profile_load_data($user);

if (strlen($user->profile_field_external_user_comment["text"])) {
    echo $OUTPUT->notification(
        $user->profile_field_external_user_comment["text"],
        'errormessage');
}

if ($mform->is_cancelled()) {
    redirect('/', get_string('redirect', 'local_external_users'), 10);
} else if ($data = $mform->get_data()) {
    global $DB, $USER;

    //Clean previous files
    $leftovers = get_user_files($USER->id);
    if (count($leftovers)) {
        $DB->delete_records('local_external_users_files',
            array('userid' => $user->id));
    }

    if (storeFileToDB($mform, $USER, 'userfile', true) &&
        storeFileToDB($mform, $USER, 'userfileimage', false)) {
        $user->profile_field_external_user_pending = true;
        $user->profile_field_external_user_verified = 0;

        profile_save_data($user);

        $event = user_submit::create(array(
            'relateduserid' => $user->id,
            'context' => $PAGE->context,
            'objectid' => $user->id,
            'other' => array(
                'userid' => $user->id,
            )
        ));
        $event->trigger();

        echo $OUTPUT->notification(
            get_string('success', 'local_external_users'),
            'notifymessage');
    } else {
        echo $OUTPUT->notification(
            get_string('pdf_error', 'local_external_users'),
            'notifymessage');
    }
}
$mform->add_action_buttons($cancel = false,
    $submitlabel = get_string('form_submit', 'local_external_users'));

if (!$user->profile_field_external_user_pending) {
    echo get_config('local_external_users', 'onboardingdescription');
    echo "<hr><br>";
    $mform->display();
} else {
    echo get_string('pending_msg', 'local_external_users');
}
echo $OUTPUT->footer();
