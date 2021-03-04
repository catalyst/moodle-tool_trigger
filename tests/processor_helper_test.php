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
 * Test for processor helper trait.
 *
 * @package    tool_trigger
 * @copyright  Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once('tool_trigger_testcase.php');

class tool_trigger_processor_helper_testcase extends tool_trigger_testcase {

    /**
     * Anonymous class for testing.
     * @var
     */
    protected $testclass;

    /**
     * Set up.
     */
    public function setup():void {
        $this->resetAfterTest(true);

        // Create anonymous class for testing trait.
        $this->testclass = new class {
            use \tool_trigger\helper\processor_helper;
        };
    }

    /**
     * Test can restore event from the DB record.
     */
    public function test_restore_event() {
        $data = (object) [
            'eventname' => '\\core\\event\\user_loggedin',
            'component' => 'core',
            'action' => 'loggedin',
            'target' => 'user',
            'objecttable' => 'user',
            'objectid' => '113000',
            'crud' => 'r',
            'edulevel' => 0,
            'contextid' => 1,
            'contextlevel' => 10,
            'contextinstanceid' => 0,
            'userid' => '113000',
            'courseid' => 0,
            'relateduserid' => null,
            'anonymous' => 0,
            'other' => 'a:1:{s:8:"username";s:9:"username1";}',
            'timecreated' => 1571795920,
            'origin' => 'cli',
            'ip' => null,
            'realuserid' => null,
        ];

        // Empty data.
        $actual = $this->testclass->restore_event(new stdClass());
        $this->assertNull($actual);

        // Non-existing event.
        $data->eventname = '\\core\\event\\non_existing_event';
        $actual = $this->testclass->restore_event($data);
        $this->assertNull($actual);

        // Existing event.
        $data->eventname = '\\core\\event\\user_loggedin';
        $actual = $this->testclass->restore_event($data);

        $expectedevent = \core\event\user_loggedin::create([
            'userid' => '113000',
            'objectid' => '113000',
            'other' => array('username' => 'username1'),
        ]);
        $this->assertTrue($actual->is_restored());
        $this->assertEquals($expectedevent->eventname, $actual->eventname);
        $this->assertEquals($expectedevent->userid, $actual->userid);
        $this->assertEquals($expectedevent->objectid, $actual->objectid);
        $this->assertEquals($expectedevent->get_username(), $actual->get_username());
    }

    /**
     * Test can execute step.
     */
    public function test_execute_step() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        $event = \core\event\user_loggedin::create([
            'userid' => $user->id,
            'objectid' => $user->id,
            'other' => array('username' => $user->username),
        ]);

        $workflowid = $this->create_workflow();
        $steps = $DB->get_records('tool_trigger_steps', ['workflowid' => $workflowid]);
        foreach ($steps as $step) {
            $result = $this->testclass->execute_step($step, new stdClass(), $event, []);
            $this->assertCount(2, $result);
            $this->assertTrue($result[0]);
            $this->assertTrue(is_array($result[1]));
            foreach ($result[1] as $key => $value) {
                $this->assertContains('user_', $key);
            }
        }
    }

    /**
     * Test can get workflow steps.
     */
    public function test_get_workflow_steps() {
        // Non-existing.
        $this->assertEmpty($this->testclass->get_workflow_steps(777777));

        // Existing.
        $workflowid = $this->create_workflow();
        $actual = $this->testclass->get_workflow_steps($workflowid);
        $this->assertCount(1, $actual);
        $step = reset($actual);
        $this->assertEquals($workflowid, $step->workflowid);
        $this->assertEquals('Get user data', $step->name);
        $this->assertEquals('\tool_trigger\steps\lookups\user_lookup_step', $step->stepclass);
        $this->assertEquals('lookups', $step->type);
    }

    /**
     * Test can get workflow record.
     */
    public function test_update_workflow_record() {
        global $DB;

        $workflowid = $this->create_workflow();

        $workflow = $DB->get_record('tool_trigger_workflows', ['id' => $workflowid]);
        $this->assertEquals(0, $workflow->timetriggered);

        $workflow->name = 'New name';
        $workflow->description = 'New desc';
        $workflow->event = 'New event';
        $workflow->enabled = 0;
        $workflow->draft = 0;
        $workflow->timecreated = 1;
        $workflow->timemodified = 2;
        $workflow->timetriggered = 3;
        $workflow->realtime = 1;

        $this->testclass->update_workflow_record($workflow);
        $this->assertEquals('New name', $workflow->name);
        $this->assertEquals('New desc', $workflow->description);
        $this->assertEquals('New event', $workflow->event);
        $this->assertEquals(0, $workflow->enabled);
        $this->assertEquals(0, $workflow->draft);
        $this->assertEquals(1, $workflow->timecreated);
        $this->assertEquals(2, $workflow->timemodified);
        $this->assertEquals(3, $workflow->timetriggered);
        $this->assertEquals(1, $workflow->realtime);
    }

    /**
     * Test can save a list of records to a trigger queue.
     */
    public function test_insert_queue_records() {
        global $DB;

        $this->assertEquals(0, $DB->count_records('tool_trigger_queue'));

        $queuerecord = new \stdClass();
        $queuerecord->workflowid = 1;
        $queuerecord->eventid = 2;
        $queuerecord->status = 10;
        $queuerecord->tries = 1;
        $queuerecord->timecreated = time();
        $queuerecord->timemodified = time();
        $queuerecord->laststep = 0;
        $this->testclass->insert_queue_records([$queuerecord]);

        $this->assertEquals(1, $DB->count_records('tool_trigger_queue'));
    }

    /**
     * Test can get event record.
     */
    public function test_get_event_record() {
        global $DB;

        $event = \core\event\user_loggedin::create([
            'userid' => '113000',
            'objectid' => '113000',
            'other' => array('username' => 'username1'),
        ]);

        $data = (object)$event->get_data();
        $data->other = serialize($event->other);
        $eventid = $DB->insert_record('tool_trigger_events', $data);

        $actual = $this->testclass->get_event_record($eventid);
        $this->assertEquals($eventid, $actual->id);
        $this->assertEquals('\core\event\user_loggedin', $actual->eventname);
        $this->assertEquals('113000', $actual->objectid);
    }

}