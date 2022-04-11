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

$pageurl = new \moodle_url('/local/external_users/views/manage.php');
$PAGE->set_url($pageurl);

$PAGE->set_title(get_string('pluginname', 'local_external_users'));
$PAGE->set_heading(get_string('pluginname', 'local_external_users'));
$PAGE->set_pagelayout('standard');
require_capability('local/external_users:manage', $context);
$userid = required_param('id', PARAM_INT);

echo $OUTPUT->header();

global $DB;
$userfiles = $DB->get_records('local_external_users_files', array('userid' => $userid));
$user = $DB->get_record_sql("SELECT id, username, firstname, middlename, lastname, phone1, email ". 
"FROM {user} WHERE id = :userid", array('userid' => $userid));

// User picture!
$userpic = new \user_picture($user);
$userpic->size = 128;
echo $OUTPUT->render($userpic);

$userinfo = \html_writer::start_tag("div", array("class" => "card-body"));
$userinfo .= \html_writer::tag("h3", $user->firstname . " " . $user->middlename . " " . $user->lastname, array('class' => 'lead'));
$userinfo .= \html_writer::start_tag("ul");
$userinfo .= \html_writer::tag("li", get_string('username', 'local_external_users') . ": " . $user->username, array('class' => 'contentnode'));
$userinfo .= \html_writer::tag("li", get_string('mail', 'local_external_users') . ": " .  $user->email, array('class' => 'contentnode'));
$userinfo .= \html_writer::tag("li", get_string('phone', 'local_external_users') . ": " .  $user->phone1, array('class' => 'contentnode'));
$userinfo .= \html_writer::end_tag("ul");
$userinfo .= \html_writer::end_tag("div");
echo $userinfo;

$filetable = new \html_table();
$filetable->attributes['class'] = 'table table-striped';
$filetable->head = array(get_string('files', 'local_external_users'), get_string('download', 'local_external_users'));
$filetable->data = array();

foreach ($userfiles as $file) {
    $actionurl = \moodle_url::make_pluginfile_url($file->contextid, $file->component, $file->filearea,
    $file->userid, $file->filepath, $file->filename, false);
    $filetable->data[] = array(format_string($file->filearea), \html_writer::link($actionurl, $file->filename));
};

echo \html_writer::table($filetable);

$action = is_user_verified($userid);
$label = $action ? get_string('revoke', 'local_external_users') : get_string('verify', 'local_external_users');
$control = \html_writer::start_tag("form", array("action" => "approve.php"));
$control .= \html_writer::tag("input" , "", array("type" => "text", "name" => "id", "hidden" => "", "value" => $userid));
$control .= \html_writer::tag("input" , "", array("type" => "text", "name" => "type", "hidden" => "", "value" => !$action));
$control .= \html_writer::tag("button", $label);
$control .= \html_writer::end_tag("form");
echo $control;

echo $OUTPUT->footer();
