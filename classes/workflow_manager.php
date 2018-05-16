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
     * @var string[] The categories of steps available.
     */
    const STEPTYPES = array('lookups', 'triggers', 'filters');

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

    /**
     * Gets the names of the available step classes.
     *
     * @param null|string $steptype Limit to steps of one step type. Or null (default)
     * to retrieve steps of all types.
     * @throws \invalid_parameter_exception
     * @return array
     */
    public function get_step_class_names($steptype = null) {
        if ($steptype === null) {
            $steptypes = self::STEPTYPES;
        } else {
            $steptypes = [$steptype];
        }

        $matchedsteps = array();
        $matches = array();

        foreach ($steptypes as $steptype) {
            $stepdir = __DIR__ . '/steps/' . $steptype;
            $handle = opendir($stepdir);
            while (($file = readdir($handle)) !== false) {
                preg_match('/\b(?!base)(.*step)/', $file, $matches);
                foreach ($matches as $classname) {
                    $matchedsteps[] = '\tool_trigger\steps\\' . $steptype . '\\' . $classname;
                }
            }
            closedir($handle);
        }
        $matchedsteps = array_unique($matchedsteps);

        return $matchedsteps;

    }

    public function get_steps_with_names($stepclasses) {
        $stepnames = array();

        foreach ($stepclasses as $stepclass) {
            if ($this->validate_step_class($stepclass)) {
                $stepnames[] = array(
                    'class' => $stepclass,
                    'name' => $stepclass::get_step_name()
                );
            }
        }

        return $stepnames;

    }

    public function get_steps_by_type($steptype) {
        if (!in_array($steptype, self::STEPTYPES)) {
            throw new \invalid_parameter_exception('badsteptype', 'tool_trigger', '');
        }

        $matchedsteps = $this->get_step_class_names($steptype);
        $stepswithnames = $this->get_steps_with_names($matchedsteps);

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
            foreach ($steps as $step) {
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

    protected $stepclasses = null;

    public function validate_step_class($stepclass) {
        if ($this->stepclasses === null) {
            $this->stepclasses = $this->get_step_class_names();
        }
        return in_array($stepclass, $this->stepclasses);
    }

    /**
     * Factory method to validate the stepclass name and then instantiate the stepclass.
     *
     * @param string $stepclass
     * @param mixed ...$params Additional params to pass to the stepclass constructor.
     * @throws \invalid_parameter_exception
     * @return \tool_trigger\steps\base\base_step
     */
    public function validate_and_make_step($stepclass, ...$params) {
        if (!$this->validate_step_class($stepclass)) {
            throw new \invalid_parameter_exception(get_string('badstepclass', 'tool_trigger'));
        }

        return new $stepclass(...$params);
    }

    /**
     * Combines the scalar values from the workflow's event data, and lookup
     * data, into a single associative array with regularized names for each
     * item. These can then be used as a way for workflow authors to identify
     * a particular field of data, and as placeholders for substitution into
     * output templates.
     *
     * These are all combined into a single array, with lookup values overwriting
     * event values. $event->get_data()['other'] values are prefaced with
     * "other_", e.g. $event->get_data()['other']['teacherid'] would become
     * "other_teacherid".
     *
     * NOTE: As tempting as it may be, this cannot be used during the step
     * "edit" phase. That's because events do not include a machine-readable
     * list of their "other" fields. So, we have to look at an instantiated
     * event object in order to get those; and the way to properly instantiate
     * an event is different for every event type!
     *
     * (Also our own workflow steps don't provide a machine-readable list of
     * the fields they add, either. But we could implement that.)
     *
     * TODO: Refactor this to someplace that makes more OO sense. Maybe in
     * an object that represents the current workflow process being executed?
     *
     * @param \core\event\base $event (Read-only) The deserialized event object that triggered this execution
     * @param array $stepresults (Read-Write) Data aggregated from the return values of previous steps in
     * the workflow.
     */
    public static function get_datafields($event = null, $stepresults = null) {
        $fields = [];
        if ($event !== null) {
            $fields = array_merge($fields, $event->get_data(), $event->get_logextra());
            if (isset($fields['other']) && is_array($fields['other'])) {
                foreach ($fields['other'] as $key => $value) {
                    if (is_scalar($value)) {
                        $fields["other_{$key}"] = $value;
                    }
                }
                unset($fields['other']);
            }
        }

        if ($stepresults !== null) {
            foreach ($stepresults as $key => $value) {
                if (is_scalar($value)) {
                    $fields[$key] = $value;
                }
            }
        }

        return $fields;
    }

    /**
     * Searches a "template" string for placeholders that are surrounded in curly brackets
     * e.g.: {firstname}. If there's a matching data field with the same name, we replace
     * the placeholder with the value of the data field.
     *
     * TODO: Refactor this to someplace that makes more OO sense. Maybe in
     * an object that represents the current workflow process being executed?
     *
     * @param string $templatestr
     * @param \core\event\base $event (Read-only) The deserialized event object that triggered this execution
     * @param array $stepresults (Read-Write) Data aggregated from the return values of previous steps in
     * @param callable $transformcallback An optional callback function to transform each datafield's value
     * before swapping it in. (For example, to urlencode them.) Should have the signature function($value, $fieldname).
     * @return string
     */
    public static function fill_in_datafield_placeholders($templatestr, $event = null, $stepresults = null, $transformcallback = false) {
        $fields = self::get_process_fields($event, $stepresults);

        if (false === $transformcallback) {
            // "... use ($fields)" gives this anonymous function access to the $fields
            // variable we declared a few lines up. (It's like a "closure" in JS,
            // except that you have to explicitly declare which variables are shared.)
            $callback = function ($matches) use ($fields){
                if (array_key_exists($matches[1], $fields)) {
                    return $fields[$matches[1]];
                } else {
                    // No match! Leave the template string in place.
                    return $matches[0];
                }
            };
        } else {
            // If they provided a transformcallback, then
            $callback = function ($matches) use ($fields, $transformcallback){
                if (array_key_exists($matches[1], $fields)) {
                    return $transformcallback($fields[$matches[1]], $matches[1]);
                } else {
                    // No match! Leave the template string in place.
                    return $matches[0];
                }
            };
        }

        return preg_replace_callback(
            '/\{([-_A-Za-z0-9]+\)}/',
            $callback,
            $templatestr
        );
    }
}