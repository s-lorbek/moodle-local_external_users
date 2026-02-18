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

use stdClass;
use DateTime;
use moodle_url;
use context_system;
use core_user;
use local_external_users\event\user_submit;
/**
 * Class verification
 *
 * @package    local_external_users
 * @copyright  2026 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class verification {
    private stdClass $user;
    private common $common;
    private verification_form $mform;
    private \DateTime $currentdate;

    /**
     * @throws dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function __construct() {
        global $DB, $USER;

        $this->mform = new verification_form();
        $this->common = new common();
        $this->currentdate = new \DateTime();
        $this->user = $DB->get_record("user", ["id" => $USER->id], '*', MUST_EXIST);
        $this->transform_data();
    }
    /**
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function transform_data(): void {
        profile_load_data($this->user);

        $status = $this->user->profile_field_external_user_verified;
        $isverified = ($status === '1');

        $limited = \DateTime::createFromFormat('!d.m.Y', $status);
        $today = $this->currentdate->setTime(0, 0, 0);

        if ($limited instanceof \DateTime && $limited >= $today) {
            $isverified = true;
        }

        $allowbrowsing = get_config("local_external_users", "allowbrowsing");

        if (empty($this->user->profile_field_external_user) || $isverified || $allowbrowsing) {
            redirect(new moodle_url('/'));
        }
    }

    public function process_and_render(): void {
        global $OUTPUT;

        if ($this->mform->is_cancelled()) {
            redirect(new moodle_url('/'));
        } else if ($data = $this->mform->get_data()) {
            $this->handle_submission($data);
        }

        echo $OUTPUT->header();

        // Show comments from admins if they exist.
        if (!empty($this->user->profile_field_external_user_comment)) {
            echo $OUTPUT->notification($this->user->profile_field_external_user_comment, 'notifyproblem');
        }

        if (empty($this->user->profile_field_external_user_pending)) {
            echo get_config('local_external_users', 'onboardingdescription');
            echo $OUTPUT->spacer(['height' => 20, 'br' => true]);
            $this->mform->display();
        } else {
            echo $OUTPUT->notification(get_string('pending_msg', 'local_external_users'), 'info');
        }

        echo $OUTPUT->footer();
    }

    private function handle_submission($data): void {
        global $DB, $USER, $PAGE, $OUTPUT;

        if (!$this->user || empty($this->user->email)) {
            $this->user = $DB->get_record('user', ['id' => $USER->id], '*', MUST_EXIST);
        }

        $leftovers = $this->common->get_user_files($USER->id);
        if (count($leftovers)) {
            $DB->delete_records(
                'local_external_users_files',
                ['userid' => $this->user->id]
            );
        }

        $docvisible = get_config("local_external_users", "show_document");
        $photovisible = get_config("local_external_users", "show_photo");

        if (
            $this->common->storeFileToDB($this->mform, $USER, 'userfile', true, $docvisible) &&
            $this->common->storeFileToDB($this->mform, $USER, 'userfileimage', false, $photovisible)
        ) {
            $this->user->profile_field_external_user_pending = true;
            $this->user->profile_field_external_user_verified = 0;
            profile_save_data($this->user);

            $event = user_submit::create([
            'relateduserid' => $this->user->id,
            'context' => $PAGE->context,
            'objectid' => $this->user->id,
            'other' => ['userid' => $this->user->id],
            ]);
            $event->trigger();

            send_message_to_user(
                $USER->id,
                get_config("local_external_users", "signupmailsubject"),
                get_config("local_external_users", "signupmailmessage"),
                ""
            );

            $this->notify_review_team();

            // Redirect to homepage with the success message.
            redirect(new moodle_url("/"), get_string('success', 'local_external_users'), 5);
        } else {
            // If file storage fails, we don't redirect so the user can see the error.
            echo $OUTPUT->notification(
                get_string('pdf_error', 'local_external_users'),
                'notifymessage'
            );
        }
    }

    private function notify_review_team(): void {
        global $CFG;

        $reviewmail = get_config("local_external_users", "submissionreviewemail");

        if (empty($reviewmail)) {
            return;
        }

        $reviewteam = new \stdClass();
        $reviewteam->firstname = "USI";
        $reviewteam->lastname = "Team";
        $reviewteam->email = $reviewmail;
        $reviewteam->maildisplay = 1;
        $reviewteam->mailformat = 1;

        $emailfrom = \core_user::get_noreply_user();
        if (empty($emailfrom->email) || $emailfrom->email == 'stop@localhost') {
            $emailfrom->email = $CFG->noreplyaddress ?: 'noreply@' . parse_url($CFG->wwwroot, PHP_URL_HOST);
        }

        $subject = get_string('reviewsubject', 'local_external_users') . $this->user->username;
        $message = get_string('reviewbody', 'local_external_users') . $this->user->username;

        email_to_user(
            $reviewteam,
            $emailfrom,
            $subject,
            html_to_text($message),
            $message
        );
    }
}
