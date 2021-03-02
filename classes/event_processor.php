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
 * Process trigger system events.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger;

use tool_trigger\helper\processor_helper;
use tool_trigger\task\process_workflows;

defined('MOODLE_INTERNAL') || die();

/**
 * Process trigger system events.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_processor {
    use processor_helper;

    /**
     * Are we in the learning mode?
     * @var bool
     */
    private $islearning;

    /** @var  static a reference to an instance of this class (using late static binding). */
    protected static $singleton;

    /**
     * Class constructor method.
     */
    public function __construct() {
        $this->islearning = (bool)get_config('tool_trigger', 'learning');
    }

    /**
     * The observer monitoring all the events.
     *
     * @param \core\event\base $event event object.
     * @return bool
     */
    public static function process_event(\core\event\base $event) {

        if (empty(self::$singleton)) {
            self::$singleton = new self();
        }

        // Check whether this an event we're subscribed to,
        // and run the appropriate workflow(s) if so.
        self::$singleton->write($event);

        return false;

    }

    /**
     * We need to capture current info at this moment,
     * at the same time this lowers memory use because
     * snapshots and custom objects may be garbage collected.
     *
     * @param \core\event\base $event The event.
     * @return array $entry The event entry.
     */
    private function prepare_event($event) {
        global $PAGE, $USER;

        $entry = $event->get_data();
        $entry['origin'] = $PAGE->requestorigin;
        $entry['ip'] = $PAGE->requestip;
        $entry['realuserid'] = \core\session\manager::is_loggedinas() ? $USER->realuser : null;
        $entry['other'] = serialize($entry['other']);

        return $entry;
    }

    /**
     * Write event in the store with buffering. Method insert_event_entries() must be
     * defined.
     *
     * @param \core\event\base $event
     *
     * @return void
     */
    public function write(\core\event\base $event) {
        $entry = $this->prepare_event($event);

        if (!$this->is_event_ignored($event)) { // If is not an ignore event then process.
            $entry['id'] = $this->insert_event_entry($entry);
            $this->process_realtime_workflows($entry);
        }

        if ($this->islearning) { // If in learning mode then store event details.
            $this->insert_learn_event_entry($entry);
        }

        return;

    }

    /**
     * The \tool_log\helper\buffered_writer trait uses this to decide whether
     * or not to record an event.
     *
     * @param \core\event\base $event
     * @return boolean
     */
    protected function is_event_ignored(\core\event\base $event) {
        global $DB;

        // Check if we can return these from cache.
        $cache = \cache::make('tool_trigger', 'eventsubscriptions');

        // The SQL we will be using to fill the cache if it is empty.
        $sql = "SELECT DISTINCT(event)
                  FROM {tool_trigger_workflows}";

        $sitesubscriptions = $cache->get(0);
        // If we do not have the triggers in the cache then return them from the DB.
        if ($sitesubscriptions === false) {
            // Set the array for the cache.
            $sitesubscriptions = array();
            if ($subscriptions = $DB->get_records_sql($sql)) {
                foreach ($subscriptions as $subscription) {
                    $sitesubscriptions[$subscription->event] = true;
                }
            }
            $cache->set(0, $sitesubscriptions);
        }

        // Check if a subscription exists for this event.
        if (isset($sitesubscriptions[$event->eventname])) {
            return false;
        }

        return true;
    }

    /**
     * Insert event data into the database.
     *
     * @param \stdClass $evententry Event data.
     * @return int
     */
    private function insert_event_entry($evententry) {
        global $DB;

        return $DB->insert_record('tool_trigger_events', $evententry);
    }

    /**
     * Insert event data into the database for learning.
     *
     * @param \stdClass $learnentry Event data.
     */
    private function insert_learn_event_entry($learnentry) {
        global $DB;
        $DB->insert_record('tool_trigger_learn_events', $learnentry);
    }

    /**
     * Process real time workflows associated with this event.
     * @param \stdClass $evententry Event data.
     */
    private function process_realtime_workflows($evententry) {
        global $DB;

        // Disable moodle specific debug messages and any errors in output and log them to error log.
        defined('NO_DEBUG_DISPLAY') || define('NO_DEBUG_DISPLAY', true);

        $evententry = (object)$evententry;
        $workflows = $DB->get_records(
            'tool_trigger_workflows',
            ['enabled' => 1, 'realtime' => 1, 'event' => $evententry->eventname]
        );

        foreach ($workflows as $workflow) {
            $this->process_realtime_workflow($workflow, $evententry);
        }
    }

    private function process_realtime_workflow($workflow, $evententry) {
        global $DB;

        try {
            $workflow->timetriggered = time();
            $this->update_workflow_record($workflow);
            $runid = self::record_workflow_trigger($workflow->id, $evententry);

            $event = $this->restore_event($evententry);
            $steps = $this->get_workflow_steps($workflow->id);
            $stepresults = [];
            $success = false;
            $prevstep = null;
            foreach ($steps as $step) {
                try {
                    $outertransaction = $DB->is_transaction_started();
                    list($success, $stepresults) = $this->execute_step($step,  new \stdClass(), $event, $stepresults);

                    // Now record the steps into the history table, and update prevstep for the next iteration.
                    if ($success && !empty($runid)) {
                        $prevstep = self::record_step_trigger($step, $prevstep, $runid, $stepresults);
                    } else if (!$success && !empty($runid)) {
                        self::record_failed_step($prevstep, $runid);
                    }

                    if (!$success) {
                        // Failed to execute this step, exit processing this trigger, but don't try again.
                        debugging('Execute workflow step: ' . $step->id . ', ' . $step->stepclass . ' Exiting workflow early');
                        break;
                    }
                } catch (\Exception $e) {
                    if (!($e->getMessage() === 'debounce')) {
                        debugging('Error execute workflow step: ' . $step->id . ', ' . $step->stepclass . ' ' . $e->getMessage());
                    }

                    // Record step fail if debugging enabled.
                    if (!empty($runid)) {
                        self::record_failed_step($prevstep, $runid, true);
                    }

                    // Insert to the queue table and try to run again in cron.
                    $queuerecord = new \stdClass();
                    $queuerecord->workflowid = $workflow->id;
                    $queuerecord->eventid = $evententry->id;
                    $queuerecord->status = process_workflows::STATUS_READY_TO_RUN;
                    $queuerecord->tries = 1;
                    $queuerecord->timecreated = time();
                    $queuerecord->timemodified = time();
                    $queuerecord->laststep = 0;
                    $this->insert_queue_records([$queuerecord]);
                    $success = true;
                    break;
                } finally {
                    if (!$outertransaction && $DB->is_transaction_started()) {
                        $DB->force_transaction_rollback();
                    }
                }
            }
            if (!$success) {
                debugging('Error execute workflow: ' . $workflow->id . ' failed and will not be rerun.');
            }
        } catch (\Exception $exception) {
            debugging('Error: processing real time workflow ' . $exception->getMessage(), $exception->getTrace());
        }
    }

    /**
     * This function records information about the workflow run for
     * history and auditing.
     *
     * @param int $workflowid The workflow id to record.
     * @param stdClass $event the event that triggered this workflow.
     * @return int|null the id of the recorded workflow record or null.
     */
    public static function record_workflow_trigger(int $workflowid, $event) {
        global $DB;

        // First, check whether this should be recorded, if debug is enabled for the workflow id.
        $debug = $DB->get_field('tool_trigger_workflows', 'debug', ['id' => $workflowid]);
        // If the debug flag is false, exit out.
        if (!$debug) {
            return null;
        }

        // Get new run number.
        $sqlfrag = "SELECT MAX(number) FROM {tool_trigger_workflow_hist} WHERE workflowid = :wfid";
        $runnumber = $DB->get_field_sql($sqlfrag, array('wfid' => $workflowid)) + 1;

        // Encode event data as JSON.
        $eventdata = json_encode($event);

        $id = $DB->insert_record('tool_trigger_workflow_hist', array(
            'workflowid' => $workflowid,
            'number' => $runnumber,
            'timecreated' => time(),
            'event' => $eventdata,
            'eventid' => $event->id
        ), true);

        // Return the id for use in other tables.
        return $id;
    }

    /**
     * This records an execution of a step for historical and auditing purposes.
     *
     * @param stdClass $step the step object to store.
     * @param integer $prevstep The previous step.
     * @param integer $runid the run this step is in.
     * @param array $stepresults the results from the stepexecution.
     * @return int the id of the inserted step record.
     */
    public static function record_step_trigger($step, $prevstep, $runid, $stepresults) {
        global $DB;

        // Clone the step to allow modifications.
        $clonestep = (array) $step;

        if (empty($prevstep)) {
            $clonestep['number'] = 0;
        } else {
            $clonestep['number'] = $DB->get_field('tool_trigger_run_hist', 'number', ['id' => $prevstep]) + 1;
        }

        // Unset step ID so this is inserted as a new record.
        unset($clonestep['id']);
        $clonestep['executed'] = time();
        $clonestep['prevstepid'] = $prevstep;
        $clonestep['runid'] = $runid;
        $clonestep['results'] = json_encode($stepresults);

        // If the stepconfigid isn't set, this is a new step record.
        // It must be set to the id of the step we are copying.
        // Else it should remain the same.
        if (empty($clonestep['stepconfigid'])) {
            $clonestep['stepconfigid'] = $step->id;
        }

        return $DB->insert_record('tool_trigger_run_hist', $clonestep, true);
    }

    /**
     * This step will record the step that failed if a run was not successfully completed.
     * If the $error bool is provided, the step will be recorded as an error not a fail.
     *
     * @param int|null $laststep the last step completed sucessfully or null if no previous step.
     * @param int $runid the id of the run.
     * @return void
     */
    public static function record_failed_step($laststep, $runid, $error = false) {
        global $DB;
        if (!empty($laststep)) {
            $laststeprec = $DB->get_record('tool_trigger_run_hist', ['id' => $laststep]);
            $failedstep = $laststeprec->number + 1;
        } else {
            // If no prevstep, run failed on step 0.
            $failedstep = 0;
        }

        $field = $error ? 'errorstep' : 'failedstep';

        $DB->set_field('tool_trigger_workflow_hist', $field, $failedstep, ['id' => $runid]);
    }

    /**
     * This gets all of the data required from the history tables, and reruns a step.
     * If newrunid is supplied, it sets the runid of the new step execution to the newrunid
     *
     * @param int $stepid the id of the step from historic table.
     * @param int $newrunid This is used to execute a step on a new workflow run.
     * @return void
     */
    public static function execute_historic_step(int $stepid, int $newrunid = 0) {
        global $DB;

        // Get step data from DB.
        $step = $DB->get_record('tool_trigger_run_hist', ['id' => $stepid]);
        $eventdata = json_decode($DB->get_field('tool_trigger_workflow_hist', 'event', ['id' => $step->runid]));

        // Set new run id to old ID if not supplied.
        $newrunid = $newrunid !== 0 ? $newrunid : $step->runid;

        $prevstep = $DB->get_record('tool_trigger_run_hist', ['id' => $step->prevstepid]);
        // If there is no previous step, instantiate to default values.
        if (!$prevstep) {
            $stepresults = [];
            $prevstepid = null;
        } else {
            $stepresults = json_decode($prevstep->results, true);
            $prevstepid = $prevstep->id;
        }

        $processor = new \tool_trigger\event_processor();
        $event = $processor->restore_event($eventdata);

        list($success, $stepresults) = $processor->execute_step($step,  new \stdClass(), $event, $stepresults);
        if ($success) {
            self::record_step_trigger($step, $prevstepid, $newrunid, $stepresults);
        }
    }

    /**
     * This function takes a stepid to rerun, then finds the current
     * configuration for that step, and executes it. If newprevid is supplied,
     * then the step is logged with the newprevid as the previd.
     * This is used when chaining calls of this from other functions to build
     * a new run chain.
     *
     * @param int the stepid to trigger a new run of with new config.
     * @param int $newprevid
     * @return void
     */
    public static function execute_current_step(int $stepid, $newprevid = 0) {
        global $DB;
        // First get the historic step from id.
        $step = $DB->get_record('tool_trigger_run_hist', ['id' => $stepid]);
        $eventdata = json_decode($DB->get_field('tool_trigger_workflow_hist', 'event', ['id' => $step->runid]));

        // Figure out if there was a previous step.
        $prevstep = $DB->get_record('tool_trigger_run_hist', ['id' => $step->prevstepid]);
        if (!$prevstep) {
            $stepresults = [];
            $prevstepid = null;
        } else {
            // If newprevid is supplied, this must be part of a longer rerun.
            if ($newprevid !== 0) {
                $stepresults = json_decode($DB->get_field('tool_trigger_run_hist', 'results', ['id' => $newprevid]), true);
                $prevstepid = $newprevid;
            } else {
                $stepresults = json_decode($prevstep->results, true);
                $prevstepid = $prevstep->id;
            }
        }

        $processor = new \tool_trigger\event_processor();
        $event = $processor->restore_event($eventdata);

        // Now retrieve the new step config.
        $newstep = $DB->get_record('tool_trigger_steps', ['id' => $step->stepconfigid]);

        list($success, $stepresults) = $processor->execute_step($newstep,  new \stdClass(), $event, $stepresults);
        if ($success) {
            self::record_step_trigger($newstep, $prevstepid, $step->runid, $stepresults);
        }
    }

    /**
     * This function takes a historic stepid, finds the next step in the chain, and triggers a new run of it.
     * If newrunid is supplied, this will update the runid to the new supplied value.
     *
     * @param int $stepid
     * @param int $newprevid The new previd to update the step to.
     * @param int $newrunid A new run id to log
     * @return array|null An array of the step that was just executed, and the resulting id.
     */
    public static function execute_next_step_historic(int $stepid, int $origrun = 0, $newprevid = 0, $newrunid = 0) {
        global $DB;

        // Get step data from DB.
        $step = $DB->get_record('tool_trigger_run_hist', ['id' => $stepid]);
        $eventdata = json_decode($DB->get_field('tool_trigger_workflow_hist', 'event', ['id' => $step->runid]));

        $origrun = $origrun !== 0 ? $origrun : $step->runid;

        // Now we need to get the min id instance of the step that follows the original step.
        $nextstepsql = "SELECT *
                          FROM {tool_trigger_run_hist}
                         WHERE workflowid = :workflow
                           AND runid = :run
                           AND number = :number
                           AND id > :previd
                      ORDER BY id ASC
                         LIMIT 1";

        // If original ID is supplied, we should get next step on from the original id.
        // This is used when chaining historical runs.
        $params = [
            'workflow' => $step->workflowid,
            'run' => $origrun,
            'number' => $step->number + 1,
            'previd' => $step->id
        ];
        $nextstep = $DB->get_record_sql($nextstepsql, $params);

        // If no nextstep is found, return false.
        if (!$nextstep) {
            return null;
        }

        // If new Runid is supplied, update the events.
        $runid = $newrunid !== 0 ? $newrunid : $step->runid;
        // Now perform a 'rerun' on this step, except it is based on the new step created above.
        $processor = new \tool_trigger\event_processor();
        $event = $processor->restore_event($eventdata);
        // If new previd is supplied, we are in a chain, must base off new event in the chain.
        if ($newprevid !== 0) {
            $previd = $newprevid !== 0 ? $newprevid : $step->id;
            $stepresults = json_decode($DB->get_field('tool_trigger_run_hist', 'results', ['id' => $previd]), true);
        } else {
            $previd = $step->id;
            $stepresults = json_decode($step->results, true);
        }

        list($success, $stepresults) = $processor->execute_step($nextstep,  new \stdClass(), $event, $stepresults);
        if ($success) {
            self::record_step_trigger($nextstep, $previd, $runid, $stepresults);
        }

        // Select the highest id of matching step type for newly executed step.
        $newstepsql = "SELECT *
                  FROM {tool_trigger_run_hist}
                 WHERE workflowid = :workflow
                   AND runid = :run
                   AND name = :stepname
                   AND id > :previd
              ORDER BY id DESC
                 LIMIT 1";
        $newstep = $DB->get_record_sql($newstepsql, [
            'workflow' => $nextstep->workflowid,
            'run' => $runid,
            'stepname' => $nextstep->name,
            'previd' => $previd
        ]);

        // Return the ID just executed for use in moving through the historic chain.
        return [$nextstep->id, $newstep->id];
    }

    /**
     * This function takes a step, and executes the next step in the chain, using current config.
     *
     * @param integer $stepid the step id to rerun
     * @return integer the ID of the executed step.
     */
    public static function execute_next_step_current(int $stepid) {
        global $DB;

        // Get step data from DB.
        $step = $DB->get_record('tool_trigger_run_hist', ['id' => $stepid]);

        // Now we need to get the most recent instance of the step that follows this step.
        $nextstepsql = "SELECT *
                          FROM {tool_trigger_run_hist}
                         WHERE workflowid = :workflow
                           AND runid = :run
                           AND number = :number
                      ORDER BY id DESC
                         LIMIT 1";
        $nextstep = $DB->get_record_sql($nextstepsql, [
            'workflow' => $step->workflowid,
            'run' => $step->runid,
            'number' => $step->number + 1
        ]);

        // If no nextstep is found, jump out.
        if (!$nextstep) {
            return false;
        }

        // Otherwise, we can fire a rerun of nextstep with current config, passing in the new previd.
        self::execute_current_step($nextstep->id, $step->id);

        // Now lookup the ID of the newly created step, and return it.
        // Select the highest id of matching step type.
        $newstepsql = "SELECT id
                  FROM {tool_trigger_run_hist}
                 WHERE workflowid = :workflow
                   AND runid = :run
                   AND name = :stepname
              ORDER BY id DESC
                 LIMIT 1";
        $newstepid = $DB->get_field_sql($newstepsql, [
            'workflow' => $nextstep->workflowid,
            'run' => $nextstep->runid,
            'stepname' => $nextstep->name
        ]);
        return $newstepid;
    }

    /**
     * This function takes a stepid, reruns the step, then triggers the
     * next step in the workflow based on the results from the rerun.
     * If the $completerun param is supplied, it will continue executing
     * steps until the end of the workflow.
     * If $newrunid is supplied, all steps will have their runid set to the newrunid.
     *
     * @param int $stepid the step to execute from.
     * @param boolean $completerun Whether to run until run completion.
     * @param int $newrunid The runid to move executed steps onto.
     * @return void
     */
    public static function execute_step_and_continue_historic(int $stepid, bool $completerun = false, $newrunid = 0) {
        global $DB;
        $step = $DB->get_record('tool_trigger_run_hist', ['id' => $stepid]);

        // Set new run id to old ID if not supplied.
        $newrunid = $newrunid !== 0 ? $newrunid : $step->runid;

        // Call the single step rerun function, then get the resulting object.
        self::execute_historic_step($stepid, $newrunid);
        // Select the highest id of matching step type.
        $newstepsql = "SELECT *
                  FROM {tool_trigger_run_hist}
                 WHERE workflowid = :workflow
                   AND runid = :run
                   AND name = :stepname
              ORDER BY id DESC
                 LIMIT 1";
        $newstep = $DB->get_record_sql($newstepsql, [
            'workflow' => $step->workflowid,
            'run' => $newrunid,
            'stepname' => $step->name
        ]);

        // Now execute the next step in the historic chain.
        $idarr = self::execute_next_step_historic($step->id, $step->runid, $newstep->id, $newrunid);

        // Now if we are completing the entire run, we need to use the id keep iterating till we get a false.
        if ($completerun) {
            while (!empty($idarr)) {
                $idarr = self::execute_next_step_historic($idarr[0], $step->runid, $idarr[1], $newrunid);
            }
        }
    }

    /**
     * This function takes a stepid, reruns the step, then triggers the
     * next step in the workflow based on the results from the rerun.
     * If the $completerun param is supplied, it will continue executing
     * steps until the end of the workflow.
     *
     * @param int $stepid
     * @param boolean $completerun
     * @return void
     */
    public static function execute_step_and_continue_current(int $stepid, bool $completerun = false) {
        global $DB;
        $step = $DB->get_record('tool_trigger_run_hist', ['id' => $stepid]);

        // Call the single step rerun function, then get the resulting object.
        self::execute_current_step($stepid);
        // Select the highest id of matching step type.
        $newstepsql = "SELECT *
                  FROM {tool_trigger_run_hist}
                 WHERE workflowid = :workflow
                   AND runid = :run
                   AND name = :stepname
              ORDER BY id DESC
                 LIMIT 1";
        $newstep = $DB->get_record_sql($newstepsql, [
            'workflow' => $step->workflowid,
            'run' => $step->runid,
            'stepname' => $step->name
        ]);

        // Now execute the next step, based on the step we just created.
        $id = self::execute_next_step_current($newstep->id);

        // Now if we are completing the entire run, we need to use the id keep iterating till we get a false.
        if ($completerun) {
            while ($id !== false) {
                $id = self::execute_next_step_current($id);
            }
        }
    }

    /**
     * This function takes a workflow, and reruns it exactly.
     *
     * @param int $runid the runid to rerun exactly.
     * @return void
     */
    public static function execute_workflow_from_event_historic(int $runid) {
        global $DB;
        $runrecord = $DB->get_record('tool_trigger_workflow_hist', ['id' => $runid]);
        // We just need to get the first step id that was in the chain for this runid,
        // Then rerun to completion with historic configuration.
        $sql = "SELECT id
                  FROM {tool_trigger_run_hist}
                 WHERE runid = :runid
              ORDER BY id ASC
                 LIMIT 1";
        $firststepid = $DB->get_field_sql($sql, ['runid' => $runid]);

        // Manually add a Workflow trigger record.
        $eventdata = json_decode($runrecord->event);
        self::record_workflow_trigger($runrecord->workflowid, $eventdata);

        // Now lets find the new run id, to supply to the rerun chain.
        $newrunidsql = "SELECT id
                          FROM {tool_trigger_workflow_hist}
                         WHERE workflowid = :workflowid
                      ORDER BY id DESC
                         LIMIT 1";
        $newrunid = $DB->get_field_sql($newrunidsql, ['workflowid' => $runrecord->workflowid]);

        // Finally, kick off the rerun.
        self::execute_step_and_continue_historic($firststepid, true, $newrunid);
    }

    /**
     * This gets the event that fired a workflow, and reruns the current workflow configuration
     * against that event.
     *
     * @param int $runid the id to use the event from.
     * @return void
     */
    public static function execute_workflow_from_event_current(int $runid) {
        global $DB;
        $runrecord = $DB->get_record('tool_trigger_workflow_hist', ['id' => $runid]);
        $workflow = $DB->get_record('tool_trigger_workflows', ['id' => $runrecord->workflowid]);
        $evententry = json_decode($runrecord->event);

        // Force the debug flag in the workflow on, so it is always recorded if manually triggered.
        $workflow->debug = 1;

        $processor = new \tool_trigger\event_processor();
        $processor->process_realtime_workflow($workflow, $evententry);
    }

    /**
     * This function gets all the errored runs, and reruns them,
     * with either current config or historic if given.
     *
     * @param int $workflow the workflow id to rerun
     * @param boolean $historic whether to use historic configuration
     * @return void
     */
    public static function rerun_all_error_runs($workflow, $historic = false) {
        global $DB;

        $timelimit = get_config('tool_trigger', 'historyduration');

        // Get all runs that still contain data.
        // This is generally the period we wish to review.
        // Cron will rerun errors, so we want the newest errored run for an event.
        // We also need to ensure if there are multiple runs, the latest one errored.

        $latesterrorsql = "SELECT MAX(id)
                             FROM {tool_trigger_workflow_hist} sube
                            WHERE sube.eventid = hist.eventid
                              AND sube.errorstep IS NOT NULL";

        $latestrunsql = "SELECT MAX(id)
                           FROM {tool_trigger_workflow_hist} subl
                          WHERE subl.eventid = hist.eventid";

        $sql = "SELECT hist.id, hist.eventid
                  FROM {tool_trigger_workflow_hist} hist
                 WHERE hist.timecreated > ?
                   AND hist.workflowid = ?
                   AND ($latesterrorsql) = hist.id
                   AND ($latestrunsql) = hist.id";

        $ids = $DB->get_records_sql($sql, [$timelimit, $workflow]);

        // We now need to iterate through, and rerun.
        $results = [];
        foreach ($ids as $run) {
            if ($historic) {
                self::execute_workflow_from_event_historic($run->id);
            } else {
                self::execute_workflow_from_event_current($run->id);
            }

            // Get new run ID, and store the result.
            $newrun = $DB->get_records('tool_trigger_workflow_hist', ['eventid' => $run->eventid], 'id DESC', '*', 0, 1);
            $newrun = reset($newrun);

            $passed = $newrun->errorstep > 0 ? false : true;
            $results[$run->id] = [$newrun->id, $passed];
        }

        // Now output a list of all of the rerun ids with status
        $output = get_string('rerunerrors', 'tool_trigger') . '<br>';
        $error = false;
        foreach ($results as $previd => $new) {
            list($newid, $passed) = $new;
            if (!$passed) {
                $output .= get_string('newrunfailed', 'tool_trigger', ['prev' => $previd, 'new' => $newid]) . '<br>';
                $error = true;
            }
        }
        $notifytype = $error ? 'notifyerror' : 'notifysuccess';
        \core\notification::add($output, $notifytype);
    }

    /**
     * Records a cancelled workflow, used in debouncing.
     *
     * @param int $workflowid
     * @param stdClass $event
     * @param int $runid
     * @param boolean $deferred
     * @return void
     */
    public static function record_cancelled_workflow($workflowid, $event, $runid = null, $deferred = false) {
        global $DB;

        // Get new run number.
        $sqlfrag = "SELECT MAX(number) FROM {tool_trigger_workflow_hist} WHERE workflowid = :wfid";
        $runnumber = $DB->get_field_sql($sqlfrag, array('wfid' => $workflowid)) + 1;

        // Encode event data as JSON.
        $eventdata = json_encode($event);

        // Decide the field type to record
        $status = $deferred ? \tool_trigger\task\process_workflows::STATUS_DEFERRED : \tool_trigger\task\process_workflows::STATUS_CANCELLED;
        $dataobj = [
            'workflowid' => $workflowid,
            'number' => $runnumber,
            'timecreated' => time(),
            'event' => $eventdata,
            'eventid' => $event->id,
            'failedstep' => $status
        ];

        if (empty($runid)) {
            $DB->insert_record('tool_trigger_workflow_hist', $dataobj);
        } else {
            $DB->set_field('tool_trigger_workflow_hist', 'failedstep', $status, ['id' => $runid]);
        }
    }
}
