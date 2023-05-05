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
 * @copyright 2023 Stephan Lorbek
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Load Moodle's configuration settings
require_once(dirname(__FILE__) . '/../../../config.php');

// Load Moodle's message API
require_once($CFG->dirroot.'/message/lib.php');

function send_message($recipientid, $senderid, $comment) {
    global $DB, $USER;

    $recipient = $DB->get_record('user', array("id" => $recipientid));
    $sender = $USER;
    // Define the message details
    $message = new \core\message\message();
    $message->component = 'moodle';
    $message->name = 'instantmessage';
    $message->userfrom = $sender;
    $message->userto = $recipient;
    $message->subject = 'Rejection';
    $message->fullmessage = "You've been rejected";
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->contexturl = '';
    $message->contexturlname = '';
    //$message->set_processor('message_chat');

    //$messageid = message_send($message);
    $messageid = message_post_message($sender, $recipient, "Rejected", 0);

    return $messageid;
}
