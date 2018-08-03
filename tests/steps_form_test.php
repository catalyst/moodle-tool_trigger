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
 * Test for the rendering of the forms of all the step classes.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/trigger/lib.php');

class tool_trigger_steps_form_testcase extends advanced_testcase {

    public function setUp() {
        // Run as admin user.
        $this->setAdminUser();
    }

    public function tearDown() {
        // Reset user for tests.
        $this->setUser();
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
     * Test the display of the starting form (with just the "type" and "step" menus).
     */
    public function test_base_form() {
        // This lib function simply prints out the base form.
        $html = tool_trigger_output_fragment_new_base_form([
            'context' => \context_system::instance()
        ]);

        // Check that it has these form fields.
        $this->assertContains('name="id"', $html);
        $this->assertContains('name="steporder"', $html);
        $this->assertContains('name="type"', $html);
        $this->assertContains('name="stepclass"', $html);

        // Check that it doesn't have these other form fields.
        $this->assertNotContains('name="name"', $html);
        $this->assertNotContains('name="description"', $html);
    }

    /**
     * Test the display of the forms of each step type.
     *
     * @param string $steptype
     * @param string $stepclass
     *
     * @dataProvider provide_steps
     */
    public function test_step_form($steptype, $stepclass) {
        $html = tool_trigger_output_fragment_new_step_form([
            'context' => \context_system::instance(),
            'steptype' => $steptype,
            'stepclass' => $stepclass,
            'event' => '\core\event\user_loggedin',
            'existingsteps' => '[]',
            'steporder' => 0
        ]);

        // We mostly want to test that it renders with no errors thrown.
        // As a minimal test that the output contains *something*, check that
        // the "name" and "description" fields have been added.
        $this->assertContains('name="name"', $html);
        $this->assertContains('name="description"', $html);
    }

    /**
     * Test getting the available fields from database.
     */
    public function test_get_trigger_fields() {
        $this->resetAfterTest();
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

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\steps\base\base_form', 'get_trigger_fields');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(
                new \tool_trigger\steps\base\base_form,
                '\core\event\user_login_failed',
                '\tool_trigger\steps\lookups\course_lookup_step',
                array(),
                -1
                );  // Get result of invoked method.

        $expected = array (
            'fields' =>
            array (
                0 =>
                array (
                    'field' => 'id',
                    'type' => 'string',
                ),
                1 =>
                array (
                    'field' => 'eventname',
                    'type' => 'string',
                ),
                2 =>
                array (
                    'field' => 'component',
                    'type' => 'string',
                ),
                3 =>
                array (
                    'field' => 'action',
                    'type' => 'string',
                ),
                4 =>
                array (
                    'field' => 'target',
                    'type' => 'string',
                ),
                5 =>
                array (
                    'field' => 'objecttable',
                    'type' => 'string',
                ),
                6 =>
                array (
                    'field' => 'objectid',
                    'type' => 'string',
                ),
                7 =>
                array (
                    'field' => 'crud',
                    'type' => 'string',
                ),
                8 =>
                array (
                    'field' => 'edulevel',
                    'type' => 'string',
                ),
                9 =>
                array (
                    'field' => 'contextid',
                    'type' => 'string',
                ),
                10 =>
                array (
                    'field' => 'contextlevel',
                    'type' => 'string',
                ),
                11 =>
                array (
                    'field' => 'contextinstanceid',
                    'type' => 'string',
                ),
                12 =>
                array (
                    'field' => 'userid',
                    'type' => 'string',
                ),
                13 =>
                array (
                    'field' => 'courseid',
                    'type' => 'string',
                ),
                14 =>
                array (
                    'field' => 'relateduserid',
                    'type' => 'string',
                ),
                15 =>
                array (
                    'field' => 'anonymous',
                    'type' => 'string',
                ),
                16 =>
                array (
                    'field' => 'other_username',
                    'type' => 'string',
                ),
                17 =>
                array (
                    'field' => 'other_reason',
                    'type' => 'integer',
                ),
                18 =>
                array (
                    'field' => 'timecreated',
                    'type' => 'string',
                ),
                19 =>
                array (
                    'field' => 'origin',
                    'type' => 'string',
                ),
                20 =>
                array (
                    'field' => 'ip',
                    'type' => 'string',
                ),
                21 =>
                array (
                    'field' => 'realuserid',
                    'type' => 'string',
                ),
            ),
            'steps' => array()
        );

        $this->assertEquals($proxy, $expected);

    }

    /**
     * Data provider for test_step_form(). Simply gets a list of all known step classes.
     *
     * @return array
     */
    public function provide_steps() {
        $data = [];

        $wfm = new \tool_trigger\workflow_manager();
        foreach (\tool_trigger\workflow_manager::STEPTYPES as $steptype) {
            $stepclasses = $wfm->get_step_class_names($steptype);
            foreach ($stepclasses as $stepclass) {
                $data[] = [ $steptype, $stepclass ];
            }
        }

        return $data;
    }
}
