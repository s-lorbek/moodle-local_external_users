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
 * TODO describe file action
 *
 * @package    local_external_users
 * @copyright  2026 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();

$context = \context_system::instance();
require_capability('local/external_users:manage', $context);
require_sesskey();

$PAGE->set_url(new moodle_url('/local/external_users/action.php'));
$PAGE->set_context($context);

$action   = required_param('action', PARAM_ALPHA);
$userid   = required_param('id', PARAM_INT);
$referrer = optional_param('referrer', 'manage', PARAM_ALPHANUMEXT);
$allowedreferrers = ['manage', 'profile', 'verification', 'manage_pending', 'manage_verified', 'manage_rejected'];
if (!in_array($referrer, $allowedreferrers)) {
    $referrer = 'manage';
}$common   = new \local_external_users\common();

switch ($action) {
    case 'approve':
        $type     = required_param('type', PARAM_INT);
        $tariff   = optional_param('tariff', 'external', PARAM_TEXT);
        $duration = optional_param('submitButton', 'regular', PARAM_TEXT);

        if ($type == 1) {
            if ($duration === 'limited') {
                $common->limited_verify_user($userid, $tariff, 'limited');
            } else if ($duration === 'limited2') {
                $common->limited_verify_user($userid, $tariff, 'limited2');
            } else {
                $common->verify_user($userid, $tariff);
            }
        } else {
            $common->revoke_user($userid);
        }
        break;

    case 'reject':
        $comment = optional_param('comment', '', PARAM_TEXT);
        $mode    = optional_param('reject', 0, PARAM_INT); // 0 = mail only, 1 = mail & delete.
        $common->reject_user($userid, $mode, $comment);
        break;

    case 'affiliate':
        $affiliation = required_param('affiliation', PARAM_ALPHANUMEXT);
        $common->setaffiliation($userid, $affiliation);
        break;

    default:
        throw new \moodle_exception('error:unknownaction', 'local_external_users');
}

$url = new moodle_url('/local/external_users/' . $referrer . '.php', ['id' => $userid]);
redirect($url, get_string('redirect', 'local_external_users'), 0);
