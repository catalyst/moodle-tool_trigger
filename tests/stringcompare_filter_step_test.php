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
require_once(__DIR__.'/fixtures/user_event_fixture.php');

use \tool_trigger\steps\filters\stringcompare_filter_step;

class tool_trigger_stringcompare_filter_step_testcase extends advanced_testcase {
    use \tool_trigger_user_event_fixture;

    /**
     * Create a "user_profile_viewed" event, of user1 viewing user2's
     * profile. And then run everything else as the cron user.
     */
    public function setup() {
        $this->setup_user_event();
    }

    /**
     * Test the basic working of all the operators, using a configuration where
     * both sides are hard-coded strings without {placeholders}.
     *
     * @param string $operator
     * @param string $comparator
     * @param boolean $expectedresult
     *
     * @dataProvider operator_permutations
     */
    public function test_all_operators($operator, $comparator, $expectedresult) {
        $stepconfig = [
            'field1' => 'Aaron Wells',
            'operator' => $operator,
            'field2' => $comparator,
            'wantmatch' => true
        ];
        $step = new \tool_trigger\steps\filters\stringcompare_filter_step(
            json_encode($stepconfig)
        );

        list($status) = $step->execute(null, null, $this->event, []);
        $this->assertEquals($expectedresult, $status);

        // Check that the "does not" setting works correctly.
        $reversestepconfig = $stepconfig;
        $reversestepconfig['wantmatch'] = false;
        $reversestep = new \tool_trigger\steps\filters\stringcompare_filter_step(
            json_encode($reversestepconfig)
        );

        list($status) = $reversestep->execute(null, null, $this->event, []);
        $this->assertEquals(!$expectedresult, $status);
    }

    /**
     * Testing every operator for comparison against something that does, and does not, match.
     * To simplify things, the "haystack" or "subject" field is expected to be
     * hard-coded to "Aaron Wells".
     *
     * @return array
     */
    public function operator_permutations() {
        return [
            [stringcompare_filter_step::OPERATOR_EQUAL, 'Aaron Wells', true],
            [stringcompare_filter_step::OPERATOR_EQUAL, 'Aaron Burr', false],
            [stringcompare_filter_step::OPERATOR_CONTAINS, 'Well', true],
            [stringcompare_filter_step::OPERATOR_CONTAINS, 'Ill', false],
            [stringcompare_filter_step::OPERATOR_STARTS_WITH, 'Aaron ', true],
            [stringcompare_filter_step::OPERATOR_STARTS_WITH, 'Herbert ', false],
            [stringcompare_filter_step::OPERATOR_ENDS_WITH, ' Wells', true],
            [stringcompare_filter_step::OPERATOR_ENDS_WITH, ' Burr', false],
            // Put some braces in the regex to verify that it works okay with our
            // datafield {placeholder} syntax. There shouldn't ever really be
            // a problem, because datafields have an alphabetic component, and
            // regexes only accept integers and commas inside of {}.
            [stringcompare_filter_step::OPERATOR_REGEX, '^[A-Z][a-z]{4} ', true],
            [stringcompare_filter_step::OPERATOR_REGEX, '[0-9]', false]
        ];
    }

    /**
     * Test that datafield substitution works correctly.
     */
    public function test_datafield() {
        $step = new stringcompare_filter_step(
            json_encode([
                // Put a datafield placeholder in field1.
                'field1' => '{other_coursefullname} and such',
                'operator' => stringcompare_filter_step::OPERATOR_STARTS_WITH,
                // The course we created during the event setup.
                'field2' => $this->course->fullname,
                'wantmatch' => true
            ])
        );

        list($status) = $step->execute(null, null, $this->event, []);

        $this->assertTrue($status);
    }

    public function test_invalidregex() {
        $step = new stringcompare_filter_step(
            json_encode([
                'field1' => 'foo',
                'operator' => stringcompare_filter_step::OPERATOR_REGEX,
                'field2' => '***[',
                'wantmatch' => true
            ])
        );

        $this->expectException('\invalid_parameter_exception');
        $step->execute(null, null, $this->event, []);
    }
}