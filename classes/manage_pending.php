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
 * Class manage_pending
 *
 * @package    local_external_users
 * @copyright  2026 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class manage_pending {
    private common $common;
    private html_table $waitingtable;
    private html_table $pendingtable;

    private array $pendingdata;
    private array $waitingdata;


    /**
     * @throws coding_exception
     * @throws dml_exception
     * @throws required_capability_exception
     * @throws moodle_exception
     */
    public function __construct() {
        global $CFG;

        $this->common = new common();

        $this->pendingtable = new html_table();
        $this->waitingtable = new html_table();
        $this->waitingdata = ['host' => $CFG->wwwroot];
        $this->pendingdata = ['host' => $CFG->wwwroot];

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
        $waitingexternalusers = $this->common->get_users_for_verification();
        $this->waitingtable->head = $tableheaders;
        $this->waitingtable->id = 'sortabletablewaiting';
        foreach ($waitingexternalusers as $user) {
            $actionurl = new moodle_url(
                "/local/external_users/profile.php",
                ['id' => $user->id, "referrer" => "manage_pending"]
            );
            $this->waitingtable->data[] = [
                html_writer::link(
                    $actionurl,
                    $user->username
                ),
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
        $pendingexternalusers = $this->common->get_pending_users_for_verification();
        $this->pendingtable->head = $tableheaders;
        $this->pendingtable->id = 'sortabletablepending';

        foreach ($pendingexternalusers as $user) {
            $actionurl = new moodle_url(
                "/local/external_users/profile.php",
                ['id' => $user->id, "referrer" => "manage_pending"]
            );
            $this->pendingtable->data[] = [
                html_writer::link(
                    $actionurl,
                    $user->username . ' <i class="fa fa-hourglass" aria-hidden="true"></i>'
                ),
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

        $this->waitingdata["table"] = html_writer::table($this->waitingtable);
        $this->waitingdata["title"] = get_string('pending', 'local_external_users');
        $this->waitingdata["count"] = strval(count($waitingexternalusers));
        $this->waitingdata["table-id"] = $this->waitingtable->id;

        $this->pendingdata["table"] = html_writer::table($this->pendingtable);
        $this->pendingdata["title"] = get_string('waiting', 'local_external_users');
        $this->pendingdata["count"] = strval(count($pendingexternalusers));
        $this->pendingdata["table-id"] = $this->pendingtable->id;
        $this->waitingdata["legend"] = true;
    }

    /**
     * @throws moodle_exception
     */
    public function render(): void {
        global $OUTPUT;
        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template(
            "local_external_users/dashboard_table",
            $this->pendingdata
        );
        echo $OUTPUT->render_from_template(
            "local_external_users/dashboard_table",
            $this->waitingdata
        );
        echo $OUTPUT->footer();
    }
}
