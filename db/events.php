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
 * Event observers for External Users
 *
 * @package    local_external_users
 * @category   event
 * @copyright  2026 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_updated',
        'callback'  => '\local_external_users\observer::purge_cache',
    ],
    [
        'eventname' => '\local_external_users\event\user_approved',
        'callback'  => '\local_external_users\observer::purge_cache',
    ],
    [
        'eventname' => '\local_external_users\event\user_limitedapproved',
        'callback'  => '\local_external_users\observer::purge_cache',
    ],
    [
        'eventname' => '\local_external_users\event\user_rejected',
        'callback'  => '\local_external_users\observer::purge_cache',
    ],
    [
        'eventname' => '\local_external_users\event\user_revoked',
        'callback'  => '\local_external_users\observer::purge_cache',
    ],
    [
        'eventname' => '\local_external_users\event\user_deactivated',
        'callback'  => '\local_external_users\observer::purge_cache',
    ],
];
