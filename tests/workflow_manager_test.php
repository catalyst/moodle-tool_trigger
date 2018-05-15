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

}
