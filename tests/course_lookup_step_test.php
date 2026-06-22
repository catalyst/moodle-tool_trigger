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

namespace tool_trigger;

/**
 * "Fail" filter step's unit tests.
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course_lookup_step_test extends \advanced_testcase {

    /**
     * Test user.
     * @var \stdClass
     */
    protected $user;

    /**
     * Test course.
     * @var \stdClass
     */
    protected $course;

    /**
     * Event for testing.
     * @var \core\event\user_graded
     */
    private $event;

    /**
     * Create a "user_profile_viewed" event, of user1 viewing user2's
     * profile. And then run everything else as the cron user.
     */
    public function setup(): void {
        $this->resetAfterTest(true);
        $this->user = \core_user::get_user_by_username('admin');
        $this->course = $this->getDataGenerator()->create_course();

        $this->setUser($this->user);

        $this->event = \core\event\course_created::create([
            'objectid' => $this->course->id,
            'context' => \context_course::instance($this->course->id),
            'other' => [
                'shortname' => $this->course->shortname,
                'fullname' => $this->course->fullname,
            ],
        ]);

        // Run as the cron user  .
        \core\cron::setup_user();
    }

    /**
     * Find the course identified at "objectid", and add their data with the
     * prefix "course_".
     */
    public function test_execute_basic(): void {
        $step = new \tool_trigger\steps\lookups\course_lookup_step(
            json_encode([
                'courseidfield' => 'objectid',
                'outputprefix' => 'course_',
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);
        $context = \context_course::instance($this->course->id);

        $this->assertTrue($status);
        $this->assertEquals($this->course->id, $stepresults['course_id']);
        $this->assertEquals($this->course->fullname, $stepresults['course_fullname']);
        $this->assertEquals($context->id, $stepresults['course_contextid']);
    }

    /**
     * Basic test, but this time with additional custom course field.
     */
    public function test_execute_basic_with_custom_profile_fields() {
        // Create custom course field.
        $customfieldg = $this->getDataGenerator()->get_plugin_generator('core_customfield');
        $category  = $customfieldg->create_category();
        $customfield = $customfieldg->create_field([
            'categoryid' => $category->get('id'),
            'type' => 'text',
            'shortname' => 'testfield1',
            'configdata' => [],
        ]);

        // Add data to the customfield_data table.
        $this->add_course_custom_course_field_data($customfield->get('id'), $this->course->id, 'CourseFieldValue');
        $step = new \tool_trigger\steps\lookups\course_lookup_step(
            json_encode([
                'courseidfield' => 'objectid',
                'outputprefix' => 'course_'
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);
        $this->assertTrue($status);
        // Check that the custom field data is returned as a step result.
        $this->assertEquals('CourseFieldValue', $stepresults['course_testfield1']);
    }

    /**
     * Test for exception if an invalid field name is entered.
     */
    public function test_execute_nosuchfield(): void {
        $step = new \tool_trigger\steps\lookups\course_lookup_step(
            json_encode([
                'courseidfield' => 'nosuchfield',
                'outputprefix' => 'course_',
            ])
        );

        $this->expectException('\moodle_exception');
        $step->execute(null, null, $this->event, []);
    }

    /**
     * Test for failure if a course is no longer present in the database.
     */
    public function test_execute_nosuchcourse(): void {
        delete_course($this->course, false);

        $step = new \tool_trigger\steps\lookups\course_lookup_step(
            json_encode([
                'courseidfield' => 'objectid',
                'outputprefix' => 'course_',
            ])
        );

        list($status) = $step->execute(null, null, $this->event, []);
        $this->assertFalse($status);
    }

    /**
     * Data provided to test hardcoded category id.
     * @return array
     */
    public function hardcoded_course_id_data_provider(): array {

        return [
            'Non-existing Course id.' => [
                777777,
                false,
                false,
            ],
            'Nil Course id.' => [
                0,
                false,
                true,
            ],
            'Empty string Course id.' => [
                '',
                false,
                true,
            ],
            'Null Course id.' => [
                null,
                false,
                true,
            ],
        ];
    }

    /**
     * Test for exception if course id entered directly.
     *
     * @dataProvider hardcoded_course_id_data_provider
     */
    public function test_execute_course_id($courseid, $status, $exception): void {
        $step = new \tool_trigger\steps\lookups\course_lookup_step(
            json_encode([
                'courseidfield' => $courseid,
                'outputprefix' => 'course_',
            ])
        );

        if ($exception) {
            $this->expectException(\invalid_parameter_exception::class);
            $this->expectExceptionMessageMatches("/Specified courseid field not present in the workflow data:*/");
        }

        list($statusresult, $stepresults) = $step->execute(null, null, $this->event, []);

        if ($status) {
            $context = \context_course::instance($this->course->id);
            $this->assertTrue($statusresult);
            $this->assertEquals($this->course->id, $stepresults['course_id']);
            $this->assertEquals($this->course->fullname, $stepresults['course_fullname']);
            $this->assertEquals($context->id, $stepresults['course_contextid']);
        } else {
            $this->assertFalse($statusresult);
        }
    }

    /**
     * Test for exception if course id entered directly with dynamic id as integer.
     */
    public function test_execute_course_id_integer(): void {
        $step = new \tool_trigger\steps\lookups\course_lookup_step(
            json_encode([
                'courseidfield' => $this->course->id,
                'outputprefix' => 'course_',
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $context = \context_course::instance($this->course->id);
        $this->assertTrue($status);
        $this->assertEquals($this->course->id, $stepresults['course_id']);
        $this->assertEquals($this->course->fullname, $stepresults['course_fullname']);
        $this->assertEquals($context->id, $stepresults['course_contextid']);
    }

    /**
     * Test for exception if course id entered directly with dynamic id as string.
     */
    public function test_execute_course_id_string(): void {
        $step = new \tool_trigger\steps\lookups\course_lookup_step(
            json_encode([
                'courseidfield' => (string)$this->course->id,
                'outputprefix' => 'course_',
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $context = \context_course::instance($this->course->id);
        $this->assertTrue($status);
        $this->assertEquals($this->course->id, $stepresults['course_id']);
        $this->assertEquals($this->course->fullname, $stepresults['course_fullname']);
        $this->assertEquals($context->id, $stepresults['course_contextid']);
    }

    public function add_course_custom_course_field_data($fieldid, $courseid, $customfielddata) {
        global $DB;

        // Add data to the customfield_data table.
        $data = new \stdClass();
        $data->fieldid = $fieldid;
        $data->instanceid = $courseid;
        $data->value = $customfielddata;
        $data->charvalue = $customfielddata;
        $data->timecreated = time();
        $data->timemodified = time();
        $data->valueformat = 0;
        $data->valuetrust = 0;
        $data->contextid = \context_course::instance($courseid)->id;

        return $DB->insert_record('customfield_data', $data);
    }
}
