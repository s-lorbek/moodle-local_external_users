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

namespace local_external_users\event;

use coding_exception;
use core\event\base;
use moodle_exception;
use moodle_url;
use stdClass;
use function get_string;

class user_deactivated extends base {
    /**
     * init function
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'data_records';
    }

    /**
     * get_description function
     * @return string
     */
    public function get_description(): string {
        return "External user with id " . $this->other['userid'] . " has been set to deleted status)";
    }

    /**
     * get_name function
     * @return string
     * @throws coding_exception
     */
    public static function get_name(): string {
        return get_string("event_deactivated", "local_external_users");
    }

    /**
     * get_url function
     * @return moodle_url
     * @throws moodle_exception
     */
    public function get_url(): moodle_url {
        return new moodle_url(
            '/local/external_users/views/profile.php',
            ['id' => $this->other['userid']]
        );
    }
}
