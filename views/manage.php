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

// @codingStandardsIgnoreStart
global $CFG;

use coding_exception;
use context_system;
use dml_exception;
use moodle_exception;
use moodle_url;
use required_capability_exception;
use function get_string;

require('../../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/datalib.php');
require_once('../classes/verification_form.php');
require_once('../classes/common.php');

/**
 * Class manage
 *
 * Handles the management dashboard for the local_external_users plugin.
 *
 * @package    local_external_users
 * @copyright  2024 Stephan (your name or organization)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage {
    /**
     * @var array $dashboarddata Data for the management dashboard template.
     */
    private array $dashboarddata;

    /**
     * Constructs the manage dashboard page for the plugin.
     *
     * @throws coding_exception
     * @throws dml_exception
     * @throws required_capability_exception
     */
    public function __construct() {
        global $PAGE;
        $context = context_system::instance();
        $PAGE->set_context($context);
        $PAGE->set_url(new moodle_url('/local/external_users/views/manage.php'));
        $PAGE->set_title(get_string('pluginname', 'local_external_users'));
        $PAGE->set_heading(get_string('pluginname', 'local_external_users'));
        $PAGE->set_pagelayout('standard');
        require_capability('local/external_users:manage', $context);
        self::transform_data();
    }

    /**
     * Prepares and sets dashboard data for the management dashboard template.
     *
     * @throws coding_exception
     */
    private function transform_data() {
        global $USER;
        $this->dashboarddata["pending"] = get_string('waiting', 'local_external_users');
        $this->dashboarddata["approved"] = get_string('approved', 'local_external_users');
        $this->dashboarddata["rejected"] = get_string('rejected', 'local_external_users');

        if (has_capability('local/external_users:manage', context_system::instance(), $USER)) {
            $this->dashboarddata["settings"] = "Plugin " . get_string('settings');
            $this->dashboarddata["auth_settings"] = get_string('authentication') . " " . get_string('settings');
        }
    }

    /**
     * Renders the management dashboard page.
     *
     * @throws moodle_exception
     */
    public function render() {
        global $OUTPUT;
        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template(
            "local_external_users/dashboard",
            $this->dashboarddata
        );
        echo $OUTPUT->footer();
    }
}

$m = new manage();
$m->render();
