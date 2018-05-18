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

class tool_trigger_user_lookup_testcase extends advanced_testcase {
    /**
     * Create a "user_profile_viewed" event, of user1 viewing user2's
     * profile. And then run everything else as the cron user.
     */
    public function setup() {
        $this->resetAfterTest(true);
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();

        $this->setUser($this->user1);

        $this->event = \core\event\user_profile_viewed::create([
            'objectid' => $this->user2->id,
            'relateduserid' => $this->user2->id,
            'context' => context_user::instance($this->user2->id)
        ]);

        $this->event->trigger();

        // Run as the cron user  .
        cron_setup_user();
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
                'outputprefix' => 'user_'
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $this->assertTrue($status);
        $this->assertEquals($this->user1->username, $stepresults['user_username']);
        $this->assertEquals($this->user1->email, $stepresults['user_email']);
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
                'outputprefix' => 'vieweduser_'
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
                'outputprefix' => 'user_'
            ])
        );

        $this->expectException('\moodle_exception');
        $step->execute(null, null, $this->event, []);
    }

    /**
     * Test for failure if a user is no longer present in the database.
     */
    public function test_execute_nosuchuser() {
        delete_user($this->user1);

        $step = new \tool_trigger\steps\lookups\user_lookup_step(
            json_encode([
                'useridfield' => 'userid',
                'outputprefix' => 'user_'
            ])
        );

        list($status) = $step->execute(null, null, $this->event, []);
        $this->assertFalse($status);
    }
}