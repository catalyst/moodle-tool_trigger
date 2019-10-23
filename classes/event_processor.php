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
            $this->insert_event_entry($entry);
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
     */
    private function insert_event_entry($evententry) {
        global $DB;
        $DB->insert_record('tool_trigger_events', $evententry);
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
            try {
                $workflow->timetriggered = time();
                $this->update_workflow_record($workflow);

                $event = $this->restore_event($evententry);
                $steps = $this->get_workflow_steps($workflow->id);
                $stepresults = [];
                $success = false;
                foreach ($steps as $step) {
                    try {
                        $outertransaction = $DB->is_transaction_started();
                        list($success, $stepresults) = $this->execute_step($step,  new \stdClass(), $event, $stepresults);

                        if (!$success) {
                            // Failed to execute this step, exit processing this trigger, but don't try again.
                            debugging('Execute workflow step: ' . $step->id . ', ' . $step->stepclass . ' Exiting workflow early');
                            break;
                        }
                    } catch (\Exception $e) {
                        debugging('Error execute workflow step: ' . $step->id . ', ' . $step->stepclass . ' ' . $e->getMessage());

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
    }
}
