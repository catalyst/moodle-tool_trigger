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
        $workflowname = 'foo bar';
        $now = 1533446590;
        $expected = 'foo_bar_20180805_0523.json';

        $jsonclass = new \tool_trigger\json\json_export($workflowname);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\tool_trigger\json\json_export', 'get_filename');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($jsonclass, $workflowname, $now); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);

    }

}