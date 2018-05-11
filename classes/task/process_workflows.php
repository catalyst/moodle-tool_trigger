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
 * Process queued workflows.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\task;

defined('MOODLE_INTERNAL') || die();
/**
 * Simple task to rocess queued workflows.
 */
class process_workflows extends \core\task\scheduled_task {
    /** Max number of tries before ignoring task. */
    const MAXTRIES = 5;

    /** Max number of tasks to try and process in a queue. */
    const LIMITQUEUE = 500;

    /** Max processing time for a queue in seconds. */
    const MAXTIME = 60;

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskprocessworkflows', 'tool_trigger');
    }

    /**
     * Processes workflows.
     */
    public function execute() {
        $now = time();

        // Check events and create queue of workflows to process.
        $this->create_trigger_queue($now);

        // Now process queue.
        $sql = "SELECT q.id as qid, q.workflowid, q.status, q.tries, q.timecreated, q.timemodified,
                       e.id as eid, e.eventname, e.contextid, e.contextlevel, e.contextinstanceid, e.link, e.courseid, e.timecreated
                  FROM {tool_trigger_queue} q
                  JOIN {tool_trigger_workflows} w ON w.id = q.workflowid
                  JOIN {tool_trigger_events} e ON e.id = q.eventid
                  WHERE w.enabled = 1 AND q.status = 0 AND AND q.tries < ?
                  ORDER BY q.timecreated";

        $this->process_queue($sql, array(self::MAXTRIES), self::LIMITQUEUE, $now);
    }

    /** Create queue of workflows that need processing.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function create_trigger_queue($now) {
        global $DB;
        $triggerqueue = array();

        // Get list of events to process that are not already in the queue.
        $sql = "SELECT e.*, w.id as workflowid
                FROM {tool_trigger_events} e
                JOIN {tool_trigger_workflows} w ON w.event = e.eventname
                LEFT JOIN {tool_trigger_queue} q ON q.eventid = e.id AND q.workflowid = w.id
                WHERE w.enabled = 1 AND q.id IS NULL";
        $events = $DB->get_recordset_sql($sql);
        foreach ($events as $event) {
            $trigger = new \stdClass();
            $trigger->workflowid = $event->workflowid;
            $trigger->eventid = $event->id;
            $trigger->status = 0;
            $trigger->tries = 0;
            $trigger->timecreated = $now;
            $trigger->timemodified = $now;
            $trigger->laststep = 0;
            $triggerqueue[] = $trigger;
        }
        $events->close();
        $DB->insert_records('tool_trigger_queue', $triggerqueue);
    }

    private function process_queue($sql, $params, $limit, $starttime) {
        global $DB;

        $queue = $DB->get_recordset_sql($sql, $params, 0, $limit);
        foreach ($queue as $q) {
            $this->process_item($q);

            if (($starttime + self::MAXTIME) > time()) {
                // Max processing time for this task has been reached.
                break;
            }
        }
        $queue->close();
    }

    /**
     * Returns an event from the log data.
     *
     * @param \stdClass $data Log data
     * @return \core\event\base
     */
    private function restore_event($data) {

        $extra = array('origin' => $data->origin, 'ip' => $data->ip, 'realuserid' => $data->realuserid);
        $data = (array)$data;
        $data['other'] = unserialize($data['other']);
        if ($data['other'] === false) {
            $data['other'] = array();
        }
        unset($data['origin']);
        unset($data['ip']);
        unset($data['realuserid']);
        unset($data['id']);

        if (!$event = \core\event\base::restore($data, $extra)) {
            return null;
        }

        return $event;
    }

    private function process_item($item) {
        global $DB;

        // Update workflow record to state this workflow was attempted.
        $workflow = new \stdClass();
        $workflow->id = $item->workflowid;
        $workflow->timetriggered = time();
        $DB->update_record('tool_trigger_workflows', $workflow);

        $event = $this->restore_event(
            $DB->get_record('tool_trigger_event', ['id' => $item->eid])
        );

        $trigger = new \stdClass();
        $trigger->id = $item->qid;
        $trigger->workflowid = $item->workflowid;
        $trigger->status = $item->status;
        $trigger->tries = $item->tries + 1;
        $trigger->timecreated = $item->timecreated;
        $trigger->timemodified = $item->timemodified;

        // Get steps for this workflow.
        $steps = $DB->get_records('tool_trigger_steps', array('workflowid' => $item->workflowid), 'steporder');
        $previousstepresult = new \stdClass(); // Contains result of previous step execution.
        $success = false;
        foreach ($steps as $step) {
            // Update queue to say which step was last attempted.
            $trigger->laststep = $step->id;
            $trigger->timemodified = time();

            $DB->update_record('tool_trigger_queue', $trigger);

            $stepclass = new $step->stepclass();
            list($success, $previousstepresult) = $stepclass->execute($step, $trigger, $event, $previousstepresult);
            if (!$success) {
                // Failed to execute this step, exit processing this trigger try again next time.
                break;
            }
        }
        if ($success) {
            // Step completed and succesful result.
            $trigger->status = 1;
            $trigger->timemodified = time();

            $DB->update_record('tool_trigger_queue', $trigger);
        }

    }
}