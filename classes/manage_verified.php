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

namespace local_external_users;


use coding_exception;
use context_system;
use dml_exception;
use html_table;
use html_writer;
use moodle_exception;
use moodle_url;
use required_capability_exception;
use function get_string;

/**
 * Class manage_verified
 *
 * @package    local_external_users
 * @copyright  2026 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_verified {
    private common $common;
    private array $dashboarddata;
    /**
     * @throws coding_exception
     * @throws dml_exception
     * @throws required_capability_exception
     * @throws moodle_exception
     */
    public function __construct() {
        global $CFG;

        $this->common = new common();
        $this->dashboarddata = ['host' => $CFG->wwwroot];
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

        $verifiedusers = $this->common->get_users_already_verified();
        $approvedtable = new html_table();
        $approvedtable->head = $tableheaders;
        $approvedtable->id = 'sortabletableapproved';

        foreach ($verifiedusers as $user) {
            $actionurl = new moodle_url(
                "/local/external_users/profile.php",
                ['id' => $user->id, "referrer" => "manage_verified"]
            );
            $approvedtable->data[] = [
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
        $this->dashboarddata["table"] = html_writer::table($approvedtable);
        $this->dashboarddata["title"] = get_string('approved', 'local_external_users');
        $this->dashboarddata["count"] = strval(count($verifiedusers));
        $this->dashboarddata["table-id"] = $approvedtable->id;
    }

    /**
     * @throws moodle_exception
     */
    public function render(): void {
        global $OUTPUT;
        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template(
            "local_external_users/dashboard_table",
            $this->dashboarddata
        );
        echo $OUTPUT->footer();
    }
}
