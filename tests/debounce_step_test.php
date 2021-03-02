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
 * Debounce filter step's unit test
 *
 * @package    tool_trigger
 * @author     Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright  Catalyst IT 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/gradelib.php");

class debounce_step_testcase extends advanced_testcase {
    /**
     * The step to execute.
     *
     * @var \tool_trigger\steps\debounce\debounce_step
     */
    private $step;

    /**
     * EventID to keep track of.
     */
    private $eventid;

    /**
     * Create a "user_profile_viewed" event, of user1 viewing user2's
     * profile. And then run everything else as the cron user.
     */
    public function setup():void {
        global $DB;
        $this->resetAfterTest(true);

        // Grade event generation, copied from lib/tests/event_user_graded.php!
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $gradecategory = grade_category::fetch_course_category($course->id);
        $gradecategory->load_grade_item();
        $gradeitem = $gradecategory->grade_item;

        $gradeitem->update_final_grade($user->id, 10, 'gradebook');

        $gradegrade = new grade_grade(array('userid' => $user->id, 'itemid' => $gradeitem->id), true);
        $gradegrade->grade_item = $gradeitem;

        $this->event = \core\event\user_graded::create_from_grade($gradegrade);

        // No existing event queue.
        $this->step = new \tool_trigger\steps\debounce\debounce_step(
            json_encode([
                'debouncecontext' => 'testcontrol',
                'debouncecontext[]' => 'eventname',
                'debounceduration[number]' => 60,
                'debounceduration[timeunit]' => 60,
            ])
        );

        // Add this step to the events table.
        $processor = new \tool_trigger\event_processor();
        $entrymethod = new ReflectionMethod($processor, 'prepare_event');
        $entrymethod->setAccessible(true);
        $entry = $entrymethod->invoke($processor, $this->event);
        $this->eventid = $DB->insert_record('tool_trigger_events', $entry, true);
    }

    private function get_mock_queue_item() {
        global $DB;

        $id = $DB->insert_record('tool_trigger_queue', [
            'workflowid' => 1,
            'eventid' => $this->eventid,
            'status' => 0,
            'tries' => 0,
            'laststep' => 1,
            'timecreated' => time(),
            'timemodified' => time()
        ], true);

        return $DB->get_record('tool_trigger_queue', ['id' => $id]);
    }

    public function test_event_queue() {
        global $DB;

        // Scenario 1: No existing event queue.
        $trigger1 = $this->get_mock_queue_item();
        list($status, $stepresults) = $this->step->execute(null, $trigger1, $this->event, ['eventid' => $this->eventid]);
        // Test that the run bailed early (false), and there is a new queue item with an execution time.
        $this->assertfalse($status);
        $records = $DB->get_records('tool_trigger_queue');
        $this->assertEquals(2, count($records));
        $end = end($records);
        $this->assertTrue($end->executiontime >= time());
        $DB->delete_records('tool_trigger_queue');

        // Scenario 2: Already existing no-execution event queue
        $trigger1 = $this->get_mock_queue_item();
        $trigger2 = $this->get_mock_queue_item();

        list($status, $stepresults) = $this->step->execute(null, $trigger2, $this->event, ['eventid' => $this->eventid]);
        // Test that the first trigger record wasn't affected.
        $this->assertFalse($status);
        $this->assertEquals(0, (int) $DB->get_field('tool_trigger_queue', 'status', ['id' => $trigger1->id]));
        $this->assertNull($DB->get_field('tool_trigger_queue', 'executiontime', ['id' => $trigger1->id]));

        $records = $DB->get_records('tool_trigger_queue');
        $this->assertEquals(3, count($records));
        $end = end($records);
        $this->assertTrue($end->executiontime >= time());
        $DB->delete_records('tool_trigger_queue');

        // Scenario 3: Already existing execution event queue
        $trigger1 = $this->get_mock_queue_item();
        $trigger2 = $this->get_mock_queue_item();
        $DB->set_field('tool_trigger_queue', 'executiontime', 5, ['id' => $trigger1->id]);

        list($status, $stepresults) = $this->step->execute(null, $trigger2, $this->event, ['eventid' => $this->eventid]);
        // Test that the first trigger record wasn't affected.
        $this->assertFalse($status);
        $this->assertEquals(0, (int) $DB->get_field('tool_trigger_queue', 'status', ['id' => $trigger1->id]));
        $this->assertEquals(5, (int) $DB->get_field('tool_trigger_queue', 'executiontime', ['id' => $trigger1->id]));

        $records = $DB->get_records('tool_trigger_queue');
        $this->assertEquals(3, count($records));
        $end = end($records);
        $this->assertTrue($end->executiontime >= time());
    }

