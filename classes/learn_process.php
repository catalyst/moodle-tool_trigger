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
 * Process learnt events.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger;
defined('MOODLE_INTERNAL') || die();
/**
 * Process learnt events.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learn_process {

    /**
     * An array of the fields for that event record.
     *
     * @var array
     */
    private $typearray = array();

    /**
     * Get a list of all the distinct events names in the learning table.
     *
     * @return array $learntevents An array of the distinct event names in table.
     */
    private function get_learnt_events() {
        global $DB;

        $sql = 'SELECT DISTINCT(eventname) FROM {tool_trigger_learn_events}';
        $learntrecords = $DB->get_records_sql($sql);
        $learntevents = array_keys($learntrecords);

        return $learntevents;
    }

    /**
     * Get all the records for the learning table
     * for a specific event name.
     *
     * @param string $learntevent The name of the event to get the records for.
     * @return \moodle_recordset $learntrecords The recordset of results.
     */
    private function get_learnt_records($learntevent) {
        global $DB;

        $learntrecords = $DB->get_recordset('tool_trigger_learn_events', array('eventname' => $learntevent));

        return $learntrecords;
    }

    /**
     * This method takes an entry for the learnt events table and returns an array
     * of the fields for that event record.
     * The array is flat with the array keys being the field names and the array values
     * being the data type of the field.
     * Keys contained in the 'other' event field are prefixed with 'other_'.
     *
     * @param object $record
     * @param bool $isother
     * @return array $typearray
     */
    private function convert_record_type($record, $isother) {
        foreach ($record as $key => $value) {  // Iterate through record fields.

            if ($key == 'other') {  // Treat the 'other' field as special.
                $other = unserialize($value);  // Convert back to PHP array.
                if ($other) {
                    // Call this function recursively to process fileds contained in other.
                    $this->convert_record_type($other, true);
                }
            } else {
                if ($isother) { // If this key was a child of 'other' give it a prefix.
                    $otherkey = 'other_' . $key;
                    $this->typearray[$otherkey] = gettype($value);  // Update result array with result.
                } else {
                    $this->typearray[$key] = gettype($value); // Update result array with result.
                }

            }
        }

        return $this->typearray;
    }

    /**
     * This method takes an array of all the processed learnt records and merges
     * them together. Returning a deduplicated list of all the available fields
     * for the captured events.
     * We do this because the same event might have different fields each time it
     * is triggered, this is because not all fields are required and may change
     * based on differing initial conditions.
     *
     * @param array $processedrecords Array of processed record arrays.
     * @return array $mergedrecords Array of results with all source arrays merged together.
     */
    private function merge_records($processedrecords) {
        $mergedrecords = array_merge(...$processedrecords);

        return $mergedrecords;
    }

    /**
     * Merge the json fields from the captured event with the
     * learnt fields in the database.
     * We do this because the same event might have different fields each time it
     * is triggered, this is because not all fields are required and may change
     * based on differing initial conditions.
     *
     * @param object $record The captured event record
     * @param object $exists The existing record of learnt fields
     * @return object $record The updated record to be inserted.
     */
    private function merge_json_fields($record, $exists) {
        $recordfields = json_decode($record->jsonfields, true);
        $existsfields = json_decode($exists->jsonfields, true);

        $mergedrecords = array_merge($recordfields, $existsfields);

        $record->jsonfields = json_encode($mergedrecords);

        return $record;
    }

    /**
     * Store the available fields for an event as JSON in the database.
     *
     * @param string $learntevent The name of the event the fields relate to.
     * @param string $jsonfields JSON string of the event fields and their data types.
     */
    public function store_json_fields($learntevent, $jsonfields) {
        global $DB;

        $record = new \stdClass();
        $record->eventname = $learntevent;
        $record->jsonfields = $jsonfields;

        // We only want one set of fields per event in the database.
        // Therefore we need to follow an "upsert" pattern.
        try {
            $transaction = $DB->start_delegated_transaction();

            // Check for existing record in DB.
            $exists = $DB->get_record('tool_trigger_event_fields', array('eventname' => $learntevent), '*', IGNORE_MISSING);

            if ($exists) {  // If record exists update.
                $record->id = $exists->id;
                $record = $this->merge_json_fields($record, $exists);  // Merge records before update.
                $DB->update_record('tool_trigger_event_fields', $record);
            } else {  // If not insert.
                $DB->insert_record('tool_trigger_event_fields', $record);
            }

            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Process the learnt events and extract the field names.
     */
    public function process () {
        global $DB;

        // Get a list of the event types from the learn table.
        $learntevents = $this->get_learnt_events();

        // For each type of event get all the entries for that event from the learn table.
        foreach ($learntevents as $learntevent) {
            $processedrecords = array();
            $learntrecords = $this->get_learnt_records($learntevent);

            foreach ($learntrecords as $record) {
                $this->typearray = array(); // Reset typearray before calling convert_record_type.
                // Convert each record into an array where key is field name and value is type.
                $processedrecords[] = $this->convert_record_type($record, false);

                // Remove learnt event from DB.
                $DB->delete_records('tool_trigger_learn_events', array('id' => $record->id));
            }

            $learntrecords->close(); // Don't forget to close the recordset!

            // Merge all entries into one array.
            $mergedfields = $this->merge_records($processedrecords);

            // Convert collated fields to json.
            $jsonfields = json_encode($mergedfields);

            // Store collated field json in db.
            $this->store_json_fields($learntevent, $jsonfields);
        }

    }

    /**
     *  Retrieve all the event names we have stored fields for.
     *
     * @return array $eventnames Unique names of the events that we have fields for.
     */
    public function get_event_fields_events() {
        global $DB;

        $sql = 'SELECT DISTINCT(eventname) FROM {tool_trigger_event_fields}';
        $eventnamerecords = $DB->get_records_sql($sql);
        $eventnames = array_keys($eventnamerecords);

        return $eventnames;
    }

    /**
     * Given an event name get the stored JSON fields for that event.
     *
     * @param string $eventname The name of the event to get the fields for.
     * @return mixed|\stdClass|false $jsonfields The JSON stored feilds for the event in JSON format.
     */
    public function get_event_fields_json($eventname) {
        global $DB;
        $jsonfields = $DB->get_record(
                'tool_trigger_event_fields',
                array('eventname' => $eventname), 'jsonfields', IGNORE_MISSING);

        return $jsonfields;
    }

    /**
     * Retrieve fields and field types from the database.
     *
     * @param string $eventname The name of the event to get fields for.
     * @return array $fieldarray Event fields and types from database.
     */
    public function get_event_fields_with_type($eventname) {
        global $DB;
        $fieldarray = array();

        $jsonfields = $DB->get_record(
            'tool_trigger_event_fields',
            array('eventname' => $eventname), 'jsonfields', IGNORE_MISSING);

        if ($jsonfields) {
            $fields = json_decode($jsonfields->jsonfields, true);
            foreach ($fields as $field => $type) {
                $fieldarray[] = array(
                    'field' => $field,
                    'type' => $type
                );
            }
        }

        return $fieldarray;
    }

    /**
     * Load records from JSON fixture file into database.
     */
    public function process_fixtures() {
        global $CFG;
        // Load fixtures from JSON file.
        $filename = $CFG->dirroot . '/admin/tool/trigger/db/fixtures/event_fields.json';
        $fixturejson = file_get_contents($filename);

        // Turn JSON into PHP array so it can be iterated over.
        $fixtures = json_decode($fixturejson);

        // Pass event anme and object as JSON to strore json fields method.
        foreach ($fixtures as $eventname => $values) {
            $jsonfields = json_encode($values);
            $this->store_json_fields($eventname, $jsonfields);
        }
    }

}
