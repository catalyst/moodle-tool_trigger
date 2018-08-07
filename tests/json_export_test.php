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
 * JSON export unit tests.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * JSON export unit tests.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class tool_trigger_json_export_testcase extends advanced_testcase {

    public function setup() {
        $this->resetAfterTest(true);
    }

    /**
     * Test filename creations
     */
    public function test_set_filename() {
        $workflowobj = new \stdClass();  // Create workflow object.
        $workflowobj->name = '__testworkflow__';
        $workflowobj->description = 'test workflow description';
        $workflowobj->event = '\mod_scorm\event\user_report_viewed';
        $workflowobj->steps = array (
            358000 => array(
                'id' => 358000,
                'name' => 'a',
                'description' => 's',
                'type' => 'lookups',
                'stepclass' => '/tool_trigger/steps/lookups/user_lookup_step',
                'data' => '{"useridfield":"userid","outputprefix":"user_","nodeleted":"1",'
                           .'"stepdesc":"User lookup","typedesc":"Lookup"}',
                'steporder' => 0,
            ),
            358001 => array(
                'id' => 358001,
                'name' => 's',
                'description' => 's',
                'type' => 'lookups',
                'stepclass' => '/tool_trigger/steps/lookups/course_lookup_step',
                'data' => '{"courseidfield":"courseid","outputprefix":"course_","stepdesc":"Course lookup","typedesc":"Lookup"}',
                'steporder' => 1
            )

        );
        $workflowobj->moodleversion = 2018080300;
        $workflowobj->pluginversion = 2018080500;

        $now = 1533446590;
        $expected = '__testworkflow___20180805_0523.json';

        $jsonclass = new \tool_trigger\json\json_export($workflowobj);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\tool_trigger\json\json_export', 'get_filename');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($jsonclass, $workflowobj->name, $now); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);

    }

}