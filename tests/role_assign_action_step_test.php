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
 * Tests for role_assign_action_step.
 *
 * @package    tool_trigger
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

class role_assign_action_step_testcase extends advanced_testcase {

    /**
     * Test user.
     * @var
     */
    protected $user;

    /**
     * Test category.
     * @var
     */
    protected $course;

    /**
     * Test event.
     * @var
     */
    protected $event;

    /**
     * Test role id.
     * @var
     */
    protected $roleid;

    /**
     * Test context.
     * @var
     */
    protected $context;

    /**
     * Initial set up.
     */
    public function setUp():void {
        parent::setUp();

        $this->resetAfterTest(true);
        $this->user = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course();
        $this->roleid = $this->getDataGenerator()->create_role();
        $this->context = context_course::instance($this->course->id);

        $this->setUser($this->user);

        // Create an event that has all required data for role_assign_action_step to be taken.
        $this->event = \core\event\role_assigned::create([
            'context' => $this->context,
            'objectid' => $this->roleid,
            'relateduserid' => $this->user->id,
            'other' => [
                'id' => 'not important',
                'component' => 'not important',
                'itemid' => 'not important'
            ],
        ]);

        // Run as the cron user  .
        cron_setup_user();
    }

    /**
     * Test fields list.
     */
    public function test_get_fields() {
        $expected = [
            'role_assign_result',
            'role_assign_record_id',
        ];
        $this->assertEquals($expected, \tool_trigger\steps\actions\role_assign_action_step::get_fields());
    }

    /**
     * Test can use hardcoded values.
     */
    public function test_execute_basic() {
        $this->assertFalse(user_has_role_assignment($this->user->id, $this->roleid, $this->context->id));

        $step = new \tool_trigger\steps\actions\role_assign_action_step(
            json_encode([
                'useridfield' => $this->user->id,
                'roleidfield' => $this->roleid,
                'contextidfield' => $this->context->id,
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $this->assertTrue($status);
        $this->assertTrue($stepresults['role_assign_result']);
        $this->assertTrue(user_has_role_assignment($this->user->id, $this->roleid, $this->context->id));
    }

    /**
     * Test can use placeholders.
     */
    public function test_execute_placeholder() {
        $this->assertFalse(user_has_role_assignment($this->user->id, $this->roleid, $this->context->id));

        $step = new \tool_trigger\steps\actions\role_assign_action_step(
            json_encode([
                'useridfield' => 'relateduserid',
                'roleidfield' => 'objectid',
                'contextidfield' => $this->context->id,
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $this->assertTrue($status);
        $this->assertTrue($stepresults['role_assign_result']);
        $this->assertTrue(user_has_role_assignment($this->user->id, $this->roleid, $this->context->id));
    }

    /**
     * Test for exception if an invalid field name is entered.
     */
    public function test_execute_nosuchfield() {
        $step = new \tool_trigger\steps\actions\role_assign_action_step(
            json_encode([
                'useridfield' => 'nosuchfield',
                'roleidfield' => 'objectid',
                'contextidfield' => $this->context->id,
            ])
        );

        $this->expectException('\moodle_exception');
        $this->expectExceptionMessageRegExp("/Specified userid field not present in the workflow data:*/");
        $step->execute(null, null, $this->event, []);
    }
}
