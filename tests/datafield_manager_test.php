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
 * Test of the datafield_manager trait
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__.'/fixtures/user_event_fixture.php');

class tool_trigger_datafield_manager_testcase extends advanced_testcase {
    use \tool_trigger_user_event_fixture;

    /**
     * Create a "user_profile_viewed" event, of user1 viewing user2's
     * profile. And then run everything else as the cron user.
     */
    public function setup() {
        $this->setup_user_event();
    }

    /**
     * Test that all the datafields are correctly generated.
     */
    public function test_get_datafields() {
        $stepdata = ['foo' => 'bar'];

        // Tell PHPUnit to create a generic object that uses this trait. Handy!
        $dfprovider = $this->getMockForTrait('\tool_trigger\helper\datafield_manager');

        $dfprovider->update_datafields($this->event, $stepdata);
        $datafields = $dfprovider->get_datafields();

        // A field from the event object.
        $this->assertEquals(
            $this->user2->id,
            $datafields['objectid']
        );

        // A field from the event's "other" array (with its name prefaced
        // with "other_").
        $this->assertEquals(
            $this->course->fullname,
            $datafields['other_coursefullname']
        );

        // A field from the stepdata.
        $this->assertEquals(
            $stepdata['foo'],
            $datafields['foo']
        );

        // Update the data and make sure that the datafields are changed accordingly.
        $stepdata['foo'] = 'baz';
        $dfprovider->update_datafields($this->event, $stepdata);
        $datafields = $dfprovider->get_datafields();

        $this->assertEquals($this->user2->id, $datafields['objectid']);
        $this->assertEquals($this->course->fullname, $datafields['other_coursefullname']);
        $this->assertEquals($stepdata['foo'], $datafields['foo']);
    }

    /**
     * Test the function that inserts datafield values into template strings.
     * The data here tests for:
     *  - newline support in the template
     *  - multiple datafields available, some used, some not
     *  - multiple tags in the template
     *  - multiple instances of the same tag, in the template
     *  - some non-existent tags in the template (which should be left in place)
     */
    public function test_render_datafields() {
        $stepdata = [
            'tagnotused' => 'valuenotused',
            'tagexists' => 'tagvalue'
        ];

        $templatestring = 'Good tag: {tagexists}.
Bad tag: {nosuchtag}.
Good tag again: {tagexists}.';

        $dfprovider = $this->getMockForTrait('\tool_trigger\helper\datafield_manager');
        $dfprovider->update_datafields($this->event, $stepdata);

        $populatedstring = $dfprovider->render_datafields($templatestring);
        $this->assertEquals('Good tag: tagvalue.
Bad tag: {nosuchtag}.
Good tag again: tagvalue.', $populatedstring);
    }

    /**
     * Test the ability to provide a callback function to render_datafields(),
     * which will transform the tag's values before putting the values into
     * the template.
     *
     * For example, in the HTTP POST step, one template represents a bunch
     * of POST data in the form x={tag1}&b={tag2}. We need to urlencode the
     * values of tag1 and tag2 before inserting them into this template, but
     * we *don't* want to urlencode the entire thing, because that would
     * urlencode the "=" and "&" characters.
     *
     * For completeness' sake, the callback function receives the tag's value
     * and the tag's name, in case there's some use-case that requires both.
     */
    public function test_render_datafields_transformcallback() {
        $stepdata = [
            'tagnotused' => 'valuenotused',
            'tagexists' => 'tagvalue'
        ];

        $templatestring = 'Tag: {tagexists}';

        $dfprovider = $this->getMockForTrait('\tool_trigger\helper\datafield_manager');
        $dfprovider->update_datafields($this->event, $stepdata);

        $transformcallback = function($v, $k) {
            return strtoupper("$k = $v");
        };

        $populatedstring = $dfprovider->render_datafields($templatestring, null, null, $transformcallback);
        $this->assertEquals('Tag: TAGEXISTS = TAGVALUE', $populatedstring);
    }
}