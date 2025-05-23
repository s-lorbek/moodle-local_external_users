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
defined('MOODLE_INTERNAL') || die();

use coding_exception;
use moodleform;
require_once("$CFG->libdir/formslib.php");
require_once("common.php");

/**
 * Form for verifying external users.
 *
 * @package   local_external_users
 * @copyright 2022 Stephan Lorbek
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class verification_form extends moodleform {
    /**
     * Defines the verification form elements.
     *
     * @throws coding_exception
     * @throws \dml_exception
     */
    public function definition(): void {
        global $CFG;
        $mform = $this->_form;
        if (get_config("local_external_users", "show_photo")) {
            $mform->addElement(
                "filepicker",
                "userfileimage",
                get_string("form_image", "local_external_users"),
                null,
                [
                    "subdirs" => 0,
                    "maxbytes" => $CFG->maxbytes,
                    "maxfiles" => 1,
                    "accepted_types" => [
                        "image/png",
                        "image/jpeg",
                        "image/gif",
                        "image/bmp",
                    ],
                ]
            );
            if (get_config("local_external_users", "required_photo")) {
                $mform->addRule(
                    "userfileimage",
                    "Please upload a valid scan/image",
                    "required"
                );
            }
        }

        if (get_config("local_external_users", "show_document")) {
            $mform->addElement(
                "filepicker",
                "userfile",
                get_string("form_document", "local_external_users"),
                null,
                [
                    "subdirs" => 0,
                    "maxbytes" => $CFG->maxbytes,
                    "maxfiles" => 1,
                    "accepted_types" => ["application/pdf"],
                ]
            );
            if (get_config("local_external_users", "required_document")) {
                $mform->addRule(
                    "userfile",
                    "Please upload a valid document",
                    "required"
                );
            }
        }
    }
    /**
     * Validates the verification form data.
     *
     * @param array $data Form data to be validated.
     * @param array $files Files uploaded through the form.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        return [];
    }
}
