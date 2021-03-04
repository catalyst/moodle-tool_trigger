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
 * Privacy test for the event monitor
 *
 * @package    tool_trigger
 * @category   test
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \tool_trigger\privacy\provider;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;

/**
 * Privacy test for the event monitor
 *
 * @package    tool_trigger
 * @category   test
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_trigger_privacy_testcase extends advanced_testcase {

    /**
     * Set up method.
     */
    public function setUp():void {
        $this->resetAfterTest();
    }

    /**
     * Check that the correct userlist is returned if there is any user data for this context.
     */
    public function test_get_users_in_context() {
        global $DB;

        $component = 'tool_trigger';
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $usercontext = \context_user::instance($user->id);
        $usercontext2 = \context_user::instance($user2->id);

        $userlist = new \core_privacy\local\request\userlist($usercontext, $component);
        provider::get_users_in_context($userlist);
        $this->assertEmpty($userlist);

        $userlist = new \core_privacy\local\request\userlist($usercontext2, $component);
        provider::get_users_in_context($userlist);
        $this->assertEmpty($userlist);

        // Add test data to database.
        $eventrecord = new stdClass();
        $eventrecord->eventname = '\core\event\user_login_failed';
        $eventrecord->component = 'core';
        $eventrecord->action = 'failed';
        $eventrecord->target = 'user_login';
        $eventrecord->crud = 'r';
        $eventrecord->edulevel = '0';
        $eventrecord->contextid = '1';
        $eventrecord->contextlevel = '10';
        $eventrecord->contextinstanceid = '0';
        $eventrecord->userid = $user->id;
        $eventrecord->courseid = '0';
        $eventrecord->other = 'N;';
        $eventrecord->timecreated = '1568763148';
        $eventrecord->origin = 'web';
        $eventrecord->ip = '192.168.1.1';

        $DB->insert_record('tool_trigger_events', $eventrecord);
        $DB->insert_record('tool_trigger_learn_events', $eventrecord);

        $userlist = new \core_privacy\local\request\userlist($usercontext, $component);
        provider::get_users_in_context($userlist);

        // Check that we only get back user.
        $userids = $userlist->get_userids();
        $this->assertCount(1, $userlist);
        $this->assertEquals($user->id, $userids[0]);

    }

    /**
     * Test deleting user data for an approved userlist in a context.
     */
    public function test_delete_data_for_users() {
        global $DB;

        $component = 'tool_trigger';
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $usercontext = \context_user::instance($user->id);
        $usercontext2 = \context_user::instance($user2->id);

        $this->setUser($user);

        // Add test data to database.
        $eventrecord = new stdClass();
        $eventrecord->eventname = '\core\event\user_login_failed';
        $eventrecord->component = 'core';
        $eventrecord->action = 'failed';
        $eventrecord->target = 'user_login';
        $eventrecord->crud = 'r';
        $eventrecord->edulevel = '0';
        $eventrecord->contextid = '1';
        $eventrecord->contextlevel = '10';
        $eventrecord->contextinstanceid = '0';
        $eventrecord->userid = $user->id;
        $eventrecord->courseid = '0';
        $eventrecord->other = 'N;';
        $eventrecord->timecreated = '1568763148';
        $eventrecord->origin = 'web';
        $eventrecord->ip = '192.168.1.1';

        $DB->insert_record('tool_trigger_events', $eventrecord);
        $DB->insert_record('tool_trigger_learn_events', $eventrecord);

        // Delete for user2, should have no effect.
        $approveduserids = [$user2->id];
        $approvedlist = new approved_userlist($usercontext, $component, $approveduserids);
        provider::delete_data_for_users($approvedlist);

        $dbrules = $DB->get_records('tool_trigger_events');
        $this->assertCount(1, $dbrules);

        $dbrules = $DB->get_records('tool_trigger_learn_events');
        $this->assertCount(1, $dbrules);

        // Delete for user1.
        $approveduserids = [$user->id];
        $approvedlist = new approved_userlist($usercontext, $component, $approveduserids);
        provider::delete_data_for_users($approvedlist);

        // Should be no more records now.
        $dbrules = $DB->get_records('tool_trigger_events');
        $this->assertEmpty($dbrules);

        $dbrules = $DB->get_records('tool_trigger_learn_events');
        $this->assertEmpty($dbrules);

    }
}
