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
 * Workflow manager unit tests.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Workflow manager unit tests.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_trigger_workflow_manager_testcase extends advanced_testcase {

    /**
     * Test getting step class names by step type.
     */
    public function test_get_step_class_names() {
        $steptype = 'actions';
        $stepobj = new \tool_trigger\workflow_manager();
        $steps = $stepobj->get_step_class_names($steptype);

        $expected = '\tool_trigger\steps\actions\http_post_action_step';

        $this->assertContains($expected, $steps);

    }

    /**
     * Test getting step human readable names by class name.
     */
    public function test_lookup_step_names() {

        $stepclasses = array('\tool_trigger\steps\actions\http_post_action_step');
        $stepobj = new \tool_trigger\workflow_manager();
        $steps = $stepobj->lookup_step_names($stepclasses);

        $expected = array(
            '\tool_trigger\steps\actions\http_post_action_step' => get_string('httppostactionstepname', 'tool_trigger')
        );

        $this->assertArraySubset($expected, $steps);
    }

    /**
     * Test getting steps by step type.
     */
    public function test_get_steps_by_type() {

        $steptype = 'actions';
        $stepobj = new \tool_trigger\workflow_manager();
        $steps = $stepobj->get_steps_by_type($steptype);

        $expected = array(
            '\tool_trigger\steps\actions\http_post_action_step' => get_string('httppostactionstepname', 'tool_trigger')
        );

        $this->assertArraySubset($expected, $steps);
    }

    /**
     * Test the code for validating the name of a step class and instantiating it.
     *
     * (This is an important security feature, because we take the step class names from form input. We have to make sure that
     * a user can't modify the form submission data and cause us to instantiate an arbitrary class.)
     */
    public function test_validate_step_class_good() {
        $wfm = new \tool_trigger\workflow_manager();

        $goodstepclassname = '\tool_trigger\steps\filters\fail_filter_step';
        $this->assertTrue($wfm->validate_step_class($goodstepclassname));
        $goodstepobj = $wfm->validate_and_make_step($goodstepclassname);
        $this->assertInstanceOf($goodstepclassname, $goodstepobj);
    }

    /**
     * Test that the validation code will reject a bad step class name, and throw an exception when asked to instantiate it.
     */
    public function test_validate_step_class_bad() {
        $wfm = new \tool_trigger\workflow_manager();

        $badstepclassname = '\core\task\password_reset_cleanup_task';
        $this->assertFalse($wfm->validate_step_class($badstepclassname));

        $this->expectException('invalid_parameter_exception');
        $wfm->validate_and_make_step($badstepclassname);
    }

    /**
     * Test getting workflow data object with step data included.
     */
    public function test_get_workflow_data_with_steps() {
        $this->resetAfterTest();
        global $DB;

        // Create a workflow with step.
        $mdata = new \stdClass();
        $mdata->workflowid = 0;
        $mdata->workflowname = '__testworkflow__';
        $mdata->workflowdescription = 'test workflow description';
        $mdata->eventtomonitor = '\mod_scorm\event\user_report_viewed';
        $mdata->workflowactive = 1;
        $mdata->draftmode = 0;
        $mdata->isstepschanged = 1;
        $mdata->stepjson = '[{"useridfield":"userid","outputprefix":"user_","nodeleted":"1","stepdesc":"User lookup",'
            .'"typedesc":"Lookup","id":"7","type":"lookups","stepclass":"/tool_trigger/steps/lookups/user_lookup_step",'
            .'"name":"a","description":"s","steporder":"0"},{"courseidfield":"courseid","outputprefix":"course_",'
            .'"stepdesc":"Course lookup","typedesc":"Lookup","id":"6","type":"lookups",'
            .'"stepclass":"/tool_trigger/steps/lookups/course_lookup_step","name":"s","description":"s","steporder":"1"}]';

        $workflowprocess = new \tool_trigger\workflow_process($mdata);
        $workflowprocess->processform();
        $workflow = $DB->get_record('tool_trigger_workflows', array('name' => '__testworkflow__'), '*', MUST_EXIST);
        $workflowid = $workflow->id;

        $workflowdata = \tool_trigger\workflow_manager::get_workflow_data_with_steps($workflowid);

        $this->assertEquals('__testworkflow__', $workflowdata->name);
        $this->assertEquals('"test workflow description"', $workflowdata->description);

    }


}
