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
 * @author     Stephan Lorbek
 * @copyright  2023 Stephan Lorbek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_external_users\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use dml_exception;

require_once($CFG->dirroot . '/user/profile/lib.php');

class comment_validation extends scheduled_task {
    /**
     * get_name function
     * @return string
     */
    public function get_name(): string {
        return "Comment Validation Task";
    }

    /**
     * execute function
     * @return void
     * @throws dml_exception
     */
    public function execute(): void {
        global $DB;
        $dataset = $DB->get_records(
            "user",
            ["auth" => "external", "deleted" => 0]
        );

        $dateformat = "d. M. Y";

        foreach ($dataset as $user) {
            profile_load_custom_fields($user);
            $timestamp = $user->profile["gebdat"];
            $birthdate = date($dateformat, $timestamp);
            $today = date($dateformat);
            $diff = date_diff(date_create($birthdate), date_create($today));
            $age = $diff->format('%y');

            $agethreshold = intval(get_config("local_external_users", "comment_validation_age"));
            if (!empty($agethreshold) && intval($age) > $agethreshold) {
                $customfields = [];
                $customfields["external_user_comment"] = "";
                profile_save_custom_fields($user->id, $customfields);
            }
        }
    }
}
