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
 * Learn processor unit tests.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Learn processor unit tests.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class tool_trigger_learn_process_testcase extends advanced_testcase {

    public function setup() {
        $this->resetAfterTest(true);
    }


    /**
     * Helper function to create learnt event object.
     *
     * @return object $learntevent The learnt event object.
     */
    public function create_learnt_event_object() {
        $learntevent = new \stdClass();
        $learntevent->eventname = '\core\event\fake_event';
        $learntevent->component = 'core';
        $learntevent->action = 'loggedin';
        $learntevent->target = 'user';
        $learntevent->objecttable = 'user';
        $learntevent->objectid = 121000;
        $learntevent->crud = 'r';
        $learntevent->edulevel = 0;
        $learntevent->contextid = 1;
        $learntevent->contextlevel = 10;
        $learntevent->contextinstanceid = 0;
        $learntevent->userid = 121000;
        $learntevent->courseid = 0;
        $learntevent->relateduserid = '';
        $learntevent->anonymous = 0;
        $learntevent->other = 'a:1:{s:8:"username";s:9:"username1";}';
        $learntevent->timecreated = 1530406950;
        $learntevent->origin = 'cli';
        $learntevent->ip = '';
        $learntevent->realuserid = '';

        return $learntevent;
    }

    /**
     * Helper method to get event fields.
     *
     * @return array $fields The event fields.
     */
    public function get_event_fields() {
        $fields = array(
            'eventname' => 'string',
            'component' => 'string',
            'action' => 'string',
            'target' => 'string',
            'objecttable' => 'string',
            'objectid' => 'integer',
            'crud' => 'string',
            'edulevel' => 'integer',
            'contextid' => 'integer',
            'contextlevel' => 'integer',
            'contextinstanceid' => 'integer',
            'userid' => 'integer',
            'courseid' => 'integer',
            'relateduserid' => 'string',
            'anonymous' => 'integer',
            'other_username' => 'string',
            'timecreated' => 'integer',
            'origin' => 'string',
            'ip' => 'string',
            'realuserid' => 'string'
        );

        return $fields;
    }

    /**
     * Test learnt events names are retrieved from database.
     */
    public function test_get_learnt_events() {
        global $DB;

        // Add event records to database.
        $learntevent = $this->create_learnt_event_object();
        $learntevent2 = $learntevent;
        $learntevent2->eventname = '\core\event\user_loggedout';
        $learntevent2->action = 'loggedout';

        $DB->insert_records('tool_trigger_learn_events', array($learntevent, $learntevent2));

        $expected = array('\core\event\fake_event', '\core\event\user_loggedout');  // Expected result.

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\learn_process', 'get_learnt_events');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \tool_trigger\learn_process); // Get result of invoked method.

        $this->assertEquals(sort($expected), sort($proxy));  // Order of returned array is not important, just values.
    }

    /**
     * Test learnt event records are retrieved from database.
     */
    public function test_get_learnt_records() {
        global $DB;
        $count = 0;
        $eventnames = array();

        // Add event records to database.
        $learntevent = $this->create_learnt_event_object();

        $DB->insert_records('tool_trigger_learn_events', array($learntevent, $learntevent));

        $expected = array('\core\event\fake_event', '\core\event\fake_event');  // Expected result.

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\learn_process', 'get_learnt_records');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \tool_trigger\learn_process, '\core\event\fake_event'); // Get result of invoked method.

        foreach ($proxy as $value) {
            $eventnames[] = $value->eventname;
            $count++;
        }

        $this->assertEquals(sort($expected), sort($eventnames));
        $this->assertEquals(2, $count);

        $proxy->close();
    }


    /**
     * Test learnt event records are retrieved from database.
     */
    public function test_convert_record_type() {

        $learntevent = $this->create_learnt_event_object();

        $expected = $this->get_event_fields();  // Expected result.

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\learn_process', 'convert_record_type');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \tool_trigger\learn_process, $learntevent, false); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);

    }

    /**
     * Test processed records are merged successfully.
     */
    public function test_merge_records() {

        $processedrecord = $this->get_event_fields();
        $processedrecord2 = $processedrecord;
        $processedrecord2['oher_foo'] = 'string';
        $processedrecords = array($processedrecord, $processedrecord2);

        $expected = $processedrecord;
        $expected['oher_foo'] = 'string';

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\learn_process', 'merge_records');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \tool_trigger\learn_process, $processedrecords); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);

    }

    /**
     * Test db record is merged successfully before updating.
     */
    public function test_merge_db_record() {

        // Simulate learnt event.
        $processedrecord = $this->get_event_fields();

        // Adjust DB record mock to have one record less and one record more than the learnt event.
        $processedrecord2 = $processedrecord;
        $processedrecord2['other_foo'] = 'string';
        unset($processedrecord2['other_username']);

        // Format objects ready for DB insertion prior to merging.
        $record = new \stdClass();
        $record->eventname = '\core\event\fake_event';
        $record->jsonfields = json_encode($processedrecord);
        $record->id = 2;

        $exists = new \stdClass();
        $exists->eventname = '\core\event\fake_event';
        $exists->jsonfields = json_encode($processedrecord2);
        $exists->id = 2;

        $expected = array_merge($processedrecord, $processedrecord2);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\learn_process', 'merge_json_fields');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \tool_trigger\learn_process, $record, $exists); // Get result of invoked method.

        $result = json_decode($proxy->jsonfields, true);

        $this->assertEquals($expected, $result);

    }

    /**
     * Test learnt fields are correctly inserted in the database.
     */
    public function test_store_json_fields_insert() {
        global $DB;

        // Simulate learnt event.
        $processedrecord = $this->get_event_fields();

        $learntevent = '\core\event\fake_event';
        $jsonfields = json_encode($processedrecord);

        $learnprocess = new \tool_trigger\learn_process();
        $learnprocess->store_json_fields($learntevent, $jsonfields);

        // Get record form DB.
        $result = $DB->get_record('tool_trigger_event_fields', array('eventname' => $learntevent));

        $this->assertEquals($result->eventname, $learntevent);
        $this->assertEquals($result->jsonfields, $jsonfields);

    }

    /**
     * Test learnt fields are correctly updated in the database.
     */
    public function test_store_json_fields_update() {
        global $DB;

        // Simulate learnt event.
        $processedrecord = $this->get_event_fields();

        $learntevent = '\core\event\fake_event';
        $jsonfields = json_encode($processedrecord);

        // Manually insert a record into database.
        $record = new \stdClass();
        $record->eventname = $learntevent;
        $record->jsonfields = $jsonfields;
        $DB->insert_record('tool_trigger_event_fields', $record);

        $learnprocess = new \tool_trigger\learn_process();
        $learnprocess->store_json_fields($learntevent, $jsonfields);

        // Get record form DB.
        $result = $DB->get_record('tool_trigger_event_fields', array('eventname' => $learntevent));

        $this->assertEquals($result->eventname, $learntevent);
        $this->assertEquals($result->jsonfields, $jsonfields);

    }

    /**
     * Test learnt fields are correctly retrieved from database for step form.
     */
    public function test_get_event_fields_with_type() {
        global $DB;

        // Simulate learnt event.
        $processedrecord = $this->get_event_fields();

        $learntevent = '\core\event\fake_event';
        $jsonfields = json_encode($processedrecord);

        // Manually insert a record into database.
        $record = new \stdClass();
        $record->eventname = $learntevent;
        $record->jsonfields = $jsonfields;
        $DB->insert_record('tool_trigger_event_fields', $record);

        $eventname = '\core\event\fake_event';
        $learnprocess = new \tool_trigger\learn_process();
        $eventfields = $learnprocess->get_event_fields_with_type($eventname);

        $expected = array (
                0 =>
                array (
                    'field' => 'eventname',
                    'type' => 'string',
                ),
                1 =>
                array (
                    'field' => 'component',
                    'type' => 'string',
                ),
                2 =>
                array (
                    'field' => 'action',
                    'type' => 'string',
                ),
                3 =>
                array (
                    'field' => 'target',
                    'type' => 'string',
                ),
                4 =>
                array (
                    'field' => 'objecttable',
                    'type' => 'string',
                ),
                5 =>
                array (
                    'field' => 'objectid',
                    'type' => 'integer',
                ),
                6 =>
                array (
                    'field' => 'crud',
                    'type' => 'string',
                ),
                7 =>
                array (
                    'field' => 'edulevel',
                    'type' => 'integer',
                ),
                8 =>
                array (
                    'field' => 'contextid',
                    'type' => 'integer',
                ),
                9 =>
                array (
                    'field' => 'contextlevel',
                    'type' => 'integer',
                ),
                10 =>
                array (
                    'field' => 'contextinstanceid',
                    'type' => 'integer',
                ),
                11 =>
                array (
                    'field' => 'userid',
                    'type' => 'integer',
                ),
                12 =>
                array (
                    'field' => 'courseid',
                    'type' => 'integer',
                ),
                13 =>
                array (
                    'field' => 'relateduserid',
                    'type' => 'string',
                ),
                14 =>
                array (
                    'field' => 'anonymous',
                    'type' => 'integer',
                ),
                15 =>
                array (
                    'field' => 'other_username',
                    'type' => 'string',
                ),
                16 =>
                array (
                    'field' => 'timecreated',
                    'type' => 'integer',
                ),
                17 =>
                array (
                    'field' => 'origin',
                    'type' => 'string',
                ),
                18 =>
                array (
                    'field' => 'ip',
                    'type' => 'string',
                ),
                19 =>
                array (
                    'field' => 'realuserid',
                    'type' => 'string',
                ),
            );

        $this->assertEquals($eventfields, $expected);

    }

    /**
     * Test retrieve all the event names we have stored fields for.
     */
    public function test_get_event_fields_events() {
        global $DB;

        // Simulate learnt event.
        $processedrecord = $this->get_event_fields();

        $learntevent = '\core\event\fake_event';
        $jsonfields = json_encode($processedrecord);

        // Manually insert a record into database.
        $record = new \stdClass();
        $record->eventname = $learntevent;
        $record->jsonfields = $jsonfields;
        $DB->insert_record('tool_trigger_event_fields', $record);

        $learnprocess = new \tool_trigger\learn_process();
        $result = $learnprocess->get_event_fields_events();

        $this->assertContains($learntevent, $result);

    }

    /**
     * Test get the stored JSON fields for that event.
     */
    public function test_get_event_fields_json() {
        global $DB;

        // Simulate learnt event.
        $processedrecord = $this->get_event_fields();

        $learntevent = '\core\event\fake_event';
        $jsonfields = json_encode($processedrecord);

        // Manually insert a record into database.
        $record = new \stdClass();
        $record->eventname = $learntevent;
        $record->jsonfields = $jsonfields;
        $DB->insert_record('tool_trigger_event_fields', $record);

        $learnprocess = new \tool_trigger\learn_process();
        $result = $learnprocess->get_event_fields_json($learntevent);

        $this->assertEquals($result->jsonfields, $jsonfields);

    }

    /**
     * Test procesing of JSON fixture file.
     */
    public function test_process_fixtures() {
        $learnprocess = new \tool_trigger\learn_process();
        $learnprocess->process_fixtures();

        // Check that some records have been added to the DB.
        $fieldsjson = $learnprocess->get_event_fields_json('\core\event\blog_comment_created');
        $fields = json_decode($fieldsjson->jsonfields, true);

        $this->assertEquals($fields['eventname'], 'string');

    }
}