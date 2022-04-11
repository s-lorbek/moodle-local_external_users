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
require('../../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');
require_once('../classes/verification_form.php');
require_once('../classes/common.php');

$context = \context_system::instance();
$PAGE->set_context($context);

$pageurl = new \moodle_url('/local/external_users/views/verification.php');
$PAGE->set_url($pageurl);

$PAGE->set_title(get_string('pluginname', 'local_external_users'));
$PAGE->set_heading(get_string('pluginname', 'local_external_users'));
$PAGE->set_pagelayout('standard');

$mform = new verification_form();
echo $OUTPUT->header();

if ($mform->is_cancelled()) {
    redirect('/', 'Back to landing page', 10);
} else if ($data = $mform->get_data()) {
    global $DB;

    $name = $mform->get_new_filename('userfile');
    $filecontent = $mform->get_file_content('userfile');

    if (valid_pdf($filecontent)) {
        $rec = $mform->save_stored_file('userfile',
            \context_system::instance()->id,
            'local_external_users',
            'userfile',
            $USER->id,
            '/',
            $name,
            true);

        $file = array('contextid' => \context_system::instance()->id,
            'component' => 'local_external_users',
            'filearea' => 'userfile',
            'filepath' => '/',
            'userid' => $USER->id,
            'filename' => $name);

        $leftovers = get_user_files('userfile');
        if (count($leftovers)) {
            foreach($leftovers as $entry)
            {
                $DB->delete_records('local_external_users_files', array('userid' => $USER->id, 'id' => $entry->id));
            }
        }
        $DB->insert_record('local_external_users_files', $file);
        echo $OUTPUT->notification(
            "Success",
            'notifymessage');
    } else {
        echo $OUTPUT->notification(
            "PDF File not valid",
            'notifymessage');
    }
}
$mform->add_action_buttons($cancel = true,
    $submitlabel = get_string('form_submit', 'local_external_users'));


$mform->display();
echo $OUTPUT->footer();
