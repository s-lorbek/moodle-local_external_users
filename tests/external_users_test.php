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
use core\context\course;
use DateTime;
use dml_exception;
use stdClass;
use advanced_testcase;


/**
 * External users tests class.
 *
 * Contains tests for event handler and helper functions
 *
 * @package    local_external_users
 * @category   test
 * @copyright  2024 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class external_users_test extends advanced_testcase {
    /** @var stdClass A test course. */
    protected stdClass $course;


    /** @var stdClass $external_user A test user of type external user. */
    protected stdClass $externaluser;

    /** @var common $commonClass Class object of utility class common */
    protected common $commonclass;


    /**
     * Setup test data.
     */
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest(false);
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $this->course = self::getDataGenerator()->create_course();
        $this->coursecontext = course::instance($this->course->id);
        $this->externaluser = self::getDataGenerator()->create_user();
        $this->commonclass = new common();

        $generator = self::getDataGenerator();
        $generator->create_custom_profile_field(['datatype' => 'text', 'shortname' => 'eduPersonScopedAffiliation',
            'name' => 'eduPersonScopedAffiliation',
            'visible' => 0]);

        // Condition after being registered using auth_external.
        profile_load_data($this->externaluser);
        $this->externaluser->profile_field_external_user = true;
        $this->externaluser->profile_field_external_user_verified = 0;
        $this->externaluser->profile_field_external_user_pending = false;
        profile_save_data($this->externaluser);
    }

    /**
     * Test call for triggering course creation events
     * @covers \local_external_users\common::verify_user
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_verify_user(): void {
        $this->resetAfterTest();
        $tariff = 'external';
        $defaultcomment = get_config("local_external_users", "discounturl");

        $this->commonclass->verify_user($this->externaluser->id, $tariff);

        profile_load_data($this->externaluser);
        $this->assertEquals($tariff, $this->externaluser->profile_field_eduPersonScopedAffiliation);
        $this->assertEquals(1, $this->externaluser->profile_field_external_user);
        $this->assertEquals(1, $this->externaluser->profile_field_external_user_verified);
        $this->assertEquals(0, $this->externaluser->profile_field_external_user_pending);
        $this->assertEquals($defaultcomment, $this->externaluser->profile_field_external_user_comment);
    }

    /**
     * Test call for triggering user rejection without deletion
     * @covers \local_external_users\common::reject_user
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_reject_user_without_deletion(): void {
        $this->resetAfterTest();
        global $DB;
        $comment = 'Reason of rejection';
        $nodelete = 0;

        $this->commonclass->reject_user($this->externaluser->id, $nodelete, $comment);

        profile_load_data($this->externaluser);
        $this->assertEquals(1, $this->externaluser->profile_field_external_user);
        $this->assertEquals(-1, $this->externaluser->profile_field_external_user_verified);
        $this->assertEquals(0, $this->externaluser->profile_field_external_user_pending);
        $this->assertEquals($comment, $this->externaluser->profile_field_external_user_comment);
        $this->assertEquals(0, $DB->get_field(
            "user",
            'deleted',
            ['id' => $this->externaluser->id]
        ));
    }

    /**
     * Test call for triggering user rejection with deletion
     * @covers \local_external_users\common::reject_user
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_reject_user_with_deletion(): void {
        global $DB;
        $this->resetAfterTest();
        $comment = 'Reason of rejection';
        $delete = 1;

        $this->commonclass->reject_user($this->externaluser->id, $delete, $comment);

        $this->assertEquals(1, $DB->get_field("user", 'deleted', ['id' => $this->externaluser->id]));
    }

    /**
     * Test call for triggering user rejection with deletion
     * @covers \local_external_users\common::reject_user
     * @throws dml_exception
     */
    public function test_getendofsemester_without_config(): void {
        $this->resetAfterTest();

        $today = new \DateTime();
        $currentmonth = (int) $today->format('n');
        $result = $this->commonclass->getendofsemester();
    }

    /**
     * Test call for triggering user rejection with deletion
     * @covers \local_external_users\common::reject_user
     * @throws dml_exception
     */
    public function test_setaffiliation(): void {
        $this->resetAfterTest();
        $affiliation = "University XYZ";
        $this->commonclass->setaffiliation($this->externaluser->id, $affiliation);
        profile_load_data($this->externaluser);
        $this->assertEquals($affiliation, $this->externaluser->profile_field_external_user_affiliation);
    }
}
