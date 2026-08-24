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

/**
 * Event class for user_deactivated.
 *
 * @package    local_external_users
 * @copyright  2026 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_deactivated extends base {
    /**
     * Initialize event metadata.
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'data_records';
    }

    /**
     * Get event description.
     * @return string
     */
    public function get_description(): string {
        return "External user with id " . $this->other['userid'] . " has been set to deleted status)";
    }

    /**
     * Get event name.
     * @return string
     * @throws coding_exception
     */
    public static function get_name(): string {
        return get_string("event_deactivated", "local_external_users");
    }

    /**
     * Get event URL.
     * @return moodle_url
     * @throws moodle_exception
     */
    public function get_url(): moodle_url {
        return new moodle_url(
            '/local/external_users/profile.php',
            ['id' => $this->other['userid']]
        );
    }
}
