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
 * Numeric comparison filter step's unit test
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/gradelib.php");
use \tool_trigger\steps\filters\numcompare_filter_step;

class tool_trigger_numcompare_filter_step_testcase extends advanced_testcase {
    /**
     * Create a "user_profile_viewed" event, of user1 viewing user2's
     * profile. And then run everything else as the cron user.
     */
    public function setup() {
        $this->resetAfterTest(true);

        // Grade event generation, copied from lib/tests/event_user_graded.php!
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $gradecategory = grade_category::fetch_course_category($course->id);
        $gradecategory->load_grade_item();
        $gradeitem = $gradecategory->grade_item;

        $gradeitem->update_final_grade($user->id, 10, 'gradebook');

        $gradegrade = new grade_grade(array('userid' => $user->id, 'itemid' => $gradeitem->id), true);
        $gradegrade->grade_item = $gradeitem;

        $this->event = \core\event\user_graded::create_from_grade($gradegrade);
    }

    /**
     *
     * @param string $operator
     * @param string $comparator
     * @param boolean $expectedresult
     *
     * @dataProvider operator_permutations
     */
    public function test_all_operators($operator, $comparator, $expectedresult) {
        $step = new \tool_trigger\steps\filters\numcompare_filter_step(
            json_encode([
                'field1' => $comparator,
                'operator' => $operator,
                'field2' => '0'
            ])
        );

        list($status) = $step->execute(null, null, $this->event, []);

        $this->assertEquals($expectedresult, $status);
    }

    /**
     * Testing every operator for comparison against something greater, equal, and lesser.
     * To simplify things, one comparotor is assumed to be hard-coded to 0.
     *
     * Note that we use string literals instead of number literals, because we're using form data which
     * will typically be a string.
     *
     * @return string[][]|boolean[][]
     */
    public function operator_permutations() {
        return [
            [ numcompare_filter_step::OPERATOR_EQUAL, '-100', false ],
            [ numcompare_filter_step::OPERATOR_EQUAL, '0', true ],
            [ numcompare_filter_step::OPERATOR_EQUAL, '100', false ],
            [ numcompare_filter_step::OPERATOR_NOTEQUAL, '-100', true ],
            [ numcompare_filter_step::OPERATOR_NOTEQUAL, '0', false ],
            [ numcompare_filter_step::OPERATOR_NOTEQUAL, '100', true ],
            [ numcompare_filter_step::OPERATOR_LT, '-100', true ],
            [ numcompare_filter_step::OPERATOR_LT, '0', false ],
            [ numcompare_filter_step::OPERATOR_LT, '100', false ],
            [ numcompare_filter_step::OPERATOR_LTE, '-100', true ],
            [ numcompare_filter_step::OPERATOR_LTE, '0', true ],
            [ numcompare_filter_step::OPERATOR_LTE, '100', false ],
            [ numcompare_filter_step::OPERATOR_GT, '-100', false ],
            [ numcompare_filter_step::OPERATOR_GT, '0', false ],
            [ numcompare_filter_step::OPERATOR_GT, '100', true ],
            [ numcompare_filter_step::OPERATOR_GT, '-100', false ],
            [ numcompare_filter_step::OPERATOR_GT, '0', false ],
            [ numcompare_filter_step::OPERATOR_GT, '100', true ]
        ];
    }

    public function test_datafield() {
        $step = new numcompare_filter_step(
            json_encode([
                // Should work if the datafield name is provided on its own...
                'field1' => 'other_finalgrade',
                'operator' => numcompare_filter_step::OPERATOR_EQUAL,
                // ... or if the datafield name is in {brackets}.
                'field2' => '{target_grade}'
            ])
        );

        list($status) = $step->execute(null, null, $this->event, ['target_grade' => '10']);

        $this->assertTrue($status);
    }

    public function test_nosuch_datafield() {
        $step = new numcompare_filter_step(
            json_encode([
                'field1' => 'nosuchfield',
                'operator' => numcompare_filter_step::OPERATOR_EQUAL,
                'field2' => '10'
            ])
            );

        $this->expectException('\invalid_parameter_exception');
        $step->execute(null, null, $this->event, []);
    }

    public function test_not_numeric() {
        $step = new numcompare_filter_step(
            json_encode([
                'field1' => 'stringfield',
                'operator' => numcompare_filter_step::OPERATOR_EQUAL,
                'field2' => '100'
            ])
        );

        $this->expectException('\invalid_parameter_exception');
        $step->execute(null, null, $this->event, ['stringfield' => 'This is not a number.']);
    }
}