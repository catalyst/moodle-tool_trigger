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
 * Helper task to offload upgrade processing
 *
 * @package    tool_trigger
 * @copyright  Catalyst IT, 2021
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\task;


/**
 * Helper task to offload upgrade processing
 *
 * @package    tool_trigger
 * @copyright  Catalyst IT, 2021
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_trigger_helper_task extends \core\task\adhoc_task {
    /**
     * Get task name
     */
    public function get_name() {
        return get_string('update_trigger_helper_task', 'tool_trigger');
    }

    /**
     * Execute task
     */
    public function execute() {
        global $DB;

        $rs = $DB->get_recordset('tool_trigger_workflow_hist');
        foreach ($rs as $record) {
            // New feature: Export workflow and run history.
            // 2021030402
            // Update the new userid field with the associated user from the json eventdata.
            $eventdata = json_decode($record->event);
            if (!empty($eventdata->userid)) {
                $record->userid = $eventdata->userid;
            } else {
                $record->userid = 0;
            }

            // Workflow history indicates pending error status.
            // 2021030403
            // Update the new attemptnum field with the default number of attempts.
            $record->attemptnum = 1;
            $DB->update_record('tool_trigger_workflow_hist', $record);
        }
        $rs->close();
    }
}
