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

use DateTime;
use Exception;

require_once($CFG->dirroot . '/user/profile/lib.php');

class semester_validation extends \core\task\scheduled_task {

    /**
     * get_name function
     * @return string
     */
    public function get_name() {
        return "Semester Validation Task";
    }

    /**
     * execute function
     * @return void
     */
    public function execute() {
        global $DB;
        $dataset = $DB->get_records("user", array("auth" => "external", "deleted" => 0));
        foreach($dataset as $user) {
            profile_load_data($user);
            if($user->profile_field_external_user_verified != '0') {
                try {
                    $date = \DateTime::createFromFormat('d.m.Y', $user->profile_field_external_user_verified);
                    if ($date < new DateTime()) {
                        $user->profile_field_external_user_verified = '0';
                        $user->profile_field_eduPersonScopedAffiliation = 'external';
                        profile_save_data($user);
                    }
                } catch (\Exception $e) {
                    echo 'Caught exception: ',  $e->getMessage(), "\n";
                }
            }
        }
    }
}


