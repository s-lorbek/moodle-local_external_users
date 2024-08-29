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
global $CFG;

use coding_exception;
use context_system;
use core_user;
use DateTime;
use dml_exception;
use local_external_users\event\user_submit;
use moodle_exception;
use moodle_url;
use stdClass;

require('../../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/datalib.php');
require_once('../classes/verification_form.php');
require_once('../classes/common.php');

class verification {
    private stdClass $user;
    private common $common;
    private verification_form $mform;
    private DateTime $currentdate;

    /**
     * @throws dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function __construct() {
        global $DB, $USER, $PAGE;

        $context = context_system::instance();
        $PAGE->set_context($context);

        $pageurl = new moodle_url('/local/external_users/views/verification.php');
        $PAGE->set_url($pageurl);

        $PAGE->set_title(get_string('pluginname', 'local_external_users'));
        $PAGE->set_heading(get_string('pluginname', 'local_external_users'));
        $PAGE->set_pagelayout('standard');

        $this->mform = new verification_form();
        $this->common = new common();
        $this->currentdate = new DateTime();
        $this->user = $DB->get_record("user", ["id" => $USER->id]);
        self::transform_data();
    }

    /**
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function transform_data(): void {
        profile_load_data($this->user);
        if (
            !$this->user->profile_field_external_user || ($this->user->profile_field_external_user_verified != 0 &&
                $this->user->profile_field_external_user_verified != -1)
        ) {
            $limited = DateTime::createFromFormat('d.m.Y', $this->user->profile_field_external_user_verified);
            if (!$limited || $limited > $this->currentdate) {
                redirect('/', get_string('redirect', 'local_external_users'), 0);
            }
        }
    }

    /**
     * @throws dml_exception
     * @throws moodle_exception
     * @throws coding_exception
     */
    public function render(): void {
        global $OUTPUT, $PAGE;
        echo $OUTPUT->header();
        if ($this->mform->is_cancelled()) {
            redirect('/', get_string('redirect', 'local_external_users'), 10);
        } else if ($this->mform->get_data()) {
            global $DB, $USER;

            // Clean previous files.
            $leftovers = $this->common->get_user_files($USER->id);
            if (count($leftovers)) {
                $DB->delete_records(
                    'local_external_users_files',
                    ['userid' => $this->user->id]
                );
            }

            if (
                $this->common->storeFileToDB(
                    $this->mform,
                    $USER,
                    'userfile',
                    true,
                    get_config("local_external_users", "required_document")
                ) &&
                $this->common->storeFileToDB(
                    $this->mform,
                    $USER,
                    'userfileimage',
                    false,
                    get_config("local_external_users", "required_photo")
                )
            ) {
                $this->user->profile_field_external_user_pending = true;
                $this->user->profile_field_external_user_verified = 0;

                profile_save_data($this->user);

                $event = user_submit::create([
                    'relateduserid' => $this->user->id,
                    'context' => $PAGE->context,
                    'objectid' => $this->user->id,
                    'other' => [
                        'userid' => $this->user->id,
                    ],
                ]);
                $event->trigger();

                echo $OUTPUT->notification(
                    get_string('success', 'local_external_users'),
                    'notifymessage'
                );

                send_message_to_user(
                    $USER->id,
                    get_config("local_external_users", "signupmailsubject"),
                    get_config("local_external_users", "signupmailmessage"),
                    ""
                );

                $reviewteam = $this->common->create_dummy_user(
                    "USI Team",
                    get_config("local_external_users", "submissionreviewemail")
                );
                $emailfrom = core_user::get_noreply_user();
                $message = get_string('reviewbody', 'local_external_users') . $this->user->username;
                email_to_user(
                    $reviewteam,
                    $emailfrom,
                    get_string('reviewsubject', 'local_external_users') . $this->user->username,
                    html_to_text($message),
                    $message,
                    null,
                    null
                );
                redirect(new moodle_url("/"));
            } else {
                echo $OUTPUT->notification(
                    get_string('pdf_error', 'local_external_users'),
                    'notifymessage'
                );
            }
        }
        $this->mform->add_action_buttons(
            $cancel = false,
            $submitlabel = get_string('form_submit', 'local_external_users')
        );
        if (
            property_exists($this->user, 'profile_field_external_user_comment') &&
            strlen($this->user->profile_field_external_user_comment)
        ) {
            echo $OUTPUT->notification(
                $this->user->profile_field_external_user_comment,
                'errormessage'
            );
        }
        if (!$this->user->profile_field_external_user_pending) {
            echo get_config('local_external_users', 'onboardingdescription');
            echo "<hr><br>";
            $this->mform->display();
        } else {
            echo get_string('pending_msg', 'local_external_users');
        }
        echo $OUTPUT->footer();
    }
}

$v = new verification();
$v->render();
