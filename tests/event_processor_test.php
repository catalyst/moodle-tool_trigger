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

require_once('tool_trigger_testcase.php');

/**
 * Event processor unit tests.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class tool_trigger_event_processor_testcase extends tool_trigger_testcase {

    public function setup():void {
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

    public function tearDown():void {
        global $DB;
        // Manually clear all related DB tables. Avoids voodoo failing tests.
        $DB->delete_records('tool_trigger_run_hist', []);
        $DB->delete_records('tool_trigger_workflow_hist', []);
        $DB->delete_records('tool_trigger_steps', []);
        $DB->delete_records('tool_trigger_workflows', []);

        // Purge caches that may cause issues with events being ignored.
        \cache_helper::purge_by_definition('tool_trigger', 'eventsubscriptions');
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
    public function test_process_event_add_event_to_db() {
        global $DB;
        $this->create_workflow();

        $this->assertEmpty($DB->get_records('tool_trigger_events'));

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        $this->assertCount(1, $DB->get_records('tool_trigger_events'));
    }

    /**
     * Test processing real time event.
     * Ensure realtime event processed and timetriggered updated in DB.
     */
    public function test_process_realtime_workflow() {
        global $DB;

        $now = time();

        $workflowid = $this->create_workflow(1);
        $timetriggered = $DB->get_field('tool_trigger_workflows', 'timetriggered', ['id' => $workflowid]);

        $this->assertEmpty($timetriggered);
        $this->assertEmpty($DB->get_records('tool_trigger_events'));

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        $this->assertCount(1, $DB->get_records('tool_trigger_events'));
        $this->assertCount(0, $DB->get_records('tool_trigger_queue'));
        $timetriggered = $DB->get_field('tool_trigger_workflows', 'timetriggered', ['id' => $workflowid]);
        $this->assertGreaterThanOrEqual($now, $timetriggered);
    }

    /**
     * Test processing real time event with an error will add a message to a queue to process later.
     */
    public function test_process_realtime_workflow_save_to_queue_if_failed() {
        global $DB;

        $now = time();
        $steps = [
            [
                'id' => 0,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '0',
                'name' => 'Get user data',
                'description' => 'Get user data',
                'useridfield' => 'broken_field', // This should trigger exception on look up step.
                'outputprefix' => 'user_'
            ],
        ];

        $workflowid = $this->create_workflow(1, $steps);
        $this->assertEmpty($DB->get_records('tool_trigger_events'));
        $this->assertEmpty($DB->get_records('tool_trigger_queue'));

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        // We should see debugging call with the failed step.
        $this->assertDebuggingCalledCount(1);

        $this->assertCount(1, $DB->get_records('tool_trigger_events'));
        $this->assertCount(1, $DB->get_records('tool_trigger_queue'));
        $timetriggered = $DB->get_field('tool_trigger_workflows', 'timetriggered', ['id' => $workflowid]);
        $this->assertGreaterThanOrEqual($now, $timetriggered);
    }

    public function test_record_workflow_trigger() {
        // Perform basic workflow setup, with debug mode disabled.
        global $DB;
        $workflowid = $this->create_workflow(1);
        $now = time();

        $timetriggered = $DB->get_field('tool_trigger_workflows', 'timetriggered', ['id' => $workflowid]);

        $this->assertEmpty($timetriggered);
        $this->assertEmpty($DB->get_records('tool_trigger_events'));

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        // Check that nothing was logged to the history tables.
        $countwfhist = $DB->count_records('tool_trigger_workflow_hist', []);
        $this->assertEquals(0, $countwfhist);

        // Now create a new indentical WF, and check that only 1 set of events is logged.
        $workflowid2 = $this->create_workflow(1, [], 1);
        \tool_trigger\event_processor::process_event($event);
        $countwfhist = $DB->count_records('tool_trigger_workflow_hist', []);
        $this->assertEquals(1, $countwfhist);

        // Lets check the shape of the data.
        $histrecord = $DB->get_record('tool_trigger_workflow_hist', ['workflowid' => $workflowid2]);
        $this->assertTrue($now <= $histrecord->timecreated);
        $this->assertEquals('\core\event\user_loggedin', json_decode($histrecord->event, true)['eventname']);
        $this->assertEquals(1, $histrecord->number);

        // Now test that even with a broken set of steps, the workflow trigger is still recorded.
        $brokensteps = [
            [
                'id' => 0,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '0',
                'name' => 'Get user data',
                'description' => 'Get user data',
                'useridfield' => 'broken_field', // This should trigger exception on look up step.
                'outputprefix' => 'user_'
            ],
        ];
        // Now create a new indentical WF, and check that only 1 set of events is logged.
        $workflowid3 = $this->create_workflow(1, $brokensteps, 1);
        \tool_trigger\event_processor::process_event($event);

        // We should see debugging call with the failed step.
        $this->assertDebuggingCalledCount(1);

        // Now check the status of the trigger run history is the same.
        $histrecord2 = $DB->get_record('tool_trigger_workflow_hist', ['workflowid' => $workflowid3]);
        $this->assertTrue($now <= $histrecord2->timecreated);
        $this->assertEquals('\core\event\user_loggedin', json_decode($histrecord2->event, true)['eventname']);
        $this->assertEquals(1, $histrecord2->number);
    }

    public function test_record_step_trigger() {
        // Perform basic workflow setup, with debug mode disabled.
        global $DB;
        $this->create_workflow(1);
        $now = time();

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        // Check that nothing was logged to the history tables.
        $countrunhist = $DB->count_records('tool_trigger_run_hist', []);
        $this->assertEquals(0, $countrunhist);

        $steps = [
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
                'id' => 1,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '1',
                'name' => 'Get user data2',
                'description' => 'Get user data2',
                'useridfield' => 'userid',
                'outputprefix' => 'user2_'
            ]
        ];

        // Now create a workflow with debug mode enabled.
        $workflowid = $this->create_workflow(1, $steps, 1);
        \tool_trigger\event_processor::process_event($event);

        $countrunhist2 = $DB->count_records('tool_trigger_run_hist', []);
        $this->assertEquals(2, $countrunhist2);

        // Lets check the shape of the data (we only care about the specific historical fields).
        $runid = $DB->get_field('tool_trigger_workflow_hist', 'id', ['workflowid' => $workflowid]);
        $records = $DB->get_records('tool_trigger_run_hist', []);
        $firststep = reset($records);
        $this->assertEquals($workflowid, $firststep->workflowid);
        $this->assertEquals($runid, $firststep->runid);
        $this->assertTrue($now <= $firststep->executed);
        $this->assertEquals(0, $firststep->number);
        $results = json_decode($firststep->results);
        $this->assertEquals('username1@example.com', $results->user_email);

        // Now check the second event, examine it is tied to previous step correctly.
        $laststep = end($records);
        $this->assertEquals($firststep->id, $laststep->prevstepid);
        $this->assertEquals(1, $laststep->number);

        // Clear out DB tables before moving on.
        $DB->delete_records('tool_trigger_run_hist', []);
        $DB->delete_records('tool_trigger_workflow_hist', []);
        $DB->delete_records('tool_trigger_workflows', []);

        // Now we will execute a workflow with 1 good step and 1 broken, and check only the good step is recorded.
        $brokensteps = [
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
                'id' => 1,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '1',
                'name' => 'Get user data2',
                'description' => 'Get user data2',
                'useridfield' => 'broken_field',
                'outputprefix' => 'user2_'
            ]
        ];

        $this->create_workflow(1, $brokensteps, 1);
        \tool_trigger\event_processor::process_event($event);
        $this->assertDebuggingCalled();

        $countrunhist3 = $DB->count_records('tool_trigger_run_hist');
        $this->assertEquals(1, $countrunhist3);
    }

    public function test_execute_current_step() {
        // Perform basic workflow setup, with debug mode enabled.
        global $DB;
        $steps = [
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
                'id' => 1,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '1',
                'name' => 'Get user data2',
                'description' => 'Get user data2',
                'useridfield' => 'userid',
                'outputprefix' => 'user2_'
            ]
        ];
        $this->create_workflow(1, $steps, 1);

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        // Now get the step records needed.
        $origrecords = $DB->get_records('tool_trigger_run_hist', []);
        $origstep = reset($origrecords);

        $secondstep = end($origrecords);

        // Manually update the descriptions of the steps.
        $DB->set_field('tool_trigger_steps', 'description', 'New description', ['type' => 'lookups']);

        // Now rerun the step from the id, and get the new repeated step record.
        \tool_trigger\event_processor::execute_current_step($origstep->id);
        $records = $DB->get_records('tool_trigger_run_hist', []);
        $this->assertEquals(3, count($records));
        $newstep = end($records);

        // Check fields line up.
        $this->assertEquals($origstep->runid, $newstep->runid);
        $this->assertEquals($origstep->workflowid, $newstep->workflowid);
        $this->assertTrue($origstep->executed <= $newstep->executed);
        $this->assertEquals($origstep->results, $newstep->results);
        $this->assertEquals($origstep->prevstepid, $newstep->prevstepid);
        // Check that the descriptions do not line up.
        $this->assertNotEquals($origstep->description, $newstep->description);

        // Now rerun the second step, and ensure the prevstepids line up.
        \tool_trigger\event_processor::execute_current_step($secondstep->id);
        $records = $DB->get_records('tool_trigger_run_hist', []);
        $this->assertEquals(4, count($records));
        $newstep = end($records);

        $this->assertEquals($secondstep->prevstepid, $newstep->prevstepid);
        // Check that these descriptions also don't line up.
        $this->assertNotEquals($secondstep->description, $newstep->description);
    }

    public function test_execute_historic_step() {
        // Perform basic workflow setup, with debug mode enabled.
        global $DB;
        $steps = [
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
                'id' => 1,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '1',
                'name' => 'Get user data2',
                'description' => 'Get user data2',
                'useridfield' => 'userid',
                'outputprefix' => 'user2_'
            ]
        ];
        $this->create_workflow(1, $steps, 1);

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        // Now get the step records needed.
        $origrecords = $DB->get_records('tool_trigger_run_hist', []);
        $origstep = reset($origrecords);

        $secondstep = end($origrecords);

        // Now rerun the step from the id, and get the new repeated step record.
        \tool_trigger\event_processor::execute_historic_step($origstep->id);
        $records = $DB->get_records('tool_trigger_run_hist', []);
        $this->assertEquals(3, count($records));
        $newstep = end($records);

        // Check fields line up.
        $this->assertEquals($origstep->runid, $newstep->runid);
        $this->assertEquals($origstep->workflowid, $newstep->workflowid);
        $this->assertTrue($origstep->executed <= $newstep->executed);
        $this->assertEquals($origstep->results, $newstep->results);
        $this->assertEquals($origstep->prevstepid, $newstep->prevstepid);

        // Now rerun the second step, and ensure the prevstepids line up.
        \tool_trigger\event_processor::execute_historic_step($secondstep->id);
        $records = $DB->get_records('tool_trigger_run_hist', []);
        $this->assertEquals(4, count($records));
        $newstep = end($records);

        $this->assertEquals($secondstep->prevstepid, $newstep->prevstepid);
    }

    public function test_execute_next_step_current() {
        // Perform basic workflow setup, with debug mode enabled.
        global $DB;
        $steps = [
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
                'id' => 1,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '1',
                'name' => 'Get user data2',
                'description' => 'Get user data2',
                'useridfield' => 'userid',
                'outputprefix' => 'user2_'
            ]
        ];
        $this->create_workflow(1, $steps, 1);

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        // Now get the step records needed.
        $origrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $origstep = reset($origrecords);
        $secondstep = end($origrecords);

        // Manually update the descriptions of the steps.
        $DB->set_field('tool_trigger_steps', 'description', 'New description', ['type' => 'lookups']);

        // Test that executing on the last step does nothing.
        \tool_trigger\event_processor::execute_next_step_current($secondstep->id);
        $secondrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(count($origrecords), count($secondrecords));

        // Now rerun first step, and check that the new record matches the second step.
        \tool_trigger\event_processor::execute_next_step_current($origstep->id);
        $thirdrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $thirdstep = end($thirdrecords);

        // Check fields line up.
        $this->assertEquals($secondstep->runid, $thirdstep->runid);
        $this->assertEquals($secondstep->workflowid, $thirdstep->workflowid);
        $this->assertEquals($secondstep->results, $thirdstep->results);
        $this->assertEquals($secondstep->prevstepid, $thirdstep->prevstepid);
        // Check that the descriptions are not equal.
        $this->assertNotEquals($secondstep->description, $thirdstep->description);
    }

    public function test_execute_next_step_historic() {
        // Perform basic workflow setup, with debug mode enabled.
        global $DB;
        $steps = [
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
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '1',
                'name' => 'Get user data2',
                'description' => 'Get user data2',
                'useridfield' => 'userid',
                'outputprefix' => 'user2_'
            ]
        ];
        $this->create_workflow(1, $steps, 1);

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        // Now get the step records needed.
        $origrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $origstep = reset($origrecords);
        $secondstep = end($origrecords);

        // Test that executing on the last step does nothing.
        \tool_trigger\event_processor::execute_next_step_historic($secondstep->id);
        $secondrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(count($origrecords), count($secondrecords));

        // Now rerun first step, and check that the new record matches the second step.
        \tool_trigger\event_processor::execute_next_step_historic($origstep->id);
        $thirdrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $thirdstep = end($thirdrecords);

        // Check fields line up.
        $this->assertEquals($secondstep->runid, $thirdstep->runid);
        $this->assertEquals($secondstep->workflowid, $thirdstep->workflowid);
        $this->assertEquals($secondstep->results, $thirdstep->results);
        $this->assertEquals($secondstep->prevstepid, $thirdstep->prevstepid);
    }

    public function test_execute_step_and_continue_current() {
        // Perform basic workflow setup, with debug mode enabled.
        global $DB;
        $steps = [
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
                'id' => 1,
                'type' => 'actions',
                'stepclass' => '\tool_trigger\steps\actions\email_action_step',
                'steporder' => '1',
                'name' => 'Emailuser1',
                'description' => 'Email1',
                'emailto' => 'testusernotinmoodle@example.com',
                'emailsubject' => 'Subject of the email',
                'emailcontent_editor[text]' => 'Content of the email',
                'emailcontent_editor[format]' => 0
            ]
        ];
        $this->create_workflow(1, $steps, 1);

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        // Now get the step records needed.
        $origrecords = $DB->get_records('tool_trigger_run_hist', []);
        $origstep = reset($origrecords);
        $secondstep = end($origrecords);

        // Now manually update the description to be different.
        $DB->set_field('tool_trigger_steps', 'description', 'New description', ['type' => 'actions']);

        // Test that executing on the last step only replays the last step.
        \tool_trigger\event_processor::execute_step_and_continue_current($secondstep->id);
        $secondrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(3, count($secondrecords));
        $thirdstep = end($secondrecords);

        // Check the fields line up.
        $this->assertEquals($secondstep->runid, $thirdstep->runid);
        $this->assertEquals($secondstep->workflowid, $thirdstep->workflowid);
        $this->assertEquals($secondstep->prevstepid, $thirdstep->prevstepid);
        // The description will be different however, based off the new config.
        $this->assertNotEquals($secondstep->description, $thirdstep->description);
        $this->assertNotEquals($secondstep->results, $thirdstep->results);
        $this->assertEquals('New description', $thirdstep->description);

        // Now execute on the first step and check 2 steps rerun.
        \tool_trigger\event_processor::execute_step_and_continue_current($origstep->id);
        $thirdrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(5, count($thirdrecords));
        $fifthstep = end($thirdrecords);
        $fourthstep = prev($thirdrecords);

        // Check 1 lines with 4, 3 lines with 5 (uses most recent run of next step).
        $this->assertEquals($origstep->runid, $fourthstep->runid);
        $this->assertEquals($origstep->workflowid, $fourthstep->workflowid);
        $this->assertEquals($origstep->prevstepid, $fourthstep->prevstepid);
        $this->assertEquals($origstep->results, $fourthstep->results);
        $this->assertEquals($origstep->description, $fourthstep->description);

        $this->assertEquals($thirdstep->runid, $fifthstep->runid);
        $this->assertEquals($thirdstep->workflowid, $fifthstep->workflowid);
        // Check that the descriptions do now line up.
        $this->assertNotEquals($thirdstep->results, $fifthstep->results);
        $this->assertEquals($thirdstep->description, $fifthstep->description);
        // Special case, will point to Step 4 as previous step, rather than Step 1 as 3 does.
        $this->assertEquals($fourthstep->id, $fifthstep->prevstepid);

        // Now clear all tables and workflows, going to test a longer run, and finishing workflow.
        $DB->delete_records('tool_trigger_run_hist', []);
        $DB->delete_records('tool_trigger_workflow_hist', []);
        $DB->delete_records('tool_trigger_workflows', []);

        $longsteps = [
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
                'id' => 1,
                'type' => 'actions',
                'stepclass' => '\tool_trigger\steps\actions\email_action_step',
                'steporder' => '1',
                'name' => 'Emailuser1',
                'description' => 'Email1',
                'emailto' => 'testusernotinmoodle@example.com',
                'emailsubject' => 'Subject of the email',
                'emailcontent_editor[text]' => 'Content of the email',
                'emailcontent_editor[format]' => 0
            ],
            [
                'id' => 2,
                'type' => 'actions',
                'stepclass' => '\tool_trigger\steps\actions\email_action_step',
                'steporder' => '2',
                'name' => 'Emailuser2',
                'description' => 'Email1',
                'emailto' => 'testusernotinmoodle@example.com',
                'emailsubject' => 'Subject of the email',
                'emailcontent_editor[text]' => 'Content of the email',
                'emailcontent_editor[format]' => 0
            ],
            [
                'id' => 3,
                'type' => 'actions',
                'stepclass' => '\tool_trigger\steps\actions\email_action_step',
                'steporder' => '3',
                'name' => 'Emailuser3',
                'description' => 'Email3',
                'emailto' => 'testusernotinmoodle@example.com',
                'emailsubject' => 'Subject of the email',
                'emailcontent_editor[text]' => 'Content of the email',
                'emailcontent_editor[format]' => 0
            ]
        ];
        $this->create_workflow(1, $longsteps, 1);
        \tool_trigger\event_processor::process_event($event);

        // Check history has 4 steps.
        $firstrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(4, count($firstrecords));

        // Manually modify the steps in the table, and change email content.
        $DB->set_field('tool_trigger_steps', 'description', 'New description', ['type' => 'actions']);

        // Now rerun and next on step 1, and check only 2 records added.
        $firststep = reset($firstrecords);
        \tool_trigger\event_processor::execute_step_and_continue_current($firststep->id);
        $secondrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(6, count($secondrecords));

        // Now clear this table, and trigger a new run.
        $DB->delete_records('tool_trigger_run_hist', []);
        \tool_trigger\event_processor::process_event($event);
        $thirdrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(4, count($firstrecords));
        $firststep = reset($thirdrecords);

        // Now rerun step 1, and complete the run (total 8 records).
        \tool_trigger\event_processor::execute_step_and_continue_current($firststep->id, true);
        $fouthrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(8, count($fouthrecords));
    }

    public function test_execute_step_and_continue_historic() {
        // Perform basic workflow setup, with debug mode enabled.
        global $DB;
        $steps = [
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
                'id' => 1,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '1',
                'name' => 'Get user data2',
                'description' => 'Get user data2',
                'useridfield' => 'userid',
                'outputprefix' => 'user2_'
            ]
        ];
        $this->create_workflow(1, $steps, 1);

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        // Now get the step records needed.
        $origrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $origstep = reset($origrecords);
        $secondstep = end($origrecords);

        // Test that executing on the last step only replays the last step.
        \tool_trigger\event_processor::execute_step_and_continue_historic($secondstep->id);
        $secondrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(3, count($secondrecords));
        $thirdstep = end($secondrecords);

        // Check the fields line up.
        $this->assertEquals($secondstep->runid, $thirdstep->runid);
        $this->assertEquals($secondstep->workflowid, $thirdstep->workflowid);
        $this->assertEquals($secondstep->results, $thirdstep->results);
        $this->assertEquals($secondstep->prevstepid, $thirdstep->prevstepid);

        // Now execute on the first step and check 2 steps rerun.
        \tool_trigger\event_processor::execute_step_and_continue_historic($origstep->id);
        $thirdrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(5, count($thirdrecords));
        $fifthstep = end($thirdrecords);
        $fourthstep = prev($thirdrecords);

        // Check 1 lines with 4, 3 lines with 5 (uses most recent run of next step).
        $this->assertEquals($origstep->runid, $fourthstep->runid);
        $this->assertEquals($origstep->workflowid, $fourthstep->workflowid);
        $this->assertEquals($origstep->results, $fourthstep->results);
        $this->assertEquals($origstep->prevstepid, $fourthstep->prevstepid);

        $this->assertEquals($thirdstep->runid, $fifthstep->runid);
        $this->assertEquals($thirdstep->workflowid, $fifthstep->workflowid);
        $this->assertEquals($thirdstep->results, $fifthstep->results);
        // Special case, will point to Step 4 as previous step, rather than Step 1 as 3 does.
        $this->assertEquals($fourthstep->id, $fifthstep->prevstepid);

        // Now clear all tables and workflows, going to test a longer run, and finishing workflow.
        $DB->delete_records('tool_trigger_run_hist', []);
        $DB->delete_records('tool_trigger_workflow_hist', []);
        $DB->delete_records('tool_trigger_workflows', []);

        $longsteps = [
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
                'id' => 1,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '1',
                'name' => 'Get user data2',
                'description' => 'Get user data2',
                'useridfield' => 'userid',
                'outputprefix' => 'user2_'
            ],
            [
                'id' => 2,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '2',
                'name' => 'Get user data3',
                'description' => 'Get user data3',
                'useridfield' => 'userid',
                'outputprefix' => 'user3_'
            ],
            [
                'id' => 3,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '3',
                'name' => 'Get user data4',
                'description' => 'Get user data4',
                'useridfield' => 'userid',
                'outputprefix' => 'user4_'
            ]
        ];
        $this->create_workflow(1, $longsteps, 1);
        \tool_trigger\event_processor::process_event($event);

        // Check history has 4 steps.
        $firstrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(4, count($firstrecords));

        // Now rerun and next on step 1, and check only 2 records added.
        $firststep = reset($firstrecords);
        \tool_trigger\event_processor::execute_step_and_continue_historic($firststep->id);
        $secondrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(6, count($secondrecords));

        // Now clear this table, and trigger a new run.
        $DB->delete_records('tool_trigger_run_hist', []);
        \tool_trigger\event_processor::process_event($event);
        $thirdrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(4, count($firstrecords));
        $firststep = reset($thirdrecords);

        // Now rerun step 1, and complete the run (total 8 records).
        \tool_trigger\event_processor::execute_step_and_continue_historic($firststep->id, true);
        $fouthrecords = $DB->get_records('tool_trigger_run_hist', [], 'id ASC');
        $this->assertEquals(8, count($fouthrecords));
    }

    public function test_execute_workflow_from_event_current() {
        // Perform basic workflow setup, with debug mode enabled.
        global $DB;
        $steps = [
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
                'id' => 1,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '1',
                'name' => 'Get user data2',
                'description' => 'Get user data2',
                'useridfield' => 'userid',
                'outputprefix' => 'user2_'
            ]
        ];
        $this->create_workflow(1, $steps, 1);

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        // Now get the run record just executed.
        $firstrun = $DB->get_record('tool_trigger_workflow_hist', []);

        // Now manually update the description to be different.
        $DB->set_field('tool_trigger_steps', 'description', 'New description', ['type' => 'lookups']);

        // Now rerun and compare the 2 runs.
        \cache_helper::purge_by_definition('tool_trigger', 'eventsubscriptions');
        \tool_trigger\event_processor::execute_workflow_from_event_current($firstrun->id);
        $records = $DB->get_records('tool_trigger_workflow_hist', [], 'id ASC');
        $secondrun = end($records);

        // Check the fields of the runs.
        $this->assertEquals($firstrun->workflowid, $secondrun->workflowid);
        $this->assertEquals($firstrun->event, $secondrun->event);
        // Check number is incremented.
        $this->assertEquals($firstrun->number + 1, $secondrun->number);

        $firstrun = $DB->get_records('tool_trigger_run_hist', ['runid' => $firstrun->id], 'id ASC');
        $secondrun = $DB->get_records('tool_trigger_run_hist', ['runid' => $secondrun->id], 'id ASC');

        // Now check the event count for the 2 runs.
        $this->assertEquals(count($firstrun), count($secondrun));

        // Now compare the first events from each, and compare the fields.
        $firststep = reset($firstrun);
        $secondstep = reset($secondrun);

        // Check the description fields are different.
        $this->assertNotEquals($firststep->description, $secondstep->description);
        $this->assertEquals('New description', $secondstep->description);
    }

    public function test_execute_workflow_from_event_historic() {
        // Perform basic workflow setup, with debug mode enabled.
        global $DB;
        $steps = [
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
                'id' => 1,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '1',
                'name' => 'Get user data2',
                'description' => 'Get user data2',
                'useridfield' => 'userid',
                'outputprefix' => 'user2_'
            ]
        ];
        $this->create_workflow(1, $steps, 1);

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        // Now get the run record just executed.
        $firstrun = $DB->get_record('tool_trigger_workflow_hist', []);

        // Now rerun and compare the 2 runs.
        \cache_helper::purge_by_definition('tool_trigger', 'eventsubscriptions');
        \tool_trigger\event_processor::execute_workflow_from_event_historic($firstrun->id);
        $records = $DB->get_records('tool_trigger_workflow_hist', [], 'id ASC');
        $secondrun = end($records);

        // Check the fields of the runs.
        $this->assertEquals($firstrun->workflowid, $secondrun->workflowid);
        $this->assertEquals($firstrun->event, $secondrun->event);
        // Check number is incremented.
        $this->assertEquals($firstrun->number + 1, $secondrun->number);

        // Now check the event count for the 2 runs.
        $firstruncount = $DB->count_records('tool_trigger_run_hist', ['runid' => $firstrun->id]);
        $secondruncount = $DB->count_records('tool_trigger_run_hist', ['runid' => $secondrun->id]);
        $this->assertEquals($firstruncount, $secondruncount);
    }

    public function test_rerun_all_error_runs() {
        global $DB;

        set_config('historyduration', 14 * DAYSECS, 'tool_trigger');

        // Perform basic workflow setup, with debug mode enabled.
        $goodsteps = [
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
                'id' => 1,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '1',
                'name' => 'Get user data2',
                'description' => 'Get user data2',
                'useridfield' => 'userid',
                'outputprefix' => 'user2_'
            ]
        ];
        $wfid = $this->create_workflow(1, $goodsteps, 1);

        $event = \core\event\user_loggedin::create($this->eventarr);
        \tool_trigger\event_processor::process_event($event);

        // Now get the run record just executed.
        $firstruns = $DB->get_records('tool_trigger_workflow_hist', [], 'id ASC');

        \cache_helper::purge_by_definition('tool_trigger', 'eventsubscriptions');
        \tool_trigger\event_processor::rerun_all_error_runs($wfid);

        // Confirm that the run wasn't re-executed, as there was no error.
        $secondruns = $DB->get_records('tool_trigger_workflow_hist', [], 'id ASC');

        $this->assertEquals(count($firstruns), count($secondruns));
        $first = reset($firstruns);
        $second = reset($secondruns);
        $this->assertEquals($first->id, $second->id);

        $DB->delete_records('tool_trigger_workflow_hist', [], 'id ASC');
        $DB->delete_records('tool_trigger_run_hist', [], 'id ASC');
        $DB->delete_records('tool_trigger_workflows', [], 'id ASC');

        // Now confirm this behaviour for a failed run (not errored!).
        $failsteps = [
            [
                'id' => 0,
                'type' => 'filters',
                'stepclass' => '\tool_trigger\steps\filters\numcompare_filter_step',
                'steporder' => '0',
                'field1' => 123,
                'operator' => '==',
                'field2' => 456,
            ]
        ];

        // New workflow with these steps.
        $wfid = $this->create_workflow(1, $failsteps, 1);
        \cache_helper::purge_by_definition('tool_trigger', 'eventsubscriptions');
        \tool_trigger\event_processor::process_event($event);

        // Now get the run record just executed.
        $firstruns = $DB->get_records('tool_trigger_workflow_hist', [], 'id ASC');

        \cache_helper::purge_by_definition('tool_trigger', 'eventsubscriptions');
        \tool_trigger\event_processor::rerun_all_error_runs($wfid);

        // Confirm that the run wasn't re-executed, as there was no error.
        $secondruns = $DB->get_records('tool_trigger_workflow_hist', [], 'id ASC');

        $this->assertEquals(count($firstruns), count($secondruns));
        $first = reset($firstruns);
        $second = reset($secondruns);
        $this->assertEquals($first->id, $second->id);

        $DB->delete_records('tool_trigger_workflow_hist', []);
        $DB->delete_records('tool_trigger_run_hist', []);
        $DB->delete_records('tool_trigger_workflows', []);

        // Now confirm an errored run will rerun (and error again).
        $errorsteps = [
            [
                'id' => 0,
                'type' => 'filters',
                'stepclass' => '\tool_trigger\steps\filters\numcompare_filter_step',
                'steporder' => '0',
                'field1' => 123,
                'operator' => 'invalid operator',
                'field2' => 456,
            ]
        ];

        // New workflow with these steps.
        $wfid = $this->create_workflow(1, $errorsteps, 1);
        \cache_helper::purge_by_definition('tool_trigger', 'eventsubscriptions');
        \tool_trigger\event_processor::process_event($event);
        $this->assertDebuggingCalledCount(3);

        // Now get the run record just executed.
        $firstruns = $DB->get_records('tool_trigger_workflow_hist', [], 'id ASC');

        // Confirm that there is a record in the errorstep field.
        $first = reset($firstruns);
        $this->assertEquals(0, $first->errorstep);

        \cache_helper::purge_by_definition('tool_trigger', 'eventsubscriptions');
        \tool_trigger\event_processor::rerun_all_error_runs($wfid);
        $this->assertDebuggingCalled();

        // Confirm that the run was re-executed, as there was an error.
        $secondruns = $DB->get_records('tool_trigger_workflow_hist', [], 'id ASC');

        $this->assertNotEquals(count($firstruns), count($secondruns));
        $second = end($secondruns);
        $this->assertGreaterThan($first->id, $second->id);

        // Now do a bit of DB hackery to add another run, that has been modified to look successful.
        $newrecord = clone($second);
        unset($newrecord->id);
        unset($newrecord->errorstep);
        $insertedid = $DB->insert_record('tool_trigger_workflow_hist', $newrecord, true);

        // Now if we attempt a rerun, nothing should happen, as this event's most recent entry is a success.
        \cache_helper::purge_by_definition('tool_trigger', 'eventsubscriptions');
        \tool_trigger\event_processor::rerun_all_error_runs($wfid);

        $thirdruns = $DB->get_records('tool_trigger_workflow_hist', [], 'id ASC');
        // Confirm that nothing changed in the DB, except the addition of the modified entry.
        $this->assertEquals(count($secondruns) + 1, count($thirdruns));
        $third = end($thirdruns);
        $this->assertEquals($insertedid, $third->id);
    }

    public function test_cleanup_history() {
        global $DB;
        $this->resetAfterTest();

        $event = \core\event\user_loggedin::create($this->eventarr);
        $workflowid2 = $this->create_workflow(1, [], 1);
        \tool_trigger\event_processor::process_event($event);

        $countwfhist = $DB->count_records('tool_trigger_run_hist', []);
        $this->assertEquals(1, $countwfhist);

        // Change it as if it was executed long time ago.
        $DB->execute('UPDATE {tool_trigger_run_hist} SET executed = :veryold', ['veryold' => 12345]);

        // Run the task. It should delete this record from history.
        $task = new \tool_trigger\task\cleanup_history();
        $task->execute();

        $countwfhist = $DB->count_records('tool_trigger_run_hist', []);
        $this->assertEquals(0, $countwfhist);
    }
}
