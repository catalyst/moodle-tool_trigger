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
            'stepclass' => $stepclass
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

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\steps\base\base_form', 'get_trigger_fields');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \tool_trigger\steps\base\base_form); // Get result of invoked method.

        error_log(print_r($proxy, true));

        //$this->assertEquals($result->jsonfields, $jsonfields);

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