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
 * @author     Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\task;

defined('MOODLE_INTERNAL') || die();
/**
 * Task to cleanup old queue.
 */
class cleanup_history extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskcleanuphistory', 'tool_trigger');
    }

    /**
     * Cleans up run data that is too old.
     */
    public function execute() {
        global $DB;

        $duration = get_config('tool_trigger', 'historyduration');
        if (empty($duration)) {
            mtrace(get_string('taskemptyhistoryconfig', 'tool_trigger'));
        }
        $lookback = time() - $duration;

        // Conditions: Delete steps executed older than duration,
        // But have to check if any steps within duration that rely on that step
        // To be safe for now. Keep all run steps if any in run are over duration.
        $sql = "SELECT rh.id FROM {tool_trigger_run_hist} rh
                      WHERE rh.executed < :lookback1
                        AND (
                            SELECT COUNT(id) as count
                              FROM {tool_trigger_run_hist} sub
                             WHERE sub.runid = rh.runid
                               AND sub.executed > :lookback2
                            ) = 0";
        $ids = $DB->get_fieldset_sql($sql, ['lookback1' => $lookback, 'lookback2' => $lookback]);
        if ($ids) {
            $DB->delete_records_list('tool_trigger_run_hist', 'id', $ids);
        }
    }
}
