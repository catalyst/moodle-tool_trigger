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
 * Course category look up tests.
 *
 * @package    tool_trigger
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

class tool_trigger_context_lookup_step_testcase extends advanced_testcase {



    /**
     * Test category.
     * @var
     */
    protected $category;

    protected $course;

    protected $module;

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
     * Initial set up.
     */
    public function setUp() {
        parent::setUp();

        $this->setAdminUser();

        $this->resetAfterTest(true);
        $this->user = $this->getDataGenerator()->create_user();
        $this->category = $this->getDataGenerator()->create_category();
        $this->course = $this->getDataGenerator()->create_course();
        $this->module = $this->getDataGenerator()->create_module('page', ['course' => $this->course->id]);


        // Run as the cron user  .
        cron_setup_user();
    }

    /**
     * Test fields list.
     */
    public function test_get_fields() {
        $expected = [
            'id',
            'contextlevel',
            'instanceid',
            'path',
            'depth',
        ];
        $this->assertEquals($expected, \tool_trigger\steps\lookups\context_lookup_step::get_fields());
    }

    /**
     * Data provider for tests.
     * @return array
     */
    public function test_data_provider() {
        return [
            'User context' => [
                '\core\event\user_created',
                [],
                CONTEXT_USER,
                'user',
            ],
            'Category context' => [
                '\core\event\course_category_created',
                [],
                CONTEXT_COURSECAT,
                'category',
            ],
            'Course context' => [
                '\core\event\course_created',
                ['fullname' => 'Test'],
                CONTEXT_COURSE,
                'course',
            ],
            'Module context' => [
                '\mod_page\event\course_module_viewed',
                [],
                CONTEXT_MODULE,
                'module',
            ],
        ];
    }

    /**
     * Test basic execution.
     *
     * @dataProvider test_data_provider
     *
     * @param string $event Event class to create.
     * @param array $other Data to put to "other" array of the event.
     * @param int $contextlevel Context level.
     * @param string $type Type of the entity (course, user, category).
     *
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_execute_basic($event, $other, $contextlevel, $type) {
        $cotextclass = context_helper::get_class_for_level($contextlevel);

        $instanceid = ($contextlevel == CONTEXT_MODULE) ? $this->$type->cmid : $this->$type->id;

        $this->event = $event::create([
            'objectid' => ($contextlevel == CONTEXT_MODULE) ? $this->$type->cmid : $this->$type->id,
            'context' => $cotextclass::instance($instanceid),
            'other' => $other
        ]);

        $expected = $cotextclass::instance($instanceid);

        // Hardcoded id as int.
        $step = new \tool_trigger\steps\lookups\context_lookup_step(
            json_encode([
                'instanceid' => $this->$type->id,
                'contextlevel' => $contextlevel,
                'mod' => ($contextlevel == CONTEXT_MODULE) ? 'page' : null,
                'outputprefix' => 'test_'
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);
        $this->assertTrue($status);
        $this->assertEquals($expected->id, $stepresults['test_id']);
        $this->assertEquals($expected->contextlevel, $stepresults['test_contextlevel']);
        $this->assertEquals($expected->instanceid, $stepresults['test_instanceid']);
        $this->assertEquals($expected->path, $stepresults['test_path']);
        $this->assertEquals($expected->depth, $stepresults['test_depth']);

        // Hardcoded id as string.
        $step = new \tool_trigger\steps\lookups\context_lookup_step(
            json_encode([
                'instanceid' => (string)$this->$type->id,
                'contextlevel' => $contextlevel,
                'mod' => ($contextlevel == CONTEXT_MODULE) ? 'page' : null,
                'outputprefix' => 'test_',
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $this->assertTrue($status);
        $this->assertEquals($expected->id, $stepresults['test_id']);
        $this->assertEquals($expected->contextlevel, $stepresults['test_contextlevel']);
        $this->assertEquals($expected->instanceid, $stepresults['test_instanceid']);
        $this->assertEquals($expected->path, $stepresults['test_path']);
        $this->assertEquals($expected->depth, $stepresults['test_depth']);

        // ID as field name from the workflow.
        $step = new \tool_trigger\steps\lookups\context_lookup_step(
            json_encode([
                'instanceid' => 'objectid',
                'contextlevel' => $contextlevel,
                'mod' => ($contextlevel == CONTEXT_MODULE) ? 'page' : null,
                'outputprefix' => 'test_'
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $this->assertTrue($status);
        $this->assertEquals($expected->id, $stepresults['test_id']);
        $this->assertEquals($expected->contextlevel, $stepresults['test_contextlevel']);
        $this->assertEquals($expected->instanceid, $stepresults['test_instanceid']);
        $this->assertEquals($expected->path, $stepresults['test_path']);
        $this->assertEquals($expected->depth, $stepresults['test_depth']);

        // Not existing ID.
        $step = new \tool_trigger\steps\lookups\context_lookup_step(
            json_encode([
                'instanceid' => 777,
                'contextlevel' => $contextlevel,
                'mod' => ($contextlevel == CONTEXT_MODULE) ? 'page' : null,
                'outputprefix' => 'test_'
            ])
        );

        list($status) = $step->execute(null, null, $this->event, []);
        $this->assertFalse($status);

        $step = new \tool_trigger\steps\lookups\context_lookup_step(
            json_encode([
                'instanceid' => 'nosuchfield',
                'contextlevel' => $contextlevel,
                'mod' => ($contextlevel == CONTEXT_MODULE) ? 'page' : null,
                'outputprefix' => 'test_'
            ])
        );

        $this->expectException('\moodle_exception');
        $step->execute(null, null, $this->event, []);
    }

}