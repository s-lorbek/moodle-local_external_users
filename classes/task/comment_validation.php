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
    public function execute() {
        global $DB;
        $dataset = $DB->get_records(
            "user",
            ["auth" => "external", "deleted" => 0]
        );
        /*
            TODO
        */
    }
}
