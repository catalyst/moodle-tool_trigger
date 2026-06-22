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
 * "Fail" filter step's unit tests.
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger;

defined('MOODLE_INTERNAL') || die();

global $CFG;

class subplugin_step_test extends \advanced_testcase {

    /**
     * Test user.
     * @var
     */
    protected $user;

    /**
     * Test event.
     * @var
     */
    protected $event;

    /**
     * Create an event to use for testing.
     */
    public function setup():void {

        // Create a user event.
        $this->user = \core_user::get_user_by_username('admin');
        $this->event = \core\event\user_profile_viewed::create([
            'objectid' => $this->user->id,
            'relateduserid' => $this->user->id,
            'context' => \context_user::instance($this->user->id),
            'other' => [
                'courseid' => 1,
                'courseshortname' => 'short name',
                'coursefullname' => 'full name'
            ]
        ]);
    }

    /**
     * Basic use-case, with default values for settings. Find the
     * user identified at "userid", and add their data with the
     * prefix "user_".
     */
    public function test_execute_basic() {
        $step = new \trigger_testplugin\steps\lookups\subplugin_lookup_test_step(json_encode([]));

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $this->assertTrue($status);
        $this->assertEquals('test', $stepresults['testlookupresult']);
    }
}
