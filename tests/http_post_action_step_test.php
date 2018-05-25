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
 * Test of the HTTP POST action step.
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/trigger/guzzle/autoloader.php');

class tool_trigger_http_post_action_step_testcase extends advanced_testcase {

    public function setup() {
        $this->resetAfterTest(true);

        $this->requests_sent = [];
        $this->user = \core_user::get_user_by_username('admin');
        $this->event = \core\event\user_profile_viewed::create([
            'objectid' => $this->user->id,
            'relateduserid' => $this->user->id,
            'context' => context_user::instance($this->user->id),
            'other' => [
                'courseid' => 1,
                'courseshortname' => 'short name',
                'coursefullname' => 'full name'
            ]
        ]);

        // Run as the cron user  .
        cron_setup_user();
    }

    private function make_mock_http_handler($response) {

        $stack = \GuzzleHttp\HandlerStack::create(
            new \GuzzleHttp\Handler\MockHandler([$response])
        );
        $stack->push(
            \GuzzleHttp\Middleware::history($this->requests_sent)
        );

        return $stack;
    }

    /**
     * Simple test, with a successful response
     */
    public function test_execute_200() {
        $stepsettings = [
            'url' => 'http://http_post_action_step.example.com',
            'httpheaders' => '',
            'httpparams' => '',
            'jsonencode' => '0'
        ];
        $step = new \tool_trigger\steps\actions\http_post_action_step(json_encode($stepsettings));

        $response = new \GuzzleHttp\Psr7\Response(200, [], 'OK', 1.1, 'All good');
        $step->set_http_client_handler($this->make_mock_http_handler($response));

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $this->assertTrue($status);
        $this->assertEquals(1, count($this->requests_sent));
        $this->assertEquals(200, $stepresults['http_response_status_code']);
        $this->assertEquals('All good', $stepresults['http_response_status_message']);
        $this->assertEquals('OK', $stepresults['http_response_body']);
    }

    /**
     * Test that we properly handle a 404 response. Guzzle will throw an exception in this
     * case, but the action step should catch the exception and handle it.
     */
    public function test_execute_404() {
        $stepsettings = [
            'url' => 'http://http_post_action_step.example.com/badurl',
            'httpheaders' => '',
            'httpparams' => '',
            'jsonencode' => '0'
        ];
        $step = new \tool_trigger\steps\actions\http_post_action_step(json_encode($stepsettings));

        $response = new \GuzzleHttp\Psr7\Response(404, [], json_encode(false), 1.1, 'Huh?');
        $step->set_http_client_handler($this->make_mock_http_handler($response));

        list($status, $stepresults) = $step->execute(null, null, $this->event, []);

        $this->assertTrue($status);
        $this->assertEquals(1, count($this->requests_sent));
        $this->assertEquals(404, $stepresults['http_response_status_code']);
        $this->assertEquals('Huh?', $stepresults['http_response_status_message']);
        $this->assertEquals(json_encode(false), $stepresults['http_response_body']);
    }

    /**
     * Test that datafield placeholders in the step's settings are handled properly.
     * Placeholders in the "http headers" setting can go in as-is, but placeholders
     * in the url and http params need to be urlencoded.
     */
    public function test_execute_with_datafields() {
        $stepsettings = [
                'url' => 'http://api.example.com/?returnurl={returnurl}&lang=en',
                'httpheaders' => 'My-Special-Header: {headervalue}',
                'httpparams' => 'a={a}&b={b}&c={c}&d=1',
                'jsonencode' => '0'
        ];
        $step = new \tool_trigger\steps\actions\http_post_action_step(json_encode($stepsettings));

        $response = new \GuzzleHttp\Psr7\Response(200, [], 'OK', 1.1, 'All good');
        $step->set_http_client_handler($this->make_mock_http_handler($response));

        // In actual use, these might be datafields added by previous workflow steps.
        $prevstepresults = [
            'headervalue' => 'Check check 1 2 1 2',
            // Check that this gets properly urlencoded.
            'returnurl' => 'http://returnurl.example.com?id=35&lang=en',
            // Check that these get properly urlencoded.
            'a' => '1005',
            'b' => '?.&=;',
            'c' => 'c'
        ];

        list($status) = $step->execute(null, null, $this->event, $prevstepresults);

        $this->assertTrue($status);
        $this->assertEquals(1, count($this->requests_sent));

        // Inspect the (mock) requests sent, to check that the substitutions were successful.
        $request = $this->requests_sent[0]['request'];

        // The datafield in the header line didn't need to be urlencoded, so it should be exactly the same.
        $this->assertEquals(
            $prevstepresults['headervalue'],
            $request->getHeaderLine('My-Special-Header')
        );

        // The "returnurl" datafield in the request URL should be urlencoded.
        $this->assertEquals(
            "http://api.example.com/?returnurl=http%3A%2F%2Freturnurl.example.com%3Fid%3D35%26lang%3Den&lang=en",
            (string) $request->getUri()
        );

        // The datafields in the request body should also be urlencoded.
        $this->assertEquals(
            "a=1005&b=%3F.%26%3D%3B&c=c&d=1",
            $request->getBody()->getContents()
        );

    }

    /**
     * Test that datafield placeholders in the step's settings are handled properly
     * and that the parameters data is correctly converted into JSON.
     * Placeholders in the "http headers" setting can go in as-is, but placeholders
     * in the url and http params need to be urlencoded.
     */
    public function test_execute_with_datafields_json() {
        $stepsettings = [
                'url' => 'http://api.example.com/?returnurl={returnurl}&lang=en',
                'httpheaders' => 'My-Special-Header: {headervalue}',
                'httpparams' => 'a={a}&b={b}&c={c}&d=1',
                'jsonencode' => '1'
        ];
        $step = new \tool_trigger\steps\actions\http_post_action_step(json_encode($stepsettings));

        $response = new \GuzzleHttp\Psr7\Response(200, [], 'OK', 1.1, 'All good');
        $step->set_http_client_handler($this->make_mock_http_handler($response));

        // In actual use, these might be datafields added by previous workflow steps.
        $prevstepresults = [
                'headervalue' => 'Check check 1 2 1 2',
                // Check that this gets properly urlencoded.
                'returnurl' => 'http://returnurl.example.com?id=35&lang=en',
                // Check that these get properly urlencoded.
                'a' => '1005',
                'b' => '?.&=;',
                'c' => 'c'
        ];

        list($status) = $step->execute(null, null, $this->event, $prevstepresults);

        $this->assertTrue($status);
        $this->assertEquals(1, count($this->requests_sent));

        // Inspect the (mock) requests sent, to check that the substitutions were successful.
        $request = $this->requests_sent[0]['request'];

        // The datafield in the header line didn't need to be urlencoded, so it should be exactly the same.
        $this->assertEquals(
                $prevstepresults['headervalue'],
                $request->getHeaderLine('My-Special-Header')
                );

        // The "returnurl" datafield in the request URL should be urlencoded.
        $this->assertEquals(
                "http://api.example.com/?returnurl=http%3A%2F%2Freturnurl.example.com%3Fid%3D35%26lang%3Den&lang=en",
                (string) $request->getUri()
                );

        // The datafields in the request body should be JSON encoded.
        $this->assertEquals(
                '{"a":"1005","b":"?.","c":"c","d":"1"}',
                $request->getBody()->getContents()
                );

    }
}