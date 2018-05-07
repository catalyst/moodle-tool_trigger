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

/**
 * Simple task to rocess queued workflows.
 */
class process_workflows extends \core\task\scheduled_task {

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
        global $DB;
        $now = time();
        $maxtries = 5; //TODO MAGIC NUMBER!!
        $this->create_trigger_queue($now);

        // Now process queue just created.
        $sql = "SELECT q.id as qid, q.workflowid, q.status, q.tries, q.timecreated, q.timemodified, 
                       e.id as eid, e.eventname, e.contextid, e.contextlevel, e.contextinstanceid, e.link, e.courseid, e.timecreated
                  FROM {tool_trigger_queue} q
                  JOIN {tool_trigger_workflows} w ON w.id = q.workflowid
                  JOIN {tool_trigger_events} e ON e.id = q.eventid
                  WHERE w.enabled = 1 AND q.status = 0 AND q.timecreated = ? AND q.tries < ?";
        $queue = $DB->get_recordset_sql($sql, array($now, $maxtries));
        foreach ($queue as $q) {
            // Update workflow record to state this workflow was attempted.
            $workflow = new \stdClass();
            $workflow->id = $q->workflowid;
            $workflow->timetriggered = time();
            $DB->update_record('tool_trigger_workflows', $workflow);

            $event = new \stdClass();
            $event->id = $q->eid;
            $event->eventname = $q->eventname;
            $event->contextid = $q->contextid;
            $event->contextlevel = $q->contextlevel;
            $event->contextinstanceid = $q->contextinstanceid;
            $event->link = $q->link;
            $event->courseid = $q->courseid;
            $event->timecreated = $q->timecreated;

            $trigger = new \stdClass();
            $trigger->id = $q->qid;
            $trigger->workflowid = $q->workflowid;
            $trigger->status = $q->status;
            $trigger->tries = $q->tries + 1;
            $trigger->timecreated = $q->timecreated;
            $trigger->timemodified = $q->timemodified;

            // Get steps for this workflow.
            $steps = $DB->get_records('tool_trigger_steps', array('workflowid' => $q->workflowid), 'steporder');
            foreach ($steps as $step) {
                // Update queue to say which step was last attempted.
                $trigger->laststep = $step->id;
                $trigger->timemodified = time();

                $DB->update_record('tool_trigger_queue', $trigger);

                $stepclass = new $step->stepclass();
                $stepclass->execute($step, $trigger, $event);
            }
        }

        $queue->close();

        // TODO: Now check to see if there are old events in queue to process.


    }

    /** Create queue of workflows that need processing.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    function create_trigger_queue($now) {
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
}
