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

use tool_trigger\helper\processor_helper;

defined('MOODLE_INTERNAL') || die();
/**
 * Simple task to rocess queued workflows.
 */
class process_workflows extends \core\task\scheduled_task {
    use processor_helper;

    /**
     * The task was cancelled.
     * @var integer
     */
    const STATUS_CANCELLED = -1;

    /**
     * The task was deferred.
     * @var integer
     */
    const STATUS_DEFERRED = -2;

    /**
     * The task has been queued but not yet executed.
     */
    const STATUS_READY_TO_RUN = 0;

    /**
     * The task finished before all steps were executed, due to
     * a filter step returning negative.
     */
    const STATUS_FINISHED_EARLY = 30;

    /**
     * The task finished and all steps were executed.
     * @var integer
     */
    const STATUS_FINISHED = 40;

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
        $this->process_queue($now);
    }

    /** Create queue of workflows that need processing.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function create_trigger_queue($now) {
        global $DB;
        $triggerqueue = array();

        // Get list of events to process that are not already in the queue for not real time workflows.
        $sql = "SELECT e.*, w.id as workflowid
                FROM {tool_trigger_events} e
                JOIN {tool_trigger_workflows} w ON w.event = e.eventname
                LEFT JOIN {tool_trigger_queue} q ON q.eventid = e.id
                WHERE w.enabled = 1 AND w.realtime = 0 AND q.id IS NULL AND q.status IS NULL;";

        $events = $DB->get_recordset_sql($sql);
        foreach ($events as $event) {
            $trigger = new \stdClass();
            $trigger->workflowid = $event->workflowid;
            $trigger->eventid = $event->id;
            $trigger->status = self::STATUS_READY_TO_RUN;
            $trigger->tries = 0;
            $trigger->timecreated = $now;
            $trigger->timemodified = $now;
            $trigger->laststep = 0;
            $triggerqueue[] = $trigger;
        }
        $events->close();
        $this->insert_queue_records($triggerqueue);
    }

    private function process_queue($starttime) {
        global $DB;

        // Now process queue including real time workflows that dumped records in the queue as couldn't process realtime.
        $sql = "SELECT q.id as qid, q.workflowid, q.status, q.tries, q.timecreated, q.timemodified, q.eventid, q.executiontime
                  FROM {tool_trigger_queue} q
                  JOIN {tool_trigger_workflows} w ON w.id = q.workflowid
                 WHERE w.enabled = 1 AND q.status = " . self::STATUS_READY_TO_RUN . "
                   AND q.tries < " . self::MAXTRIES . "
                   AND (q.executiontime IS NULL
                    OR q.executiontime < :time)
                 ORDER BY q.timecreated";
        $queue = $DB->get_recordset_sql($sql, ['time' => time()], 0, self::LIMITQUEUE);

        foreach ($queue as $q) {
            mtrace('Executing workflow: ' . $q->workflowid);
            $this->process_item($q);

            if (time() > ($starttime + self::MAXTIME)) {
                mtrace('Max processing time for this task has been reached.');
                break;
            }
        }
        $queue->close();
    }

    private function process_item($item) {
        global $DB;

        // Check if this queue item has been cancelled in this run.
        // We can skip them safely.
        if ($DB->get_field('tool_trigger_queue', 'status', ['id' => $item->qid]) === self::STATUS_CANCELLED) {
            return;
        }

        $trigger = new \stdClass();
        $trigger->id = $item->qid;
        $trigger->workflowid = $item->workflowid;
        $trigger->tries = $item->tries + 1;
        $trigger->timemodified = $item->timemodified;
        if (!empty($item->executiontime)) {
            $trigger->executiontime = $item->executiontime;
        }

        $this->update_queue_record($trigger);

        // Update workflow record to state this workflow was attempted.
        $workflow = new \stdClass();
        $workflow->id = $item->workflowid;
        $runid = \tool_trigger\event_processor::record_workflow_trigger($workflow->id, $this->get_event_record($item->eventid));
        $workflow->timetriggered = time();
        $this->update_workflow_record($workflow);

        $event = $this->restore_event($this->get_event_record($item->eventid));

        // Get steps for this workflow.
        $steps = $this->get_workflow_steps($item->workflowid);
        // Add itemid to the initial stepresults for use in debouncing.
        $stepresults = ['eventid' => $item->eventid];
        $success = false;
        $prevstep = null;

        foreach ($steps as $step) {
            // Update queue to say which step was last attempted.
            $trigger->laststep = $step->id;
            $trigger->timemodified = time();
            $this->update_queue_record($trigger);

            mtrace('Execute workflow step: ' . $step->id . ', ' . $step->stepclass);

            try {
                $outertransaction = $DB->is_transaction_started();

                list($success, $stepresults) = $this->execute_step($step,  $trigger, $event, $stepresults);

                // Record a success, or a failed debounce step with a queuedid.
                if (!empty($runid) && ($success || !$success && !empty($stepresults['debouncequeueid']))) {
                    $prevstep = \tool_trigger\event_processor::record_step_trigger($step, $prevstep, $runid, $stepresults);
                } else if (!$success && !empty($runid)) {
                    \tool_trigger\event_processor::record_failed_step($prevstep, $runid);
                }

                if (!$success) {
                    // Failed to execute this step, exit processing this trigger, but don't try again.
                    mtrace('Exiting workflow early');
                    break;
                }

            } catch (\Exception $e) {
                // Errored out executing this step. Exit processing this trigger, and try again later(?)
                $trigger->status = self::STATUS_READY_TO_RUN;
                $trigger->timemodified = time();
                $this->update_queue_record($trigger);
                if (!empty($e->debuginfo)) {

                    mtrace("Debug info:");
                    mtrace($e->debuginfo);
                }
                mtrace("Backtrace:");
                mtrace(format_backtrace($e->getTrace(), true));

                // Record the failed step for debugging.
                if (!empty($runid)) {
                    \tool_trigger\event_processor::record_failed_step($prevstep, $runid, true);
                }

                return;

            } finally {
                if (!$outertransaction && $DB->is_transaction_started()) {
                    mtrace('WARNING: Database transaction left uncommitted in '
                        . $step->stepclass . '; performing automatic rollback.');
                    $DB->force_transaction_rollback();
                }
            }
        }

        if ($success) {
            // All steps completed.
            $trigger->status = self::STATUS_FINISHED;
        } else {
            // Some steps not completed, this may be a cancelled status, or a deferred status.
            $record = false;
            if (array_key_exists('debouncequeueid', $stepresults)) {
                $trigger->status = self::STATUS_DEFERRED;
                $record = true;
            } else if (array_key_exists('cancelled', $stepresults) && $stepresults['cancelled']) {
                $trigger->status = self::STATUS_CANCELLED;
                $record = true;
            } else {
                $trigger->status = self::STATUS_FINISHED_EARLY;
            }

            // Now lets update the historical reference for this run.
            if ($record && !empty($runid)) {
                $deferred = $trigger->status === self::STATUS_DEFERRED;
                \tool_trigger\event_processor::record_cancelled_workflow(
                    $workflow->id,
                    $this->get_event_record($item->eventid),
                    $runid,
                    $deferred
                );
            }
        }
        $trigger->timemodified = time();
        $this->update_queue_record($trigger);

    }
}
