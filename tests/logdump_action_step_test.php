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
 * Unit tests for logdump_action_step
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

class tool_trigger_logdump_action_step_testcase extends basic_testcase {
    public function test_execute() {

        $user = \core_user::get_user_by_username('admin');
        $context = context_user::instance($user->id);
        $event = \core\event\user_profile_viewed::create([
            'objectid' => $user->id,
            'relateduserid' => $user->id,
            'context' => $context,
            'other' => [
                'courseid' => 1,
                'courseshortname' => 'short name',
                'coursefullname' => 'full name'
            ]
        ]);

        $prevstepresults = [
            'foo' => 'bar',
            'baz' => 'bax'
        ];

        // Just check for a portion of the var_dump of the event, because it's looong!
        $eventdump = <<<EOD
  ["eventname"]=>
  string(31) "\\core\\event\\user_profile_viewed"
  ["component"]=>
  string(4) "core"
  ["action"]=>
  string(6) "viewed"
  ["target"]=>
  string(12) "user_profile"
  ["objecttable"]=>
  string(4) "user"
  ["objectid"]=>
  string(1) "{$user->id}"
EOD;

        $stepresultsdump = <<<'EOD'
array(2) {
  ["foo"]=>
  string(3) "bar"
  ["baz"]=>
  string(3) "bax"
}
EOD;

        $step = new \tool_trigger\steps\actions\logdump_action_step();
        // Look for the var_dump of the event object and the stepresults object, in the output.
        $this->expectOutputRegex('/' . preg_quote($eventdump) . '.*' . preg_quote($stepresultsdump) . '/ms', '/');
        list($status, $stepresults) = $step->execute((object)['name' => 'logdump step'], null, $event, $prevstepresults);
        $this->assertTrue($status);

        // The logdump step also adds the var_dump()s to a datafield called "vardump".
        $this->assertContains($eventdump, $stepresults['vardump']);
        $this->assertContains($stepresultsdump, $stepresults['vardump']);
    }
}