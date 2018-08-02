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
 * Event processor unit tests.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Event processor unit tests.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class tool_trigger_event_processor_testcase extends advanced_testcase {

    public function setup() {
        global $DB;
        $this->resetAfterTest(true);

        // Create an event. This _is_ easier to do via direct DB insertions.
        $user = $this->getDataGenerator()->create_user();
        $context = \context_system::instance();

        $eventarr = array(
        'objectid' => $user->id,
        'contextid' => $context->id,
        'userid' => $user->id,
        'courseid' => 0,
        'relateduserid' => null,
        'anonymous' => 0,
        'other' => ['username' => $user->username],

        );

        $this->eventarr = $eventarr;
        $this->user = $user;
        $this->context = $context;

        // Run as the cron user.
        cron_setup_user();

    }

    /**
     * Helper function to create a test workflow.
     *
     * @return int $workflowid The id of the created workflow.
     */
    public function create_workflow() {
        $mdata = new \stdClass();
        $mdata->workflowid = 0;
        $mdata->workflowname = 'Email me about login';
        $mdata->workflowdescription = 'When a user logs in, email me.';
        $mdata->eventtomonitor = '\core\event\user_loggedin';
        $mdata->workflowactive = 1;
        $mdata->draftmode = 0;
        $mdata->isstepschanged = 1;
        $mdata->stepjson = json_encode([
                [
                        'id' => 0,
                        'type' => 'lookups',
                        'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                        'steporder' => '0',
                        'name' => 'Get user data',
                        'description' => 'Get user data',
                        'useridfield' => 'userid',
                        'outputprefix' => 'user_'
                ],
                [
                        'id' => 0,
                        'type' => 'actions',
                        'stepclass' => '\tool_trigger\steps\actions\email_action_step',
                        'steporder' => '1',
                        'name' => 'Email user data to me',
                        'description' => 'Email user data to me',
                        'emailto' => \core_user::get_user_by_username('admin')->email,
                        'emailsubject' => '{user_firstname} {user_lastname} logged in',
                        'emailcontent' => '{user_email} logged in.'
                ]
        ]);

        // Insert it into the database. (It seems like it'll be more robust to do this
        // by calling workflow_process rather than doing it by hand.)
        $workflowprocess = new \tool_trigger\workflow_process($mdata);
        $workflowid = $workflowprocess->processform();

        return $workflowid;
    }

    /**
     * Test is event ignored.
     * Test event with no associated workflow is ignored.
     */
    public function test_is_event_ignored() {

        $event = \core\event\user_loggedin::create($this->eventarr);

        // We're testing a protected method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\event_processor', 'is_event_ignored');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \tool_trigger\event_processor, $event); // Get result of invoked method.

        $this->assertTrue($proxy);
    }

    /**
     * Test is event ignored.
     * Test event with no associated workflow is NOT ignored.
     */
    public function test_is_event_ignored_false() {

        $this->create_workflow(); // Add a workflow to the database.
        $event = \core\event\user_loggedin::create($this->eventarr);

        // We're testing a protected method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\event_processor', 'is_event_ignored');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \tool_trigger\event_processor, $event); // Get result of invoked method.

        $this->assertFalse($proxy);
    }

    /**
     * Test is prepare event data when learning mode is false.
     */
    public function test_prepare_event() {
        $event = \core\event\user_loggedin::create($this->eventarr);

        // We're testing a protected method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\event_processor', 'prepare_event');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \tool_trigger\event_processor, $event); // Get result of invoked method.
        $expected = unserialize($proxy['other']);

        $this->assertEquals($this->eventarr['other'], $expected);
    }

    /**
     * Test processing event.
     * Ensure details for a non ignored event end up in database.
     */
    public function test_process_event() {

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);
    }

}