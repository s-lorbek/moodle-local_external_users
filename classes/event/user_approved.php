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

use core\event\base;
use moodle_url;
use stdClass;
use function get_string;

class user_approved extends base
{

    /**
     * init function
     * @return void
     */
    protected function init()
    {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'data_records';
    }

    /**
     * get_description function
     * @return string
     */
    public function get_description()
    {
        return "External user with id " . $this->other['userid'] . " has been changed to approved status. (was " . $this->other['oldstatus'] . ")";
    }

    /**
     * get_name function
     * @return string
     */
    public static function get_name()
    {
        return get_string("event_approved", "local_external_users");
    }

    /**
     * get_url function
     * @return string
     */
    public function get_url()
    {
        return new moodle_url('/local/external_users/views/profile.php',
            array('id' => $this->other['userid']));
    }

    /**
     * get_legacy_eventdata function
     * @return object
     */
    protected function get_legacy_eventdata()
    {
        $eventdata = new stdClass();
        $eventdata->cmid = $this->objectid;
        $eventdata->courseid = $this->courseid;
        $eventdata->userid = $this->userid;
        return $eventdata;
    }

    /**
     * get_legacy_logdata function
     * @return array
     */
    protected function get_legacy_logdata()
    {
        $url = new moodle_url('/local/external_users/views/profile.php',
            array('id' => $this->other['userid']));
        $urlparams = array('id' => $this->courseid);
        $info = "External Users";
        $eventname = get_string("event_approved", "local_external_users");
        $userid = $this->userid;
        $cmid = $this->objectid;
        $courseid = $this->courseid;
        $action = "approved";
        $description = "External user with id " . $this->other['userid'] . " has been changed to approved status.";

        return array($courseid, 'course', $action, $url->out(false,
            $urlparams), $info, $cmid, $userid, $description);
    }
}
