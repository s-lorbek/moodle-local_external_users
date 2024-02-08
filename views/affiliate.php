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

// @codingStandardsIgnoreStart
use context_system;
use moodle_url;

require('../../../config.php');
// @codingStandardsIgnoreEnd

$context = context_system::instance();
$PAGE->set_context($context);

$pageurl = new moodle_url('/local/external_users/views/approve.php');
$PAGE->set_url($pageurl);

require_capability('local/external_users:manage', $context);
$common = new common();

$userid = required_param('id', PARAM_INT);
$affiliation = required_param('affiliation', PARAM_TEXT);

echo $common->setaffiliation($userid, $affiliation);
redirect(new moodle_url("profile.php", ['id' => $userid]));