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
            'type',
            'stepclass',
            'name',
            'description',
            'steporder'
    );

    /**
     * Class constructor.
     *
     * @param \core_form\data $mformdata Data from submitted form.
     */
    public function __construct($mformdata) {
        $this->formdata = $mformdata;
    }

    /**
     * Take JSON from the form and format ready for insertion into DB.
     *
     * @param string $formjson The JSON from the form.
     * @param int $workflowid The id for the workflow to associte step records to.
     * @return array $records The array of record objects ready for DB insertion.
     */
    public function processjson($formjson, $workflowid, $now=0) {
        $jsonobjs = json_decode($formjson);
        $records = array();

        if ($now == 0) {
            $now = time();
        }
        $steporder = 0;

        // Nested loops FTW.
        foreach ($jsonobjs as $jsonobj) {
            $record = new \stdClass();
            $data = new \stdClass();
            foreach ($jsonobj as $namevalue){
                if(in_array($namevalue->name, $this->stepfields)) {
                    $record->{$namevalue->name} = $namevalue->value;
                } else if ($namevalue->name <> 'sesskey') {
                    $data->{$namevalue->name} = $namevalue->value;
                }
            }
            $record->workflowid = $workflowid;
            $record->timecreated = $now;
            $record->timemodified = $now;
            $record->steporder = $steporder++;
            $record->data = json_encode($data);
            $records[] = $record;
        }

        return $records;
    }

    public function processform($now=0) {
        global $DB;

        if ($now == 0) {
            $now = time();
        }

        $return = true;
        $formdata = $this->formdata;
        $formjson = $formdata->stepjson;

        $workflowrecord = new \stdClass();
        $workflowrecord->name = $formdata->workflowname;
        $workflowrecord->description = json_encode($formdata->workflowdescription);
        $workflowrecord->event = $formdata->eventtomonitor;
        $workflowrecord->async = $formdata->asyncmode;
        $workflowrecord->enabled = $formdata->workflowactive;
        $workflowrecord->draft = $formdata->draftmode;
        $workflowrecord->timecreated = $now;
        $workflowrecord->timemodified = $now;
        $workflowrecord->timetriggered = 0;

        try {
            $transaction = $DB->start_delegated_transaction();
            $workflowid = $DB->insert_record('tool_trigger_workflows', $workflowrecord); // Save workflow and get back id.

            // Process step JSON and save records to db.
            $steprecords = $this->processjson($formjson, $workflowid);
            $DB->insert_records('tool_trigger_steps', $steprecords);

            // Assuming the both inserts work, we get to the following line.
            $transaction->allow_commit();
        } catch(\Exception $e) {
            $transaction->rollback($e);
            $return = false;
        }

        return $return;
    }

}
