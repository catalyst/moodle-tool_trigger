<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY, without even the implied warranty of
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

class tool_trigger_event_processor_testcase extends advanced_testcase {

    public function setup() {
        global $DB;
        $this->resetAfterTest(true);
    }


    /**
     * Test is event ignored.
     * Test event with no associated workflow is ignored.
     */
    public function test_get_learnt_events() {

        // Add event records to database.

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\learn_process', 'get_learnt_events');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \tool_trigger\learn_process); // Get result of invoked method.

        $this->assertTrue($proxy);
    }

}