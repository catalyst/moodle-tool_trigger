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
 * Debounce step class.
 *
 * The debounce step is a special step that queues up the workflow to be run after a certain
 * period of time, using ONLY the latest instance of the workflow to occur in the period,
 * with a period reset occuring at each new workflow instance trigger.
 *
 * @package    tool_trigger
 * @copyright  Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\debounce;
use tool_trigger\steps\base\base_step;
use xmldb_table;
use xmldb_field;

defined('MOODLE_INTERNAL') || die;

/**
 * Debounce step class.
 *
 * @package    tool_trigger
 * @copyright  Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class debounce_step extends base_step {

    /**
     * The fields to match exactly for the event to debounce.
     *
     * @var array
     */
    private $matchfields;

    /**
     * The fields to match exactly for the event to debounce.
     *
     * @var int
     */
    private $duration;

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::init()
     */
    protected function init() {
        // Also fix up autocomplete and duration for debouncing.
        if (!empty($data['debouncecontext'])) {
            $data['debouncecontext'] = $data['debouncecontext[]'];
        }
        if (!empty($data['debounceduration[number]'])) {
            $data['debounceduration']['number'] = $data['debounceduration[number]'];
            $data['debounceduration']['timeunit'] = $data['debounceduration[timeunit]'];
        }
        $this->matchfields = explode(',', $this->data['debouncecontext[]']);
        $this->duration = (int) $this->data['debounceduration[number]'] * (int) $this->data['debounceduration[timeunit]'];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_type()
     */
    public static function get_step_type() {
        return base_step::STEPTYPE_DEBOUNCE;
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::execute()
     */
    public function execute($step, $trigger, $event, $stepresults) {
        global $DB, $USER;

        // Special case, the debounce step should never process realtime.
        // Bail here, and move to the cron queue by throwing an exception.
        if (!CLI_SCRIPT) {
            throw new \Exception('debounce');
        }

        // If there is no execution time, we need to queue this step to fire at the specified time.
        // Add the new queue record, then return a fail to stop re-execution of this workflow instance.
        if (empty($trigger->executiontime)) {
            $newrecord = clone $trigger;
            unset($newrecord->id);
            $newrecord->executiontime = time() + $this->duration;
            // The eventid should always be set in the initial stepresults array.
            $newrecord->eventid = $stepresults['eventid'];
            $newrecord->tries = 0;
            $newrecord->timemodified = time();
            $newrecord->timecreated = time();

            $queuedid = $DB->insert_record('tool_trigger_queue', $newrecord, true);

            // Put some data in stepresults for use in logging debug mode.
            $stepresults['debouncequeueid'] = $queuedid;

            return [false, $stepresults];
        }

        // If there is an execution time, This should be run.
        // The SQL means that it will only get here when its ready to run.
        $sql = "SELECT q.*
                  FROM {tool_trigger_queue} q
                  JOIN {tool_trigger_events} e
                    ON q.eventid = e.id
                 WHERE q.workflowid = :workflowid
                   AND q.status = 0
                   AND ";

        // This could explode badly on mangled data, but its inside a try so ¯\_(ツ)_/¯.
        $counter = 0;
        $params = ['workflowid' => $trigger->workflowid];
        $dbman = $DB->get_manager();
        // Define field eventid to be added to tool_trigger_queue.
        $table = new xmldb_table('tool_trigger_events');

        foreach ($this->matchfields as $field) {
            // We need to use $field directly as moodles parameter binding isnt powerful enough.
            // This means we have to check it *exactly* matches an event table field.
            $fieldxml = new xmldb_field($field);
            if (!$dbman->field_exists($table, $fieldxml)) {
                // This is naughty. Skip it.
                continue;
            }

            $value = "value" . (string) $counter;
            $chunk = "e.$field = :$value";
            if ($counter != 0) {
                $chunk = " AND " . $chunk;
            }
            $sql .= $chunk;
            // Cast all fields to named table var field.
            $params = array_merge($params, [$value => $event->$field]);
        }

        $sql .= " ORDER BY e.id, q.executiontime, q.id";
        $records = $DB->get_records_sql($sql, $params);

        // Now we have all records that should be debounced.
        // We should now identify the highest execution time, where no time takes the highest prio.
        // This will always use at most one debounce query + the execution query. $performance++;
        // All others should be marked cancelled.
        // The items in the queue should then all be skipped until the one we want to execute.
        $notime  = [];
        $maxtime = 0;
        // Assume we are highest.
        $highest = $trigger;
        foreach ($records as $record) {
            if (empty($record->executiontime)) {
                $notime[] = $record;
                continue;
            } else {
                if ($record->executiontime >= $maxtime) {
                    $maxtime = $record->executiontime;
                    $highest = $record;
                }
            }
        }

        // We now have the highest executiontime, but we need to prio the highest match with no exectime.
        if (count($notime) > 1) {
            // This can happen with 2 events in succession between a cron run.
            usort($notime, function($el1, $el2) {
                if ($el1->timecreated === $el2->timecreated) {
                    return $el1->eventid - $el2->eventid;
                } else {
                    return $el1->timecreated - $el2->timecreated;
                }
            });
            $highest = $notime[0];
        } else if (count($notime) == 1) {
            $highest = $notime[0];
        }

        // Cancel all but the highest record, and the current record. That is handled out of loop.
        $stepresults['debouncedids'] = [];
        foreach ($records as $record) {
            if ($record->id === $highest->id || $trigger->id === $record->id) {
                continue;
            }

            $DB->set_field('tool_trigger_queue', 'status', \tool_trigger\task\process_workflows::STATUS_CANCELLED, ['id' => $record->id]);
            $eventrecord = $DB->get_record('tool_trigger_events', ['id' => $record->eventid]);

            \tool_trigger\event_processor::record_cancelled_workflow($trigger->workflowid, $eventrecord);
            $stepresults['debouncedevents'][] = $record->eventid;
        }

        // Now return the state based on whether this should continue.
        $status = $trigger->id === $highest->id;
        // Steps can cancel themselves. Add it to the stepresults so we can catch it later.
        $stepresults['cancelled'] = !$status;
        return [$status, $stepresults];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_type_desc()
     */
    public static function get_step_type_desc() {
        return get_string('debounce_desc', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_type_desc()
     */
    public static function get_step_type_name() {
        return get_string('debounce', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_desc()
     */
    public static function get_step_desc() {
        return get_string('debounce_desc', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public static function get_step_name() {
        return get_string('debounce', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public function make_form($customdata, $ajaxformdata) {
        return new debounce_form(null, $customdata, 'post', '', null, true, $ajaxformdata, $this);
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_fields()
     */
    public static function get_fields() {
        return false;
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::form_definition_extra()
     */
    public function form_definition_extra ($form, $mform, $customdata) {
        return;
    }
}
