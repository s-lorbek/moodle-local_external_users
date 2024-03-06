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
global $CFG;

use coding_exception;
use context_system;
use dml_exception;
use html_table;
use html_writer;
use moodle_exception;
use moodle_url;
use required_capability_exception;
use function get_string;

require('../../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir . '/datalib.php');
require_once('../classes/common.php');

class manage_rejected {
    private common $common;
    private array $dashboarddata;

    /**
     * @throws coding_exception
     * @throws dml_exception
     * @throws required_capability_exception
     * @throws moodle_exception
     */
    public function __construct() {
        global $PAGE;
        $context = context_system::instance();
        $PAGE->set_context($context);
        $PAGE->set_url(new moodle_url('/local/external_users/views/manage_rejected.php'));
        $PAGE->set_title(get_string('pluginname', 'local_external_users'));
        $PAGE->set_heading(get_string('pluginname', 'local_external_users'));
        $PAGE->set_pagelayout('standard');
        require_capability('local/external_users:manage', $context);
        $this->common = new common();

        $this->dashboarddata = [];

        self::transform_data();
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function transform_data(): void {
        $tableheaders = [get_string('username', 'local_external_users'),
            get_string('firstname', 'local_external_users'),
            get_string('lastname', 'local_external_users'),
            get_string('manage', 'local_external_users'),
            "Moodle Link"];

        $rejectedusers = $this->common->get_users_rejected();
        $rejectedtable = new html_table();
        $rejectedtable->head = $tableheaders;
        $rejectedtable->id = 'sortabletablerejected';

        foreach ($rejectedusers as $user) {
            $actionurl = new moodle_url(
                "/local/external_users/views/profile.php",
                ['id' => $user->id]
            );
            $rejectedtable->data[] = [
                html_writer::link($actionurl, format_string($user->username)),
                format_string($user->firstname),
                format_string($user->lastname),
                html_writer::link($actionurl, "Link"),
                html_writer::link(
                    new moodle_url(
                        "/user/profile.php",
                        ["id" => $user->id]
                    ),
                    get_string("usericon", "local_external_users")
                )];
        }
        $this->dashboarddata["table"] = html_writer::table($rejectedtable);
        $this->dashboarddata["title"] = get_string('rejected', 'local_external_users');
        $this->dashboarddata["count"] = strval(count($rejectedusers));
        $this->dashboarddata["table-id"] = $rejectedtable->id;
    }

    /**
     * @throws moodle_exception
     */
    public function render() {
        global $OUTPUT;
        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template(
            "local_external_users/dashboard_table",
            $this->dashboarddata
        );
        echo $OUTPUT->footer();
    }
}

$mr = new manage_rejected();
$mr->render();
