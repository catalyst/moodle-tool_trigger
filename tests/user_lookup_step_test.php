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
 * "Fail" filter step's unit tests.
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__.'/fixtures/user_event_fixture.php');

class tool_trigger_user_lookup_testcase extends advanced_testcase {
    use \tool_trigger_user_event_fixture;

    /**
     * Create a "user_profile_viewed" event, of user1 viewing user2's
     * profile. And then run everything else as the cron user.
     */
    public function setup():void {
        $this->setup_user_event();
    }

    protected function tearDown(): void {
        $this->user1 = null;
        $this->user2 = null;
        $this->course = null;
        $this->event = null;
        parent::tearDown();
    }

    /**
     * Basic use-case, with default values for settings. Find the
     * user identified at "userid", and add their data with the
     * prefix "user_".
     */
    public function test_execute_basic() {
        $step = new \tool_trigger\steps\lookups\user_lookup_step(
            json_encode([
                'useridfield' => 'userid',
                'outputprefix' => 'user_',
                'nodeleted' => '1'
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $this->assertTrue($status);
        $this->assertEquals($this->user1->username, $stepresults['user_username']);
        $this->assertEquals($this->user1->email, $stepresults['user_email']);
    }

    /**
     * Basic test, but this time with additional custom profile field.
     */
    public function test_execute_basic_with_custom_profile_fields() {
        // Create user profile fields.
        $field1 = $this->add_user_custom_profile_field('testfield1', 'text');
        $field2 = $this->add_user_custom_profile_field('testfield2', 'text');

        // Populate data.
        profile_save_data((object)['id' => $this->user1->id, 'profile_field_' . $field1->shortname => 'User 1 Field Data']);

        $step = new \tool_trigger\steps\lookups\user_lookup_step(
            json_encode([
                'useridfield' => 'userid',
                'outputprefix' => 'user_',
                'nodeleted' => '1'
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $this->assertTrue($status);
        $this->assertEquals($this->user1->username, $stepresults['user_username']);
        $this->assertEquals($this->user1->email, $stepresults['user_email']);
        $this->assertEquals('User 1 Field Data', $stepresults['user_testfield1']);
        $this->assertSame(null, $stepresults['user_testfield2']);
    }

    /**
     * Non-default values for step's settings. Look for the relateduserid
     * (an optional field, for events that involve one user interacting
     * with another), and use a different prefix.
     */
    public function test_execute_relateduser() {
        $step = new \tool_trigger\steps\lookups\user_lookup_step(
            json_encode([
                'useridfield' => 'relateduserid',
                'outputprefix' => 'vieweduser_',
                'nodeleted' => '1'
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $this->assertTrue($status);
        $this->assertEquals($this->user2->username, $stepresults['vieweduser_username']);
        $this->assertEquals($this->user2->email, $stepresults['vieweduser_email']);
    }

    /**
     * Test for exception if an invalid field name is entered.
     */
    public function test_execute_nosuchfield() {
        $step = new \tool_trigger\steps\lookups\user_lookup_step(
            json_encode([
                'useridfield' => 'nosuchfield',
                'outputprefix' => 'user_',
                'nodeleted' => '1'
            ])
        );

        $this->expectException('\moodle_exception');
        $step->execute(null, null, $this->event, []);
    }

    /**
     * Test for failure if a user is no longer present in the database.
     */
    public function test_execute_deleted_user() {
        // Delete one of our users.
        delete_user($this->user1);

        // With the default "nodeleted = 1", we should see a false return status
        // if the user has been deleted.
        $step = new \tool_trigger\steps\lookups\user_lookup_step(
            json_encode([
                'useridfield' => 'userid',
                'outputprefix' => 'user_',
                'nodeleted' => '1'
            ])
        );

        list($status) = $step->execute(null, null, $this->event, []);
        $this->assertFalse($status);

        // With "nodeleted = 0", we should see a true return status, and data
        // about the deleted user.
        $step2 = new \tool_trigger\steps\lookups\user_lookup_step(
            json_encode([
                'useridfield' => 'userid',
                'outputprefix' => 'user_',
                'nodeleted' => '0'
            ])
        );
        list($status2, $stepresults2) = $step2->execute(null, null, $this->event, []);
        $this->assertTrue($status2);
        $this->assertArraySubset(
            [
                'user_id' => $this->user1->id,
                'user_deleted' => '1'
            ],
            $stepresults2
        );
    }
}