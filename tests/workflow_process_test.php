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
 * Workflow form processing unit tests.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Workflow form processing unit tests.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_trigger_workflow_process_testcase extends advanced_testcase {

    /**
     * Test workflow process form data
     */
    public function test_processform() {
        $this->resetAfterTest();
        global $DB;

        $mdata = new \stdClass();
        $mdata->workflowid = 0;
        $mdata->workflowname = 'test workflow';
        $mdata->workflowdescription = 'test workflow description';
        $mdata->eventtomonitor = '\mod_scorm\event\user_report_viewed';
        $mdata->workflowactive = 1;
        $mdata->draftmode = 0;
        $mdata->isstepschanged = 1;
        $mdata->stepjson = '[{"id":"0","type":"action","stepclass":"/steps/action/log_step",'
                            .'"steporder":"0","name":"test step","description":"test step description"}]';

        $workflowprocess = new \tool_trigger\workflow_process($mdata);
        $result = $workflowprocess->processform();

        // Check that some values were actually written to db.
        $workflowexists = $DB->record_exists('tool_trigger_workflows', array('name' => 'test workflow'));
        $stepexists = $DB->record_exists('tool_trigger_steps', array('name' => 'test step'));

        $this->assertTrue($workflowexists);
        $this->assertTrue($stepexists);
        $this->assertTrue($result);
    }

    /**
     * Test workflow process step json data.
     */
    public function test_processjson() {
        $mdata = new \stdClass();
        $json = '[{"id":"0","type":"action","stepclass":"/steps/action/log_step","steporder":"0"'
            . ',"name":"test step","description":"test step description"}]';
        $now = 1521773594;

        $expected = new \stdClass();
        $expected->id = 0;
        $expected->workflowid = 1;
        $expected->timecreated = $now;
        $expected->timemodified = $now;
        $expected->type = 'action';
        $expected->stepclass = '/steps/action/log_step';
        $expected->steporder = 0;
        $expected->name = 'test step';
        $expected->description = 'test step description';
        $expected->data = '';

        $workflowprocess = new \tool_trigger\workflow_process($mdata);
        $result = $workflowprocess->processjson($json, 1, $now);

        $this->assertEquals($expected, $result[0]);
    }

    /**
     * Test workflow process step json data with mulitple steps.
     */
    public function test_processjson_multiple_steps() {
        $mdata = new \stdClass();
        $json = '['
                . '{"id":"0","type":"action","stepclass":"/steps/action/log_step",'
                . '"steporder":"0","name":"step 1 name","description":"step 1 description"}'
                . ',{"id":"0","type":"action","stepclass":"/steps/action/log_step",'
                . '"steporder":"1","name":"step 2 name","description":"step 2 description"}'
                . ']';
        $now = 1521773594;

        $expected1 = new \stdClass ();
        $expected1->id = 0;
        $expected1->workflowid = 1;
        $expected1->timecreated = $now;
        $expected1->timemodified = $now;
        $expected1->type = 'action';
        $expected1->stepclass = '/steps/action/log_step';
        $expected1->steporder = 0;
        $expected1->name = 'step 1 name';
        $expected1->description = 'step 1 description';
        $expected1->data = '';

        $expected2 = new \stdClass ();
        $expected2->id = 0;
        $expected2->workflowid = 1;
        $expected2->timecreated = $now;
        $expected2->timemodified = $now;
        $expected2->type = 'action';
        $expected2->stepclass = '/steps/action/log_step';
        $expected2->steporder = 1;
        $expected2->name = 'step 2 name';
        $expected2->description = 'step 2 description';
        $expected2->data = '';

        $workflowprocess = new \tool_trigger\workflow_process ($mdata);
        $result = $workflowprocess->processjson ($json, 1, $now);

        $this->assertEquals ($expected1, $result[0]);
        $this->assertEquals ($expected2, $result[1]);
    }

    public function test_import_prep () {
        global $CFG;

        $filename = $CFG->dirroot . '/admin/tool/trigger/tests/fixtures/' . 'Test_login_failed_workflow_20180826_0058.json';
        $fp = fopen($filename, 'r');
        $filecontentjson = fread($fp, filesize($filename));
        fclose($fp);

        $expecteddescription = new \stdClass();
        $expecteddescription->text = '<p>A workflow to use as a test fixture for the worklfow import process.</p>'
            .'<p>It is triggered on a user login failed event<br></p>';
        $expecteddescription->format = '1';

        $expectedsteps = array(
            array(
                 'name' => 'Test fixture user lookup',
                 'description' => 'A user step that gets user profile imformation',
                 'type' => 'lookups',
                 'stepclass' => '\\tool_trigger\\steps\\lookups\\user_lookup_step',
                 'steporder' => '0',
                 'useridfield' => 'userid',
                 'outputprefix' => 'user_',
                 'nodeleted' => '1',
                 'stepdesc' => 'User lookup',
                 'typedesc' => 'Lookup'
             ),
             array(
                 'name' => 'Test fixture cron log',
                 'description' => 'A step that dumps workflow output to the cron log.',
                 'type' => 'actions',
                 'stepclass' => '\\tool_trigger\\steps\\actions\\logdump_action_step',
                 'steporder' => '1',
                 'stepdesc' => 'Cron log',
                 'typedesc' => 'Action'
             )
             );

        $expected = new \stdClass ();
        $expected->workflowid = 0;
        $expected->workflowname = 'Test login failed workflow';
        $expected->workflowdescription = $expecteddescription;
        $expected->eventtomonitor = '\core\event\user_login_failed';
        $expected->workflowactive = 0;
        $expected->draftmode = 0;
        $expected->isstepschanged = 1;
        $expected->stepjson = json_encode($expectedsteps);

        $workflowobj = \tool_trigger\workflow_process::import_prep($filecontentjson);

        $this->assertEquals ($expected, $workflowobj);

    }
}
