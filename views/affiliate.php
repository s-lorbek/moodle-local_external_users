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
use coding_exception;
use context_system;
use dml_exception;
use moodle_exception;
use moodle_url;
use required_capability_exception;

require('../../../config.php');
// @codingStandardsIgnoreEnd

class affiliate {
    private common $common;
    /**
     * @throws coding_exception
     * @throws dml_exception
     * @throws required_capability_exception
     */
    public function __construct() {
        global $PAGE;
        $context = context_system::instance();
        $PAGE->set_context($context);
        $PAGE->set_url(new moodle_url('/local/external_users/views/affiliate.php'));
        require_capability('local/external_users:manage', $context);
        $this->common = new common();
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function process(): void {
        $userid = required_param('id', PARAM_INT);
        $affiliation = required_param('affiliation', PARAM_TEXT);

        echo $this->common->setaffiliation($userid, $affiliation);
        redirect(new moodle_url("profile.php", ['id' => $userid]));
    }
}

$a = new affiliate();
$a->process();
