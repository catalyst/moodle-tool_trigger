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
 * Tool trigger test case.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Tool trigger test case.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

abstract class tool_trigger_testcase extends advanced_testcase {

    /**
     * Helper function to create a test workflow.
     *
     * @param int $realtime Is realtime workflow?
     * @param array $steps A list of steps.
     * @return int $workflowid The id of the created workflow.
     */
    public function create_workflow($realtime = 0, $steps = [], $debug = 0) {
        if (empty($steps)) {
            $steps = [
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
            ];
        }

        $mdata = new \stdClass();
        $mdata->workflowid = 0;
        $mdata->workflowname = 'Email me about login';
        $mdata->workflowdescription = 'When a user logs in, email me.';
        $mdata->eventtomonitor = '\core\event\user_loggedin';
        $mdata->workflowactive = 1;
        $mdata->workflowrealtime = $realtime;
        $mdata->workflowdebug = $debug;
        $mdata->draftmode = 0;
        $mdata->isstepschanged = 1;
        $mdata->stepjson = json_encode($steps);

        // Insert it into the database. (It seems like it'll be more robust to do this
        // by calling workflow_process rather than doing it by hand.)
        $workflowprocess = new \tool_trigger\workflow_process($mdata);
        $workflowid = $workflowprocess->processform(0, true);

        // We now need to purge the event caches, so the new workflow is picked up.
        \cache_helper::purge_by_definition('tool_trigger', 'eventsubscriptions');

        return $workflowid;
    }
}
