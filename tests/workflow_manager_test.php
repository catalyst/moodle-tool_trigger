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
        $steptype = 'triggers';
        $stepobj = new \tool_trigger\workflow_manager();
        $steps = $stepobj->get_step_class_names($steptype);

        $expected = '\tool_trigger\steps\triggers\http_post_trigger_step';

        $this->assertContains($expected, $steps);

    }

    /**
     * Test getting step human readable names by class name.
     */
    public function test_get_steps_with_names() {

        $stepclasses = array('\tool_trigger\steps\triggers\http_post_trigger_step');
        $stepobj = new \tool_trigger\workflow_manager();
        $steps = $stepobj->get_steps_with_names($stepclasses);

        $expected = array(
                'class' => '\tool_trigger\steps\triggers\http_post_trigger_step',
                'name' => get_string('httpposttriggerstepname', 'tool_trigger')
        );

        $this->assertContains($expected, $steps);

    }

    /**
     * Test getting steps by step type.
     */
    public function test_get_steps_by_type() {

        $steptype = 'triggers';
        $stepobj = new \tool_trigger\workflow_manager();
        $steps = $stepobj->get_steps_by_type($steptype);

        $expected = array(
                'class' => '\tool_trigger\steps\triggers\http_post_trigger_step',
                'name' => get_string('httpposttriggerstepname', 'tool_trigger')
        );

        $this->assertContains($expected, $steps);
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
}
