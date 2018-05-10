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
 * Cleanup old events/processed tasks.
 *
 * @package    tool_trigger
 * @copyright  Catalyst IT
 * @author     Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\task;

defined('MOODLE_INTERNAL') || die();
/**
 * Task to cleanup old queue.
 */
class cleanup extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskcleanup', 'tool_trigger');
    }

    /**
     * Processes workflows.
     */
    public function execute() {
        global $DB;
        $timetocleanup = get_config('timetocleanup', 'tool_trigger');
        if (empty($timetocleanup)) {
            return;
        }
        $timetocleanup = time() - $timetocleanup;

        // Delete events first so that we don't accidentally create new queue items.

        // Sql to get ids of events that are linked to an incomplete trigger.
        $activesql = "SELECT e.id FROM {tool_trigger_events} e
                        JOIN {tool_trigger_queue} q ON q.eventid = e.id
                       WHERE q.status <> 1";

        // First get all events that are older than the timeframe and do not have an open trigger.
        $sql = "DELETE FROM {tool_trigger_events}
                 WHERE id NOT IN (".$activesql.") AND timecreated < ?";
        $DB->execute($sql, array($timetocleanup));

        // Now cleanup processed queue older than the timeframe.
        $sql = "DELETE FROM {tool_trigger_queue}
                 WHERE q.status = 1 AND timemodified < ?";
        $DB->execute($sql, array($timetocleanup));
    }
}