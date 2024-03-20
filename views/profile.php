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
use dml_exception;
use html_table;
use html_writer;
use moodle_exception;
use moodle_url;
use required_capability_exception;
use stdClass;
use function get_string;

require('../../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/datalib.php');
require_once('../classes/verification_form.php');
require_once('../classes/common.php');

class profile {
    private stdClass $user;
    private common $common;
    private array $userfiles;
    private int $userid;
    private array $profiledata;
    private array $profilecontrol;
    private string $referrer;
    /**
     * @throws coding_exception
     * @throws dml_exception
     * @throws coding_exception
     * @throws required_capability_exception|moodle_exception
     */
    public function __construct() {
        global $DB, $PAGE;

        $context = context_system::instance();
        $PAGE->set_context($context);

        $pageurl = new moodle_url('/local/external_users/views/manage.php');
        $PAGE->set_url($pageurl);
        $PAGE->set_title(get_string('pluginname', 'local_external_users'));
        $PAGE->set_heading(get_string('pluginname', 'local_external_users'));
        $PAGE->set_pagelayout('standard');
        require_capability('local/external_users:manage', $context);

        $this->common = new common();
        $this->userid = required_param('id', PARAM_INT);
        $this->referrer = required_param('referrer', PARAM_TEXT);
        $this->user = $DB->get_record("user", ['id' => $this->userid]);
        $this->transform_data();
    }

    /**
     * @throws coding_exception
     * @throws dml_exception|moodle_exception
     */
    private function transform_data(): void {
        global $DB;
        profile_load_data($this->user);

        $approveduntil = ($this->user->profile_field_external_user_verified != '0' &&
            $this->user->profile_field_external_user_verified != '1') ?
            $this->user->profile_field_external_user_verified : "-";

        switch ($this->user->profile_field_external_user_verified) {
            case "-1":
            case "0":
                $verifiedfield = get_string(
                    'no',
                    'local_external_users'
                );
                break;
            case "1":
                $verifiedfield = get_string(
                    'yes',
                    'local_external_users'
                );
                break;
            default:
                $verifiedfield = get_string(
                    'limited',
                    'local_external_users'
                );
        }
        $this->profiledata = [
            'back_label' => get_string('back_label', 'local_external_users'),
            'back_link' => $this->referrer . ".php",
            'firstname' => $this->user->firstname,
            'middlename' => $this->user->middlename,
            'lastname' => $this->user->lastname,
            'mail' => get_string('mail', 'local_external_users') . ": " . $this->user->email,
            'verified' => get_string(
                'approved',
                'local_external_users'
            ) . ": " . $verifiedfield,
            'verifiedtill' => get_string(
                'approveduntil',
                'local_external_users'
            ) . ": " . $approveduntil,
            'eduScope' => "EduScope : " . $this->user->profile_field_eduPersonScopedAffiliation,
            'uploadedfiles_header' => get_string(
                'uploadedfiles',
                'local_external_users'
            ),
            'birthdate' => get_string(
                'birthdate',
                'local_external_users'
            ) . ": " . date(
                'd.m.Y',
                property_exists($this->user, 'profile_field_gebdat') ? $this->user->profile_field_gebdat : null
            ),
            'username' => get_string('username', 'local_external_users') . ": " . $this->user->username,
            'affiliation' => get_string('affiliation', 'local_external_users') . ": " .
                $this->user->profile_field_external_user_affiliation,
        ];

        if (
            !$DB->record_exists(
                "local_external_users_files",
                ["userid" => $this->userid]
            )
        ) {
            $this->profiledata['userpiclink'] = "";
        } else {
            $userpic = $DB->get_record_sql(
                "SELECT * FROM {local_external_users_files} WHERE userid = :userid and filearea LIKE 'userfileimage'",
                ["userid" => $this->userid]
            );
            if (isset($userpic)) {
                $actionurl = moodle_url::make_pluginfile_url(
                    $userpic->contextid,
                    $userpic->component,
                    $userpic->filearea,
                    $userpic->userid,
                    $userpic->filepath,
                    $userpic->filename
                );
                $this->profiledata['userpiclink'] = $actionurl;
            }
        }

        $filetable = new html_table();
        $filetable->attributes['class'] = 'table table-striped';
        $filetable->head = [get_string('download', 'local_external_users')];
        $filetable->data[] = $this->common->get_user_file_table($this->userid);

        $this->profiledata['filetable'] = html_writer::table($filetable);

        $options = html_writer::start_tag("div", []);
        $options .= html_writer::tag(
            "input",
            "",
            ["id" => "option1", "type" => "radio", "name" => "reject", "value" => 0, "checked" => ""]
        );
        $options .= html_writer::tag(
            "label",
            get_string('rejection_control_mailonly', 'local_external_users'),
            ["for" => "option1", "class" => "px-1"]
        );
        $options .= html_writer::end_tag("div");

        $options .= html_writer::start_tag("div", []);
        $options .= html_writer::tag(
            "input",
            "",
            ["id" => "option2", "type" => "radio", "name" => "reject", "value" => 1]
        );
        $options .= html_writer::tag(
            "label",
            get_string('rejection_control_maildelete', 'local_external_users'),
            ["for" => "option2", "class" => "px-1"]
        );
        $options .= html_writer::end_tag("div");

        $action = $this->common->is_user_verified($this->userid);
        $affiliationoptions = $this->common->getaffiliationoptions();
        $affiliationoptions = array_map(function ($affiliationoptions) {
            return ['value' => $affiliationoptions,
                'selected' => ($affiliationoptions === $this->user->profile_field_external_user_affiliation)];
        }, $affiliationoptions);

        $this->profilecontrol = [
            'legend' => get_string('rejection_control_header', 'local_external_users'),
            'options' => $options,
            'action' => !$action,
            'userid' => $this->userid,
            'btn1_style' => !$action ? "primary" : "warning",
            'rejection_control_additional_comment' => get_string(
                'rejection_control_additional_comment',
                'local_external_users'
            ),
            'rejection_header' => get_string('reject', 'local_external_users'),
            'affiliation_label' => get_string('affiliation', 'local_external_users'),
            'affiliation_options' => $affiliationoptions,
        ];

        if ($this->common->is_user_verified($this->userid)) {
            $this->profilecontrol['revoke'] = get_string('revoke', 'local_external_users');
        } else {
            $this->profilecontrol['approve'] = get_string('approve', 'local_external_users');
            $this->profilecontrol['approve-limited'] = get_string(
                'approvelimited',
                'local_external_users'
            ) . $this->common->getEndOfSemester() . ")";
            $this->profilecontrol['approve-limited2'] = get_string(
                'approvelimited2',
                'local_external_users'
            ) . $this->common->getEndOfNextSemester() . ")";
        }
    }

    /**
     * @throws moodle_exception
     */
    public function render(): void {
        global $OUTPUT;
        echo $OUTPUT->header();
        profile_load_data($this->user);
        if (empty($this->userfiles) && !$this->user->profile_field_external_user_verified) {
            echo $OUTPUT->notification(get_string(
                'pending_onboarding',
                'local_external_users'
            ), 'notifymessage');
        }
        echo text_to_html($OUTPUT->render_from_template(
            "local_external_users/profile",
            $this->profiledata
        ));
        echo text_to_html($OUTPUT->render_from_template(
            "local_external_users/profile_control",
            $this->profilecontrol
        ));
        echo $OUTPUT->footer();
    }
}

$p = new profile();
$p->render();