    public function test_event_cancellation() {
        global $DB;

        // Scenario 1:  2 event, run lower
        $trigger1 = $this->get_mock_queue_item();
        $stepresults1 = ['eventid' => $this->eventid];
        $trigger2 = $this->get_mock_queue_item();
        $stepresults2 = ['eventid' => $this->eventid];
        // Execute both events to create queued versions.
        list($status, $stepresults1) = $this->step->execute(null, $trigger1, $this->event, $stepresults1);
        list($status, $stepresults2) = $this->step->execute(null, $trigger2, $this->event, $stepresults2);

        $this->assertEquals(4, $DB->count_records('tool_trigger_queue'));

        $qtrigger1 = $DB->get_record('tool_trigger_queue', ['id' => $stepresults1['debouncequeueid']]);
        $qtrigger2 = $DB->get_record('tool_trigger_queue', ['id' => $stepresults2['debouncequeueid']]);
        // Hack the execution times to order 2 > 1.
        $DB->set_field('tool_trigger_queue', 'executiontime', 1, ['id' => $qtrigger1->id]);
        $DB->set_field('tool_trigger_queue', 'executiontime', 2, ['id' => $qtrigger2->id]);
        // Fake the cancelled status of the queue step performed by the controller.
        $DB->set_field('tool_trigger_queue', 'status', -1, ['executiontime' => null]);

        list($status, $stepresults1) = $this->step->execute(null, $qtrigger1, $this->event, $stepresults1);
        // Confirm there is a cancel in the stepresults, and the higher exectime was not cancelled.
        $this->assertFalse($status);
        $this->assertTrue($stepresults1['cancelled']);
        $this->assertEquals(0, (int) $DB->get_field('tool_trigger_queue', 'status', ['id' => $qtrigger2->id]));

        // Execute the second one and confirm it fires correctly.
        list($status, $stepresults2) = $this->step->execute(null, $qtrigger2, $this->event, $stepresults2);
        $this->assertTrue($status);
        $this->assertFalse($stepresults2['cancelled']);
        $this->assertEquals(-1, (int) $DB->get_field('tool_trigger_queue', 'status', ['id' => $qtrigger1->id]));

        $DB->delete_records('tool_trigger_queue');

        // Scenario 2: 2 event, run higher
        $trigger1 = $this->get_mock_queue_item();
        $stepresults1 = ['eventid' => $this->eventid];
        $trigger2 = $this->get_mock_queue_item();
        $stepresults2 = ['eventid' => $this->eventid];
        // Execute both events to create queued versions.
        list($status, $stepresults1) = $this->step->execute(null, $trigger1, $this->event, $stepresults1);
        list($status, $stepresults2) = $this->step->execute(null, $trigger2, $this->event, $stepresults2);

        $this->assertEquals(4, $DB->count_records('tool_trigger_queue'));

        $qtrigger1 = $DB->get_record('tool_trigger_queue', ['id' => $stepresults1['debouncequeueid']]);
        $qtrigger2 = $DB->get_record('tool_trigger_queue', ['id' => $stepresults2['debouncequeueid']]);
        // Hack the execution times to order 2 > 1.
        $DB->set_field('tool_trigger_queue', 'executiontime', 1, ['id' => $qtrigger1->id]);
        $DB->set_field('tool_trigger_queue', 'executiontime', 2, ['id' => $qtrigger2->id]);
        // Fake the cancelled status of the queue step performed by the controller.
        $DB->set_field('tool_trigger_queue', 'status', -1, ['executiontime' => null]);

        list($status, $stepresults2) = $this->step->execute(null, $qtrigger2, $this->event, $stepresults2);
        // Confirm there is a cancel in the stepresults, and the higher exectime was not cancelled.
        $this->assertTrue($status);
        $this->assertFalse($stepresults2['cancelled']);
        $this->assertEquals(-1, (int) $DB->get_field('tool_trigger_queue', 'status', ['id' => $qtrigger1->id]));

        $DB->delete_records('tool_trigger_queue');

        // Scenario 3: 3 event, run middle
        $trigger1 = $this->get_mock_queue_item();
        $stepresults1 = ['eventid' => $this->eventid];
        $trigger2 = $this->get_mock_queue_item();
        $stepresults2 = ['eventid' => $this->eventid];
        $trigger3 = $this->get_mock_queue_item();
        $stepresults3 = ['eventid' => $this->eventid];
        // Execute all events to create queued versions.
        list($status, $stepresults1) = $this->step->execute(null, $trigger1, $this->event, $stepresults1);
        list($status, $stepresults2) = $this->step->execute(null, $trigger2, $this->event, $stepresults2);
        list($status, $stepresults3) = $this->step->execute(null, $trigger3, $this->event, $stepresults3);

        $this->assertEquals(6, $DB->count_records('tool_trigger_queue'));

        $qtrigger1 = $DB->get_record('tool_trigger_queue', ['id' => $stepresults1['debouncequeueid']]);
        $qtrigger2 = $DB->get_record('tool_trigger_queue', ['id' => $stepresults2['debouncequeueid']]);
        $qtrigger3 = $DB->get_record('tool_trigger_queue', ['id' => $stepresults3['debouncequeueid']]);
        // Hack the execution times to order 3 > 2 > 1.
        $DB->set_field('tool_trigger_queue', 'executiontime', 1, ['id' => $qtrigger1->id]);
        $DB->set_field('tool_trigger_queue', 'executiontime', 2, ['id' => $qtrigger2->id]);
        $DB->set_field('tool_trigger_queue', 'executiontime', 3, ['id' => $qtrigger3->id]);
        // Fake the cancelled status of the queue step performed by the controller.
        $DB->set_field('tool_trigger_queue', 'status', -1, ['executiontime' => null]);

        list($status, $stepresults2) = $this->step->execute(null, $qtrigger2, $this->event, $stepresults2);
        // Confirm there is a cancel in the stepresults, and the higher exectime was not cancelled.
        $this->assertFalse($status);
        $this->assertTrue($stepresults2['cancelled']);
        $this->assertEquals(-1, (int) $DB->get_field('tool_trigger_queue', 'status', ['id' => $qtrigger1->id]));
        $this->assertEquals(0, (int) $DB->get_field('tool_trigger_queue', 'status', ['id' => $qtrigger3->id]));

        // Execute the third one and confirm it fires correctly.
        list($status, $stepresults3) = $this->step->execute(null, $qtrigger3, $this->event, $stepresults3);
        $this->assertTrue($status);
        $this->assertFalse($stepresults3['cancelled']);
        $this->assertEquals(-1, (int) $DB->get_field('tool_trigger_queue', 'status', ['id' => $qtrigger1->id]));
        $this->assertEquals(-1, (int) $DB->get_field('tool_trigger_queue', 'status', ['id' => $qtrigger2->id]));
    }
}
