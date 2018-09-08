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
 * Test of the webservice action
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

class tool_trigger_webservice_action_step_testcase extends advanced_testcase {


    /**
     * Test getting webservice form.
     */
    public function test_get_webservice_form() {

        // Function to get form for.
        $function = new \stdClass();
        $function->id = 391;
        $function->name = 'enrol_manual_enrol_users';
        $function->classname = 'enrol_manual_external';
        $function->methodname = 'enrol_users';
        $function->classpath = 'enrol/manual/externallib.php';
        $function->component = 'enrol_manual';
        $function->capabilities = 'enrol/manual:enrol';
        $function->services = '';

        $mform = new tool_trigger\steps\base\base_form();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\steps\actions\webservice_action_step', 'get_webservice_form');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(
            new tool_trigger\steps\actions\webservice_action_step,
            $function,
            $mform);  // Get result of invoked method.
    }

}