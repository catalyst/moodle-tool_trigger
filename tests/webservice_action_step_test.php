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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__.'/fixtures/user_event_fixture.php');

/**
 * Test of the Webservice action step.
 *
 * @package    tool_trigger
 * @author     Kevin Pham <kevinpham@catalyst-au.net>
 * @copyright  Catalyst IT, 2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_trigger_webservice_action_step_testcase extends \advanced_testcase {
    use \tool_trigger_user_event_fixture;

    /**
     * Create a "user_profile_viewed" event, of user1 viewing user2's
     * profile. And then run everything else as the cron user.
     */
    public function setup(): void {
        $this->setup_user_event();
    }

    /**
     * Simple test, with a successful result.
     */
    public function test_with_valid_call_to_enrol_user() {
        global $DB;

        $adminuser = get_admin();
        $stepsettings = [
            'username' => $adminuser->username,
            'functionname' => 'enrol_manual_enrol_users',
            'params' =>
                '{"enrolments":{"0":{"roleid":"5","userid":' . $this->user1->id . ',"courseid":' . $this->course->id . '}}}',
        ];

        // Ensure the user provided by the username is not actually 'logged in'
        // to perform the required actions.
        $this->assertEquals(0, $adminuser->lastaccess);
        $this->assertEquals(0, $adminuser->lastlogin);

        // Check if user is NOT enrolled yet.
        $context = context_course::instance($this->course->id);
        $enrolled = is_enrolled($context, $this->user1->id);
        $this->assertFalse($enrolled);

        $step = new \tool_trigger\steps\actions\webservice_action_step(json_encode($stepsettings));
        list($status, $stepresults) = $step->execute(null, null, $this->event, []);
        $this->assertTrue($status);
        $this->assertNotNull($stepresults);
        $this->assertArrayHasKey('data', $stepresults);
        $this->assertArrayNotHasKey('exception', $stepresults);

        // Check if user is now enrolled as expected, showing the call did indeed work as expected.
        $context = context_course::instance($this->course->id);
        $enrolled = is_enrolled($context, $this->user1->id);
        $this->assertTrue($enrolled);

        $user = $DB->get_record('user', ['id' => $adminuser->id, 'deleted' => 0]);
        $this->assertEquals(0, $user->lastaccess);
        $this->assertEquals(0, $user->lastlogin);
    }

    /**
     * Test when the username is not valid, so the step fails with an exception.
     */
    public function test_with_invalid_username() {
        $stepsettings = [
            'username' => 'tool_trigger_invalid_username',
            'functionname' => 'enrol_manual_enrol_users',
            'params' =>
                '{"enrolments":{"0":{"roleid":"5","userid":' . $this->user1->id . ',"courseid":' . $this->course->id . '}}}',
        ];
        $step = new \tool_trigger\steps\actions\webservice_action_step(json_encode($stepsettings));
        $this->expectException(dml_missing_record_exception::class);
        $step->execute(null, null, $this->event, []);
    }

    /**
     * Test with non_existent function
     */
    public function test_with_non_existent_function() {
        $adminuser = get_admin();
        $stepsettings = [
            'username' => $adminuser->username,
            'functionname' => 'tool_trigger_function_does_not_exist',
            'params' =>
                '{"enrolments":{"0":{"roleid":"5","userid":' . $this->user1->id . ',"courseid":' . $this->course->id . '}}}',
        ];
        $step = new \tool_trigger\steps\actions\webservice_action_step(json_encode($stepsettings));

        // Manually catch the exception to check the message
        $this->expectException('Error');
        $this->expectExceptionMessageRegExp('/external_functions/');
        $step->execute(null, null, $this->event, []);
    }

    /**
     * Test with invalid function parameters
     */
    public function test_with_invalid_function_parameters() {
        $adminuser = get_admin();
        $stepsettings = [
            'username' => $adminuser->username,
            'functionname' => 'enrol_manual_enrol_users',
            'params' =>
                '{"not_enrolments":{"0":{"roleid":"5","userid":' . $this->user1->id . ',"courseid":' . $this->course->id . '}}}',
        ];
        $step = new \tool_trigger\steps\actions\webservice_action_step(json_encode($stepsettings));

        $this->expectException('Error');
        // Setup the expectations for exception format.
        $this->expectExceptionMessageRegExp('/errorcode.*invalidparameter.*debuginfo.*enrolments/');
        $step->execute(null, null, $this->event, []);
    }
}
