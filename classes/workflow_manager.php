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
 * Workflow manager class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger;

defined('MOODLE_INTERNAL') || die();

/**
 * Workflow manager class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workflow_manager {

    /**
     * Helper method to convert db records to workflow objects.
     *
     * @param array $records of workflows from db.
     * @return array of worklfow objects.
     */
    protected static function get_instances($records) {
        $workflows = array();
        foreach ($records as $key => $record) {
            $workflows[$key] = new workflow($record);
        }
        return $workflows;
    }

    /**
     * Get workflow count.
     *
     * @return int $count Count of workflows present in system.
     */
    public static function count_workflows() {
        global $DB;
        $count = $DB->count_records('tool_trigger_workflows');
        return $count;
    }

    /**
     * Retrieve a specific workflow
     *
     * @param int $workflowid
     * @return boolean|\tool_trigger\workflow
     */
    public static function get_workflow($workflowid) {
        global $DB;
        $record = $DB->get_record('tool_trigger_workflows', ['id' => $workflowid], '*', IGNORE_MISSING);
        if (!$record) {
            return false;
        } else {
            return new workflow($record);
        }
    }

    /**
     * Get all the created workflows, to show them in a table.
     *
     * @param int $limitfrom Limit from which to fetch worklfows.
     * @param int $limitto  Limit to which workflows need to be fetched.
     * @return array List of worklfows .
     */
    public static function get_workflows_paginated($limitfrom = 0, $limitto = 0) {
        global $DB;

        $orderby = 'name ASC';
        $records = $DB->get_records('tool_trigger_workflows', null, $orderby, '*', $limitfrom, $limitto);
        $workflows = self::get_instances($records);

        return $workflows;
    }

    public function get_step_class_names($steptype) {

        $matchedsteps = array();
        $matches = array();
        $stepdir = __DIR__ . '/steps/' . $steptype;
        $handle = opendir($stepdir);
        while (($file = readdir($handle)) !== false) {
            preg_match('/\b(?!base)(.*step)/', $file, $matches);
            $matchedsteps = array_merge($matches, $matchedsteps);

        }
        closedir($handle);
        $matchedsteps = array_unique($matchedsteps);

        return $matchedsteps;

    }

    public function get_steps_with_names($steptype, $stepclasses) {
        $stepnames = array();

        foreach ($stepclasses as $stepclass) {

            $stepclass = '\tool_trigger\steps\\' . $steptype . '\\' . $stepclass;
            $class = new $stepclass();
            $stepname = array(
                    'class' => $stepclass,
                    'name' => $class->get_step_name()
            );
            $stepnames[] = $stepname;

        }

        return $stepnames;

    }

    public function get_steps_by_type($steptype) {
        $acceptedtypes = array('lookups', 'triggers', 'filters');
        if (!in_array($steptype, $acceptedtypes)) {
            throw new \moodle_exception('badsteptype', 'tool_trigger', '');
        }

        $matchedsteps = $this->get_step_class_names($steptype);
        $stepswithnames = $this->get_steps_with_names($steptype, $matchedsteps);

        return $stepswithnames;
    }

    /**
     * Create a copy of a workflow.
     *
     * @param \tool_trigger\workflow $workflow
     * @return boolean|\tool_trigger\workflow
     */
    public function copy_workflow(\tool_trigger\workflow $workflow) {
        global $DB;

        $now = time();

        $newworkflow = fullclone($workflow->workflow);
        unset($newworkflow->id);
        // Add " (copy)" suffix to name.
        $newworkflow->name = get_string('duplicatedworkflowname', 'tool_trigger', $newworkflow->name);
        $newworkflow->timecreated = $now;
        $newworkflow->timemodified = $now;
        $newworkflow->timetriggered = 0;

        $steps = $DB->get_records(
            'tool_trigger_steps',
            ['workflowid' => $workflow->id],
            'steporder'
        );

        try {
            $transaction = $DB->start_delegated_transaction();

            $newworkflowid = $DB->insert_record('tool_trigger_workflows', $newworkflow);

            $newsteps = [];
            foreach($steps as $step) {
                $newstep = fullclone($step);
                unset($newstep->id);
                $newstep->workflowid = $newworkflowid;
                $newstep->timecreated = $now;
                $newstep->timemodified = $now;
                $newsteps[] = $newstep;
            }
            $DB->insert_records('tool_trigger_steps', $newsteps);

            $DB->commit_delegated_transaction($transaction);
        } catch (\Exception $e) {
            $transaction->rollback($e);
            return false;
        }

        return self::get_workflow($newworkflowid);
    }

    /**
     * Delete a workflow.
     * @param int $workflowid
     * @return boolean
     */
    public function delete_workflow($workflowid) {
        global $DB;

        try {
            $transaction = $DB->start_delegated_transaction();
            $DB->delete_records('tool_trigger_steps', ['workflowid' => $workflowid]);
            $DB->delete_records('tool_trigger_workflows', ['id' => $workflowid]);
            $DB->delete_records('tool_trigger_queue', ['workflowid' => $workflowid]);
            $DB->commit_delegated_transaction($transaction);
        } catch (\Exception $e) {
            $transaction->rollback($e);
            return false;
        }
        return true;
    }
}