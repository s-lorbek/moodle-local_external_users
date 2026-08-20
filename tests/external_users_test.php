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

    protected stdClass $coursecontext;


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
        $this->assertEquals('', $this->externaluser->profile_field_external_user_pending);
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
        $this->assertEquals('', $this->externaluser->profile_field_external_user_pending);
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
     * Test getendofsemester.
     * @covers \local_external_users\common::getendofsemester
     * @throws dml_exception
     */
    public function test_getendofsemester(): void {
        $this->resetAfterTest();

        // 1. With configuration.
        set_config("endofterm", "15.05.2027", "local_external_users");
        $this->assertEquals("15.05.2027", $this->commonclass->getendofsemester());

        // 2. Without configuration (relies on current month).
        unset_config("endofterm", "local_external_users");
        $today = new \DateTime();
        $currentmonth = (int)$today->format('n');
        if ($currentmonth >= 3 && $currentmonth <= 9) {
            $expected = $today->format("t.09.Y");
        } else {
            $nextyear = $today->format('Y') + 1;
            $expected = "28.02.$nextyear";
        }
        $this->assertEquals($expected, $this->commonclass->getendofsemester());
    }

    /**
     * Test getendofnextsemester.
     * @covers \local_external_users\common::getendofnextsemester
     * @throws dml_exception
     */
    public function test_getendofnextsemester(): void {
        $this->resetAfterTest();

        // 1. With configuration.
        set_config("endofnextterm", "31.12.2027", "local_external_users");
        $this->assertEquals("31.12.2027", $this->commonclass->getendofnextsemester());

        // 2. Without configuration (relies on current month).
        unset_config("endofnextterm", "local_external_users");
        $today = new \DateTime();
        $currentmonth = (int)$today->format('n');
        $currentyear = $today->format('Y');
        $nextyear = $currentyear + 1;
        if ($currentmonth >= 3 && $currentmonth <= 9) {
            $expected = "28.02.$nextyear";
        } else {
            $expected = "30.09.$nextyear";
        }
        $this->assertEquals($expected, $this->commonclass->getendofnextsemester());
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

    /**
     * Tests that the hook correctly redirects the user if the verified date is expired.
     *
     * Creates a user and sets the external user flag and the verified date to yesterday.
     * Then calls the hook and checks that a redirect occurred.
     *
     * @throws moodle_exception
     */
    public function test_redirect_on_expired_date(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $verified = $DB->get_field('user_info_field', 'id', ['shortname' => 'external_user_verified']);
        $external = $DB->get_field('user_info_field', 'id', ['shortname' => 'external_user']);

        $DB->insert_record('user_info_data', [
        'userid' => $user->id,
        'fieldid' => $external,
        'data' => '1',
        ]);

        $yesterday = (new \DateTime('yesterday'))->format('d.m.Y');
        $DB->insert_record('user_info_data', [
        'userid' => $user->id,
        'fieldid' => $verified,
        'data' => $yesterday,
        ]);

        $this->setUser($user);

        $PAGE->set_url(new \moodle_url('/index.php'));
        $hookmock = $this->getMockBuilder(\core\hook\output\before_http_headers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectException(\moodle_exception::class);
        \local_external_users\hook_callbacks::onload($hookmock);
        $this->assertTrue(true, "Hook finished with redirecting because the user has a expired date.");
    }

    /**
     * Tests that the hook doesn't redirect the user if the date is valid.
     *
     * The test creates a user and sets the external user flag and the verified date to a valid date in the future.
     * It then calls the hook and checks that no redirect occurred.
     */
    public function test_noredirect_on_valid_date(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $verified = $DB->get_field('user_info_field', 'id', ['shortname' => 'external_user_verified']);
        $external = $DB->get_field('user_info_field', 'id', ['shortname' => 'external_user']);

        $DB->insert_record('user_info_data', [
        'userid' => $user->id,
        'fieldid' => $external,
        'data' => '1',
        ]);

        $todayinnextyear = (new \DateTime())->modify('+1 year')->format('d.m.Y');
        $DB->insert_record('user_info_data', [
        'userid' => $user->id,
        'fieldid' => $verified,
        'data' => $todayinnextyear,
        ]);

        $this->setUser($user);

        $PAGE->set_url(new \moodle_url('/index.php'));
        $hookmock = $this->getMockBuilder(\core\hook\output\before_http_headers::class)
            ->disableOriginalConstructor()
            ->getMock();
        \local_external_users\hook_callbacks::onload($hookmock);
        $this->assertTrue(true, "Hook finished without redirecting because the user has a valid date.");
    }

    /**
     * Tests that the hook redirects the user if the user is unverified or rejected.
     *
     * The test creates a user and sets the external user flag and the verified date to unverified and rejected respectively.
     * It then calls the hook and checks that a redirect occurred in both cases.
     */
    public function test_redirect_on_unverified_or_rejected_user(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $verified = $DB->get_field('user_info_field', 'id', ['shortname' => 'external_user_verified']);
        $external = $DB->get_field('user_info_field', 'id', ['shortname' => 'external_user']);

        $DB->insert_record('user_info_data', [
        'userid' => $user->id,
        'fieldid' => $external,
        'data' => '1',
        ]);

        $DB->insert_record('user_info_data', [
        'userid' => $user->id,
        'fieldid' => $verified,
        'data' => '0', // Unverified.
        ]);

        $this->setUser($user);

        $PAGE->set_url(new \moodle_url('/index.php'));
        $hookmock = $this->getMockBuilder(\core\hook\output\before_http_headers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectException(\moodle_exception::class);
        \local_external_users\hook_callbacks::onload($hookmock);

        $DB->insert_record('user_info_data', [
        'userid' => $user->id,
        'fieldid' => $verified,
        'data' => '-1', // Rejected.
        ]);
         $PAGE->set_url(new \moodle_url('/index.php'));
        $hookmock = $this->getMockBuilder(\core\hook\output\before_http_headers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectException(\moodle_exception::class);
        \local_external_users\hook_callbacks::onload($hookmock);
    }

    /**
     * Ensure the hook does **not** redirect when the user has a pending
     * verification request and allow browsing is enabled. A pending flag is set by the submission handler
     * and prevents the homepage-from-submission redirect loop.
     */
    public function test_no_redirect_for_pending_user_with_allowbrowsing(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $verified = $DB->get_field('user_info_field', 'id', ['shortname' => 'external_user_verified']);
        $external = $DB->get_field('user_info_field', 'id', ['shortname' => 'external_user']);
        $pending = $DB->get_field('user_info_field', 'id', ['shortname' => 'external_user_pending']);

        set_config("allowbrowsing", 1, "local_external_users");

        $DB->insert_record('user_info_data', [
            'userid' => $user->id,
            'fieldid' => $external,
            'data' => '1',
        ]);
        $DB->insert_record('user_info_data', [
            'userid' => $user->id,
            'fieldid' => $verified,
            'data' => '0', // unverified
        ]);
        $DB->insert_record('user_info_data', [
            'userid' => $user->id,
            'fieldid' => $pending,
            'data' => '1',
        ]);

        $this->setUser($user);
        $PAGE->set_url(new \moodle_url('/index.php'));
        $hookmock = $this->getMockBuilder(\core\hook\output\before_http_headers::class)
            ->disableOriginalConstructor()
            ->getMock();
        // no exception means redirect was skipped
        \local_external_users\hook_callbacks::onload($hookmock);
        $this->assertTrue(true, 'Pending user should not be redirected');
    }

    /**
     * Test valid_pdf function.
     * @covers \local_external_users\common::valid_pdf
     */
    public function test_valid_pdf(): void {
        $this->assertEquals(1, $this->commonclass->valid_pdf("%PDF-1.4\n..."));
        $this->assertEquals(1, $this->commonclass->valid_pdf("%PDF-something"));
        $this->assertEquals(0, $this->commonclass->valid_pdf("Not a PDF"));
        $this->assertEquals(0, $this->commonclass->valid_pdf(""));
    }

    /**
     * Test parse_string_to_array function.
     * @covers \local_external_users\common::parse_string_to_array
     */
    public function test_parse_string_to_array(): void {
        $this->assertEquals(['a', 'b', 'c'], $this->commonclass->parse_string_to_array('a, b, c'));
        $this->assertEquals(['a', 'b', 'c'], $this->commonclass->parse_string_to_array('a,b,c'));
        $this->assertEquals(['a'], $this->commonclass->parse_string_to_array('a'));
        $this->assertEquals([''], $this->commonclass->parse_string_to_array(''));
    }

    /**
     * Test check_redirect_excludes function.
     * @covers \local_external_users\common::check_redirect_excludes
     */
    public function test_check_redirect_excludes(): void {
        $this->resetAfterTest();
        set_config("redirect_excludes", "login,logout,forgot", "local_external_users");

        $this->assertTrue($this->commonclass->check_redirect_excludes('/login/index.php'));
        $this->assertTrue($this->commonclass->check_redirect_excludes('/auth/logout.php'));
        $this->assertFalse($this->commonclass->check_redirect_excludes('/index.php'));
        $this->assertFalse($this->commonclass->check_redirect_excludes('/user/profile.php'));
    }

    /**
     * Test create_dummy_user function.
     * @covers \local_external_users\common::create_dummy_user
     */
    public function test_create_dummy_user(): void {
        $user = $this->commonclass->create_dummy_user('Test Dummy', 'dummy@example.com');
        $this->assertInstanceOf(\stdClass::class, $user);
        $this->assertEquals('dummy@example.com', $user->email);
        $this->assertEquals('Test Dummy', $user->firstname);
        $this->assertEquals('', $user->lastname);
        $this->assertEquals(-99, $user->id);
    }

    /**
     * Test is_external_user function.
     * @covers \local_external_users\common::is_external_user
     */
    public function test_is_external_user(): void {
        $this->resetAfterTest();

        // 1. User does not exist.
        $this->assertEquals(-1, $this->commonclass->is_external_user(99999));

        // 2. User exists but is not an external user.
        $nonexternaluser = $this->getDataGenerator()->create_user();
        profile_load_data($nonexternaluser);
        $nonexternaluser->profile_field_external_user = 0;
        profile_save_data($nonexternaluser);
        $this->assertEmpty($this->commonclass->is_external_user($nonexternaluser->id));

        // 3. User exists and is an external user.
        $externaluser = $this->getDataGenerator()->create_user();
        profile_load_data($externaluser);
        $externaluser->profile_field_external_user = 1;
        profile_save_data($externaluser);
        $this->assertEquals(1, $this->commonclass->is_external_user($externaluser->id));
    }

    /**
     * Test is_user_verified function.
     * @covers \local_external_users\common::is_user_verified
     */
    public function test_is_user_verified(): void {
        $this->resetAfterTest();

        // 1. User does not exist.
        $this->assertEquals(-1, $this->commonclass->is_user_verified(99999));

        // 2. User exists but is not an external user.
        $nonexternaluser = $this->getDataGenerator()->create_user();
        profile_load_data($nonexternaluser);
        $nonexternaluser->profile_field_external_user = 0;
        profile_save_data($nonexternaluser);
        $this->assertEquals(-1, $this->commonclass->is_user_verified($nonexternaluser->id));

        // 3. External user, unverified.
        $user1 = $this->getDataGenerator()->create_user();
        profile_load_data($user1);
        $user1->profile_field_external_user = 1;
        $user1->profile_field_external_user_verified = 0;
        profile_save_data($user1);
        $this->assertEquals(0, $this->commonclass->is_user_verified($user1->id));

        // 4. External user, verified.
        $user2 = $this->getDataGenerator()->create_user();
        profile_load_data($user2);
        $user2->profile_field_external_user = 1;
        $user2->profile_field_external_user_verified = 1;
        profile_save_data($user2);
        $this->assertEquals(1, $this->commonclass->is_user_verified($user2->id));

        // 5. External user, rejected.
        $user3 = $this->getDataGenerator()->create_user();
        profile_load_data($user3);
        $user3->profile_field_external_user = 1;
        $user3->profile_field_external_user_verified = -1;
        profile_save_data($user3);
        $this->assertEquals(-1, $this->commonclass->is_user_verified($user3->id));
    }

    /**
     * Test list retrieving functions.
     * @covers \local_external_users\common::get_users_for_verification
     * @covers \local_external_users\common::get_pending_users_for_verification
     * @covers \local_external_users\common::get_users_already_verified
     * @covers \local_external_users\common::get_users_rejected
     * @throws dml_exception
     */
    public function test_get_users_lists(): void {
        global $DB;
        $this->resetAfterTest();

        // 1. User needs verification (unverified, not pending, auth=external).
        $u1 = $this->getDataGenerator()->create_user(['username' => 'unverifieduser']);
        $DB->set_field('user', 'auth', 'external', ['id' => $u1->id]);
        profile_load_data($u1);
        $u1->profile_field_external_user = 1;
        $u1->profile_field_external_user_verified = '0';
        $u1->profile_field_external_user_pending = '0';
        profile_save_data($u1);

        // 2. Pending user (unverified, pending=1, auth=external).
        $u2 = $this->getDataGenerator()->create_user(['username' => 'pendinguser']);
        $DB->set_field('user', 'auth', 'external', ['id' => $u2->id]);
        profile_load_data($u2);
        $u2->profile_field_external_user = 1;
        $u2->profile_field_external_user_verified = '0';
        $u2->profile_field_external_user_pending = '1';
        profile_save_data($u2);

        // 3. Already verified user (verified=1, auth=external).
        $u3 = $this->getDataGenerator()->create_user(['username' => 'verifieduser']);
        $DB->set_field('user', 'auth', 'external', ['id' => $u3->id]);
        profile_load_data($u3);
        $u3->profile_field_external_user = 1;
        $u3->profile_field_external_user_verified = '1';
        $u3->profile_field_external_user_pending = '0';
        profile_save_data($u3);

        // 4. Limited verified user (verified=date, auth=external).
        $u4 = $this->getDataGenerator()->create_user(['username' => 'limiteduser']);
        $DB->set_field('user', 'auth', 'external', ['id' => $u4->id]);
        profile_load_data($u4);
        $u4->profile_field_external_user = 1;
        $u4->profile_field_external_user_verified = '30.09.2027';
        $u4->profile_field_external_user_pending = '0';
        profile_save_data($u4);

        // 5. Rejected user (verified=-1, auth=external).
        $u5 = $this->getDataGenerator()->create_user(['username' => 'rejecteduser']);
        $DB->set_field('user', 'auth', 'external', ['id' => $u5->id]);
        profile_load_data($u5);
        $u5->profile_field_external_user = 1;
        $u5->profile_field_external_user_verified = '-1';
        $u5->profile_field_external_user_pending = '0';
        profile_save_data($u5);

        // 6. Manual user (should not appear in any list).
        $u6 = $this->getDataGenerator()->create_user(['username' => 'manualuser']);
        profile_load_data($u6);
        $u6->profile_field_external_user = 1;
        $u6->profile_field_external_user_verified = '0';
        $u6->profile_field_external_user_pending = '0';
        profile_save_data($u6);

        // Execute retrievers
        $forverification = $this->commonclass->get_users_for_verification();
        $pending = $this->commonclass->get_pending_users_for_verification();
        $alreadyverified = $this->commonclass->get_users_already_verified();
        $rejected = $this->commonclass->get_users_rejected();

        // Assert u1 is in for_verification
        $this->assertArrayHasKey($u1->id, $forverification);
        $this->assertArrayNotHasKey($u2->id, $forverification); // u2 is pending
        $this->assertArrayNotHasKey($u3->id, $forverification);
        $this->assertArrayNotHasKey($u4->id, $forverification);
        $this->assertArrayNotHasKey($u5->id, $forverification);
        $this->assertArrayNotHasKey($u6->id, $forverification); // u6 is manual

        // Assert u2 is in pending
        $this->assertArrayHasKey($u2->id, $pending);
        $this->assertArrayNotHasKey($u1->id, $pending);

        // Assert u3 and u4 are in already_verified
        $this->assertArrayHasKey($u3->id, $alreadyverified);
        $this->assertArrayHasKey($u4->id, $alreadyverified);
        $this->assertArrayNotHasKey($u1->id, $alreadyverified);
        
        // Check username suffix logic for limited user (L)
        $this->assertEquals('verifieduser', $alreadyverified[$u3->id]->username);
        $this->assertEquals('limiteduser (L)', $alreadyverified[$u4->id]->username);

        // Assert u5 is in rejected
        $this->assertArrayHasKey($u5->id, $rejected);
        $this->assertArrayNotHasKey($u1->id, $rejected);
    }

    /**
     * Test revoke_user function.
     * @covers \local_external_users\common::revoke_user
     */
    public function test_revoke_user(): void {
        global $DB;
        $this->resetAfterTest();

        // 1. Invalid user
        $this->assertEquals(-1, $this->commonclass->revoke_user(99999));

        // 2. Valid external user
        $user = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'auth', 'external', ['id' => $user->id]);
        profile_load_data($user);
        $user->profile_field_external_user = 1;
        $user->profile_field_external_user_verified = '1';
        $user->profile_field_external_user_pending = '0';
        $user->profile_field_eduPersonScopedAffiliation = 'tariff_a';
        profile_save_data($user);

        // Redirect events to assert event triggering
        $sink = $this->redirectEvents();

        $result = $this->commonclass->revoke_user($user->id);
        $this->assertEquals(0, $result);

        // Assert profile fields
        profile_load_data($user);
        $this->assertEquals(0, $user->profile_field_external_user_verified);
        $this->assertEquals(1, $user->profile_field_external_user_pending);
        $this->assertEquals('', $user->profile_field_eduPersonScopedAffiliation);

        // Assert event
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(\local_external_users\event\user_revoked::class, $event);
        $this->assertEquals($user->id, $event->relateduserid);
        $sink->close();
    }

    /**
     * Test limited_verify_user function.
     * @covers \local_external_users\common::limited_verify_user
     */
    public function test_limited_verify_user(): void {
        global $DB;
        $this->resetAfterTest();

        // 1. Invalid user
        $this->assertEquals(-1, $this->commonclass->limited_verify_user(99999, 'tariff_a', 'limited'));

        // 2. Valid external user - type 'limited' (current semester)
        $user = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'auth', 'external', ['id' => $user->id]);
        profile_load_data($user);
        $user->profile_field_external_user = 1;
        $user->profile_field_external_user_verified = '0';
        $user->profile_field_external_user_pending = '1';
        profile_save_data($user);

        $sink = $this->redirectEvents();
        $result = $this->commonclass->limited_verify_user($user->id, 'tariff_a', 'limited');
        $this->assertEquals(0, $result);

        profile_load_data($user);
        $expecteddate = $this->commonclass->getendofsemester();
        $this->assertEquals($expecteddate, $user->profile_field_external_user_verified);
        $this->assertEmpty($user->profile_field_external_user_pending);
        $this->assertEquals('tariff_a', $user->profile_field_eduPersonScopedAffiliation);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(\local_external_users\event\user_limitedapproved::class, $event);
        $this->assertEquals($user->id, $event->relateduserid);
        $sink->close();

        // 3. Valid external user - type 'limited2' (next semester)
        $user2 = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'auth', 'external', ['id' => $user2->id]);
        profile_load_data($user2);
        $user2->profile_field_external_user = 1;
        $user2->profile_field_external_user_verified = '0';
        $user2->profile_field_external_user_pending = '1';
        profile_save_data($user2);

        $sink2 = $this->redirectEvents();
        $result2 = $this->commonclass->limited_verify_user($user2->id, 'tariff_b', 'limited2');
        $this->assertEquals(0, $result2);

        profile_load_data($user2);
        $expectednextdate = $this->commonclass->getendofnextsemester();
        $this->assertEquals($expectednextdate, $user2->profile_field_external_user_verified);
        $this->assertEmpty($user2->profile_field_external_user_pending);
        $this->assertEquals('tariff_b', $user2->profile_field_eduPersonScopedAffiliation);

        $events2 = $sink2->get_events();
        $this->assertCount(1, $events2);
        $event2 = reset($events2);
        $this->assertInstanceOf(\local_external_users\event\user_limitedapproved::class, $event2);
        $this->assertEquals($user2->id, $event2->relateduserid);
        $sink2->close();
    }

    /**
     * Test file helper functions.
     * @covers \local_external_users\common::get_user_files
     * @covers \local_external_users\common::get_user_file_table
     * @covers \local_external_users\common::remove_user_files
     * @throws moodle_exception
     */
    public function test_file_operations(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $contextid = \context_system::instance()->id;

        // 1. Create a stored file in Moodle file storage
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => $contextid,
            'component' => 'local_external_users',
            'filearea' => 'somearea',
            'itemid' => $user->id, // Using user ID as itemid
            'filepath' => '/',
            'filename' => 'testfile.txt',
            'userid' => $user->id,
        ];
        $fs->create_file_from_string($filerecord, 'Hello World');

        // 2. Insert record into local_external_users_files table
        $dbfield = [
            'contextid' => (string)$contextid,
            'component' => 'local_external_users',
            'filearea' => 'somearea',
            'filepath' => '/',
            'userid' => $user->id,
            'filename' => 'testfile.txt',
        ];
        $dbid = $DB->insert_record('local_external_users_files', $dbfield);

        // 3. Test get_user_files
        $userfiles = $this->commonclass->get_user_files($user->id);
        $this->assertCount(1, $userfiles);
        $file = reset($userfiles);
        $this->assertEquals('testfile.txt', $file->filename);
        $this->assertEquals($user->id, $file->userid);

        // 4. Test get_user_file_table
        $filetable = $this->commonclass->get_user_file_table($user->id);
        $this->assertCount(1, $filetable);
        $html = reset($filetable);
        $this->assertStringContainsString('testfile.txt', $html);
        $this->assertStringContainsString('pluginfile.php', $html);

        // 5. Test remove_user_files
        $removed = $this->commonclass->remove_user_files($user->id);
        $this->assertEquals(1, $removed);

        // Assert database record was deleted
        $this->assertFalse($DB->record_exists('local_external_users_files', ['id' => $dbid]));

        // Assert file storage was deleted
        $this->assertFalse($fs->file_exists($contextid, 'local_external_users', 'somearea', $user->id, '/', 'testfile.txt'));
    }
}
