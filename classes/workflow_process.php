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
 * Process workflow form.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger;

defined('MOODLE_INTERNAL') || die();

/**
 * Process workflow form.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workflow_process {

    /**
     * @var \stdClass The data from the submitted form.
     */
    protected $formdata;

    /**
     * @var array Array of fields to filter from step JSON.
     */
    protected $stepfields = array(
            'id',
            'type',
            'stepclass',
            'name',
            'description',
            'steporder'
    );

    /**
     * Class constructor.
     *
     * @param null|\stdClass $mformdata Data from submitted form.
     */
    public function __construct($mformdata = null) {
        if ($mformdata !== null) {
            $this->formdata = $mformdata;
        }
    }

    /**
     * Converts a workflow object into the data structure needed to fill in the
     * default values in the "edit workflow" form.
     *
     * @param int $workflowid
     * @return array
     */
    public function to_form_defaults($workflowid) {
        $workflow = workflow_manager::get_workflow($workflowid);
        return [
            'workflowid' => $workflow->id,
            'workflowname' => $workflow->workflow->name,
            'workflowdescription' => [
                'text' => $workflow->descriptiontext,
                'format' => $workflow->descriptionformat
            ],
            'eventtomonitor' => $workflow->event,
            'draftmode' => $workflow->draft,
            'workflowactive' => $workflow->active,
            'stepjson' => $this->encode_steps_to_json_for_form($workflow)
        ];
    }

    /**
     * Encode the workflow's steps into the JSON format used by the
     * form's modal. This is effectively the reverse operation to
     * $this->processjson().
     *
     * @param \tool_trigger\workflow $workflow
     * @return string
     */
    public function encode_steps_to_json_for_form($workflow) {
        global $DB;

        $steps = $DB->get_records(
            'tool_trigger_steps',
            ['workflowid' => $workflow->id],
            'steporder',
            // Fetch all the "stepfields" fields from the database, as well
            // as the "data" field which has json-encoded additional data
            // that may vary by step type.
            implode(
                ', ',
                array_merge($this->stepfields, ['data'])
            )
        );
        if (!$steps) {
            return '';
        }

        // Deserialize the JSON data, and merge the values from the
        // named database columns, with the values from the deserialized
        // json.
        $stepsforjson = [];
        foreach ($steps as $step) {
            $stepdata = json_decode($step->data, true);
            unset($step->data);
            if ($stepdata !== null) {
                $flattenedstep = array_merge($stepdata, (array) $step);
            }
            $stepsforjson[] = $flattenedstep;
        }
        return json_encode(array_values($stepsforjson));
    }

    /**
     * Take JSON from the form and format ready for insertion into DB.
     * This is effectively the reverse operation to $this->encode_steps_to_json_for_form().
     *
     * @param string $formjson The JSON from the form.
     * @param int $workflowid The id for the workflow to associte step records to.
     * @param int $now the current timestamp.
     * @return array $records The array of record objects ready for DB insertion.
     */
    public function processjson($formjson, $workflowid, $now=0) {
        $jsonobjs = json_decode($formjson, true);
        $records = [];

        if ($now == 0) {
            $now = time();
        }
        $steporder = 0;

        // Nested loops FTW.
        foreach ($jsonobjs as $jsonobj) {
            $record = [];
            // Extract the fields that are stored in specific database columns.
            foreach ($this->stepfields as $field) {
                if (array_key_exists($field, $jsonobj)) {
                    $record[$field] = $jsonobj[$field];
                    unset($jsonobj[$field]);
                }
            }

            $record = (object) $record;
            $record->workflowid = $workflowid;
            $record->timecreated = $now;
            $record->timemodified = $now;
            $record->steporder = $steporder++;
            // Store other fields as serialized data in the DB.
            if (!$jsonobj) {
                $record->data = '';
            } else {
                $record->data = json_encode($jsonobj);
            }
            $records[] = $record;
        }

        return $records;
    }

    /**
     * Process the form.
     *
     * @param int $now
     * @return boolean
     */
    public function processform($now=0) {
        global $DB;

        if ($now == 0) {
            $now = time();
        }

        $return = true;
        $formdata = $this->formdata;
        $formjson = $formdata->stepjson;

        $isnewworkflow = ($formdata->workflowid == 0);

        $workflowrecord = new \stdClass();
        $workflowrecord->name = $formdata->workflowname;
        $workflowrecord->description = json_encode($formdata->workflowdescription);
        $workflowrecord->event = $formdata->eventtomonitor;
        $workflowrecord->enabled = $formdata->workflowactive;
        $workflowrecord->draft = $formdata->draftmode;
        $workflowrecord->timecreated = $now;
        $workflowrecord->timemodified = $now;
        $workflowrecord->timetriggered = 0;

        try {
            $transaction = $DB->start_delegated_transaction();
            if ($isnewworkflow) {
                $workflowid = $DB->insert_record('tool_trigger_workflows', $workflowrecord);
            } else {
                $workflowid = $formdata->workflowid;
                $workflowrecord->id = $workflowid;
                $DB->update_record('tool_trigger_workflows', $workflowrecord);
            }

            if ($formdata->isstepschanged) {

                // Process the JSON into records for the database.
                $submittedsteps = $this->processjson($formjson, $workflowid);
                list($stepstoinsert, $stepstoupdate, $stepstodelete) = $this->find_changed_steps($workflowid, $submittedsteps);

                if (count($stepstoinsert)) {
                    $DB->insert_records('tool_trigger_steps', $stepstoinsert);
                }

                if (count($stepstoupdate)) {
                    foreach ($stepstoupdate as $steprec) {
                        $DB->update_record('tool_trigger_steps', $steprec, true);
                    }
                }

                if (count($stepstodelete)) {
                    $DB->delete_records_list('tool_trigger_steps', 'id', $stepstodelete);
                }
            }

            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
            $return = false;
        }

        return $return;
    }

    /**
     *
     * @param string $filejson
     * @return \stdClass
     */
    static public function import_prep($filejson) {
        $content = json_decode($filejson, true);

        $data = new \stdClass();
        $data->workflowid = 0;
        $data->workflowname = $content['name'];
        $data->workflowdescription = json_decode($content['description']);
        $data->eventtomonitor = $content['event'];
        $data->workflowactive = 0;
        $data->draftmode = 0;
        $data->isstepschanged = 1;

        // Format and flatten step data.
        $cleansteps = array();
        foreach ($content['steps'] as $step) {
            $stepdata = json_decode($step['data']);
            foreach ($stepdata as $key => $value) {
                $step[$key] = $value;
            }
            unset($step['id']);
            unset($step['data']);
            $cleansteps[] = $step;
        }

        $data->stepjson = json_encode($cleansteps);
        return $data;
    }

    /**
     * When making changes to an existing workflow, compare the workflow's current steps in the database, to the steps that came
     * from the form submission, and figure out what changes we need to make to the DB records.
     * @param int $workflowid
     * @param array $submittedsteps
     * @return array An array containing the steps to be inserted, steps to be updated, and a list of the IDs of the steps to
     * delete
     */
    protected function find_changed_steps($workflowid, $submittedsteps) {
        global $DB;

        // Get the IDs of the existing steps for this workflow.
        $oldstepids = $DB->get_fieldset_select(
            'tool_trigger_steps',
            'id',
            'workflowid = :workflowid',
            ['workflowid' => $workflowid]
        );

        $stepstoinsert = [];
        $stepstoupdate = [];
        $stepstodelete = [];

        foreach ($submittedsteps as &$curstep) {
            if (!property_exists($curstep, 'id') || !$curstep->id) {
                // No ID means that the step is not yet in the database.
                unset($curstep->id); // Remove the ID field, in case it's an empty string or 0.
                $stepstoinsert[] = $curstep;
            } else {
                $i = array_search($curstep->id, $oldstepids);
                if (false === $i) {
                    // An update to a step that is not currently in the database?
                    // This shouldn't happen normally, but could be a result of a
                    // duplicate submission? We'll treat it as a new insert.
                    \core\notification::warning(
                        'Step with an invalid database ID present in form data.'
                        . ' Check that the steps as submitted look correct.'
                    );

                    unset($curstep->id); // Clear the ID so we can do a new insert.
                    $stepstoinsert[] = $curstep;
                } else {
                    // A step that's already in the database. Update it.
                    if (array_key_exists($curstep->id, $stepstoupdate)) {
                        \core\notification::warning(
                            'Duplicate step database IDs in form data.'
                            .' Check that the steps as submitted look correct.'
                        );
                    }
                    $stepstoupdate[] = $curstep;
                }
            }
        }

        // Delete any steps whose ID is in oldstepids, but not in the
        // submitted steps.
        $stepstodelete = array_diff(
            $oldstepids,
            array_column($stepstoupdate, 'id')
        );

        return [$stepstoinsert, $stepstoupdate, $stepstodelete];
    }
}
