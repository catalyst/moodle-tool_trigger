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
 * trait to help in processing events.
 *
 * @package    tool_trigger
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\helper;

defined('MOODLE_INTERNAL') || die();

trait processor_helper {

    /**
     * Returns an event from the log data.
     *
     * @param \stdClass $data Log data
     * @return \core\event\base
     */
    public function restore_event(\stdClass $data) {
        if (empty((array)$data)) {
            return null;
        }
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

    /**
     * Execute workflow step.
     *
     * @param \stdClass $step The `tool_trigger_steps` record for this step instance
     * @param \stdClass $trigger The `tool_trigger_queue` record for this execution
     * of the workflow.
     * @param \core\event\base $event The deserialized event object that triggered this execution
     * @param array $stepresults To pass to the step.
     *
     * @return array
     */
    public function execute_step($step, $trigger, $event, $stepresults) {
        $workflowmanager = new \tool_trigger\workflow_manager();

        $stepobj = $workflowmanager->validate_and_make_step($step->stepclass, $step->data);

        return $stepobj->execute($step, $trigger, $event, $stepresults);
    }

    /**
     * Get steps for the given workflow.
     *
     * @param int $workflowid Workflow ID.
     *
     * @return array
     */
    public function get_workflow_steps($workflowid) {
        global $DB;

        return $DB->get_records('tool_trigger_steps', ['workflowid' => $workflowid], 'steporder');
    }

    /**
     * Update workflow record.
     *
     * @param \stdClass $workflow Workflow record.
     */
    public function update_workflow_record(\stdClass $workflow) {
        global $DB;

        if (!empty($workflow->id)) {
            $DB->update_record('tool_trigger_workflows', $workflow);
        }
    }

    /**
     * Insert queue records in DB.
     *
     * @param array $records A list of queue records to insert.
     */
    public function insert_queue_records(array $records) {
        global $DB;

        $DB->insert_records('tool_trigger_queue', $records);
    }

    /**
     * Update queue record.
     *
     * @param \stdClass $queue Queue record.
     */
    public function update_queue_record(\stdClass $queue) {
        global $DB;

        if (!empty($queue->id)) {
            $DB->update_record('tool_trigger_queue', $queue);
        }
    }

    /**
     * Get event record from DB.
     *
     * @param int $eventid Event record ID.
     *
     * @return mixed
     */
    public function get_event_record($eventid) {
        global $DB;

        return $DB->get_record('tool_trigger_events', ['id' => $eventid]);
    }
}