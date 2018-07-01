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
 */
class learn_process {

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
     * @return moodle_recordset $learntrecords The recordset of results.
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
                $other =  unserialize($value);  // Convert back to PHP array.
                $this->convert_record_type($other, true);  // Call this function recursively to process fileds contained in other.
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
     * Process the learnt events and extract the field names.
     */
    public function process () {
        $processedrecords = array();

        // Get a list of the event types from the learn table.
        $learntevents = $this->get_learnt_events();

        // For each type of event get all the entries for that event from the learn table.
        foreach ($learntevents as $learntevent) {
            $learntrecords = $this->get_learnt_records($learntevent);

            foreach ($learntrecords as $record) {
                $this->typearray = array(); // Reset typearray before calling convert_record_type.
                // Convert each record into an array where key is field name and value is type.
                $processedrecords[] = $this->convert_record_type($record, false);
            }
            $learntrecords->close(); // Don't forget to close the recordset!

            // Merge all entries into one array.

            // convert collated fields to json.

            // store collated field json in db.
        }
    }

}
