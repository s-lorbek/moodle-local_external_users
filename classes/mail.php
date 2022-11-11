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

function send($users, $subject, $content) {
    global $CFG, $PAGE;
    $noreply = new \stdClass();
    $noreply->firstname = $CFG->supportname;
    $noreply->lastname = '';
    $noreply->username = 'usiadmin';
    $noreply->email = $CFG->supportemail;
    $noreply->maildisplay = 2;
    $noreply->alternatename = "";
    $noreply->firstnamephonetic = "";
    $noreply->lastnamephonetic = "";
    $noreply->middlename = "";
    $sucessfullcount = 0;

    if (empty($users)) {
        return 0;
    }
    foreach ($users as $user) {
        $success = email_to_user($user, $noreply, $subject,
            html_to_text($content), $content, '', '', true);
        if (!$success) {
            $event = \local_external_users\event\mail_failed::create(array(
                'relateduserid' => $user->id,
                'context' => $PAGE->context,
                'objectid' => 0,
                'other' => array(
                    'user' => $user->username,
                )
            ));
            $event->trigger();
        } else {
            $sucessfullcount++;
        }
    }
    return $sucessfullcount;
}
