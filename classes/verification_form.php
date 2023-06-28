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

defined('MOODLE_INTERNAL') || die();

require_once('common.php');

class verification_form extends \moodleform {
    public function definition() {
        global $CFG;
        $mform = $this->_form;
        $mform->addElement('filepicker', 'userfileimage', "Profile image upload", null,
            array('maxbytes' => 5000000, 'accepted_types' => array('image/png', 'image/jpeg', 'image/gif')));
        $mform->addElement('select', 'role_field', get_string('role'), array());
        $mform->setType('role_field', PARAM_RAW);
        $mform->addElement('text', 'subject_field',
            get_string('form_subject', 'local_external_users'));
        $mform->setType('subject_field', PARAM_RAW);
        $mform->addElement('editor', 'content_field',
            get_string('form_body', 'local_external_users'));
        $mform->setType('content', PARAM_RAW);
        $mform->addElement('filepicker', 'userfile', get_string('file'), null,
                   array('maxbytes' => 5000000, 'accepted_types' => 'pdf'));
    }
    public function validation($data, $files) {
        return array();
    }
}
