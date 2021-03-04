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

class tool_trigger_course_cat_lookup_step_testcase extends advanced_testcase {

    /**
     * Test user.
     * @var
     */
    protected $user;

    /**
     * Test category.
     * @var
     */
    protected $category;

    /**
     * Test event.
     * @var
     */
    protected $event;

    /**
     * Initial set up.
     */
    public function setUp():void {
        parent::setUp();

        $this->resetAfterTest(true);
        $this->user = \core_user::get_user_by_username('admin');
        $this->category = $this->getDataGenerator()->create_category();

        $this->setUser($this->user);

        $this->event = \core\event\course_category_created::create([
            'objectid' => $this->category->id,
            'context' => context_coursecat::instance($this->category->id),
        ]);

        // Run as the cron user  .
        cron_setup_user();
    }

    /**
     * Test fields list.
     */
    public function test_get_fields() {
        $expected = [
            'id',
            'name',
            'idnumber',
            'description',
            'descriptionformat',
            'parent',
            'sortorder',
            'coursecount',
            'visible',
            'visibleold',
            'timemodified',
            'depth',
            'path',
            'theme',
            'contextid',
        ];
        $this->assertEquals($expected, \tool_trigger\steps\lookups\course_cat_lookup_step::get_fields());
    }

    /**
     * Find the category identified at "objectid", and add their data with the
     * prefix "category_".
     */
    public function test_execute_basic() {
        $step = new \tool_trigger\steps\lookups\course_cat_lookup_step(
            json_encode([
                'categoryidfield' => 'objectid',
                'outputprefix' => 'category_'
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);
        $context = context_coursecat::instance($this->category->id);

        $this->assertTrue($status);
        $this->assertEquals($this->category->id, $stepresults['category_id']);
        $this->assertEquals($this->category->name, $stepresults['category_name']);
        $this->assertEquals($context->id, $stepresults['category_contextid']);
    }

    /**
     * Test for exception if an invalid field name is entered.
     */
    public function test_execute_nosuchfield() {
        $step = new \tool_trigger\steps\lookups\course_cat_lookup_step(
            json_encode([
                'categoryidfield' => 'nosuchfield',
                'outputprefix' => 'category_'
            ])
        );

        $this->expectException('\moodle_exception');
        $step->execute(null, null, $this->event, []);
    }

    /**
     * Test for failure if a category is no longer present in the database.
     */
    public function test_execute_no_such_category() {
        global $DB;

        $DB->delete_records('course_categories', ['id' => $this->category->id]);

        $step = new \tool_trigger\steps\lookups\course_cat_lookup_step(
            json_encode([
                'categoryidfield' => 'objectid',
                'outputprefix' => 'category_'
            ])
        );

        list($status) = $step->execute(null, null, $this->event, []);
        $this->assertFalse($status);
    }

    /**
     * Data provided to test hardcoded category id.
     * @return array
     */
    public function hardcoded_category_id_data_provider() {

        return [
            'Non-existing category id.' => [
                777777,
                false,
                false,
            ],
            'Nil category id.' => [
                0,
                false,
                true,
            ],
            'Empty string category id.' => [
                '',
                false,
                true,
            ],
            'Null category id.' => [
                null,
                false,
                true,
            ],
        ];
    }

    /**
     * Test hardcoded category id.
     *
     * @dataProvider hardcoded_category_id_data_provider
     */
    public function test_execute_category_id($categoryid, $status, $exception) {
        $step = new \tool_trigger\steps\lookups\course_cat_lookup_step(
            json_encode([
                'categoryidfield' => $categoryid,
                'outputprefix' => 'category_'
            ])
        );

        if ($exception) {
            $this->expectException('\moodle_exception');
            $this->expectExceptionMessageRegExp("/Specified category field not present in the workflow data:*/");
        }

        list($statusresult, $stepresults) = $step->execute(null, null, $this->event, []);

        if ($status) {
            $context = context_coursecat::instance($this->category->id);
            $this->assertTrue($statusresult);
            $this->assertEquals($this->category->id, $stepresults['category_id']);
            $this->assertEquals($this->category->name, $stepresults['category_name']);
            $this->assertEquals($context->id, $stepresults['category_contextid']);
        } else {
            $this->assertFalse($statusresult);
        }
    }

    /**
     * Test dynamic category id as int.
     */
    public function test_execute_category_id_integer() {
        $step = new \tool_trigger\steps\lookups\course_cat_lookup_step(
            json_encode([
                'categoryidfield' => $this->category->id,
                'outputprefix' => 'category_'
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $context = context_coursecat::instance($this->category->id);
        $this->assertTrue($status);
        $this->assertEquals($this->category->id, $stepresults['category_id']);
        $this->assertEquals($this->category->name, $stepresults['category_name']);
        $this->assertEquals($context->id, $stepresults['category_contextid']);
    }

    /**
     * Test dynamic category id as string.
     */
    public function test_execute_category_id_string() {
        $step = new \tool_trigger\steps\lookups\course_cat_lookup_step(
            json_encode([
                'categoryidfield' => (string) $this->category->id,
                'outputprefix' => 'category_'
            ])
        );

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $context = context_coursecat::instance($this->category->id);
        $this->assertTrue($status);
        $this->assertEquals($this->category->id, $stepresults['category_id']);
        $this->assertEquals($this->category->name, $stepresults['category_name']);
        $this->assertEquals($context->id, $stepresults['category_contextid']);
    }
}
