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

class tool_trigger_process_workflow_testcase extends advanced_testcase {

    public function setup() {
        global $DB;
        $this->resetAfterTest(true);

        // Create an event. This _is_ easier to do via direct DB insertions.
        $user = $this->getDataGenerator()->create_user();
        $context = \context_system::instance();

        $eventobj = new \stdClass();
        $eventobj->eventname = '\core\event\user_loggedin';
        $eventobj->component = 'core';
        $eventobj->action = 'loggedin';
        $eventobj->target = 'user';
        $eventobj->objecttable = 'user';
        $eventobj->objectid = $user->id;
        $eventobj->crud = 'r';
        $eventobj->edulevel = 0;
        $eventobj->contextid = $context->id;
        $eventobj->contextlevel = $context->contextlevel;
        $eventobj->contextinstanceid = $context->instanceid;
        $eventobj->userid = $user->id;
        $eventobj->courseid = 0;
        $eventobj->relateduserid = null;
        $eventobj->anonymous = 0;
        $eventobj->other = serialize(['username' => $user->username]);
        $eventobj->timecreated = time();
        $eventobj->origin = 'web';
        $eventobj->ip = '127.0.0.1';
        $eventobj->realuserid = null;

        $eventid = $DB->insert_record('tool_trigger_events', $eventobj);
        $eventobj->id = $eventid;
        $this->eventobj = $eventobj;
        $this->user = $user;
        $this->context = $context;

        // Run as the cron user  .
        cron_setup_user();

    }

    /**
     * Test a workflow that monitors login events, looks up the user's name,
     * and emails it to the admin.
     *
     * TODO: test a workflow that throws an execption
     */
    public function test_process_workflow() {
        $mdata = new \stdClass();
        $mdata->workflowid = 0;
        $mdata->workflowname = 'Email me about login';
        $mdata->workflowdescription = 'When a user logs in, email me.';
        $mdata->eventtomonitor = '\core\event\user_loggedin';
        $mdata->workflowactive = 1;
        $mdata->draftmode = 0;
        $mdata->isstepschanged = 1;
        $mdata->stepjson = json_encode([
            [
                'id' => 0,
                'type' => 'lookups',
                'stepclass' => '\tool_trigger\steps\lookups\user_lookup_step',
                'steporder' => '0',
                'name' => 'Get user data',
                'description' => 'Get user data',
                'useridfield' => 'userid',
                'outputprefix' => 'user_'
            ],
            [
                'id' => 0,
                'type' => 'actions',
                'stepclass' => '\tool_trigger\steps\actions\email_action_step',
                'steporder' => '1',
                'name' => 'Email user data to me',
                'description' => 'Email user data to me',
                'emailto' => \core_user::get_user_by_username('admin')->email,
                'emailsubject' => '{user_firstname} {user_lastname} logged in',
                'emailcontent' => '{user_email} logged in.'
            ]
        ]);

        // Insert it into the database. (It seems like it'll be more robust to do this
        // by calling workflow_process rather than doing it by hand.)
        $workflowprocess = new \tool_trigger\workflow_process($mdata);
        $workflowid = $workflowprocess->processform();

        // Capture email messages.
        unset_config('noemailever');
        $messagesink = $this->redirectMessages();

        // Run the task.
        $task = new \tool_trigger\task\process_workflows();
        $this->expectOutputRegex('/Executing workflow/');
        $task->execute();

        // Make sure the output didn't contain any warning or error messages.
        $this->assertNotContains('warning', strtolower($this->getActualOutput()));
        $this->assertNotContains('error', strtolower($this->getActualOutput()));
        $this->assertNotContains('debug', strtolower($this->getActualOutput()));

        $messages = $messagesink->get_messages();
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $this->assertEquals(
            \core_user::get_user_by_username('admin')->id,
            $message->useridto
        );
        $this->assertEquals(
            "{$this->user->firstname} {$this->user->lastname} logged in",
            $message->subject
        );
        $this->assertEquals(
            "{$this->user->email} logged in.",
            $message->fullmessage
        );

        // TODO: Test that the records in the tool_trigger_queue table are
        // correct.
        // TODO: Test that this workflow won't execute a second time for the
        // same event.
    }
}