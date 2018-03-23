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
     * @var \stdClass The rule object form database.
     */
    protected $formdata;

    public function __construct($mformdata) {
        $this->formdata = $mformdata;
    }

    /**
     * Take JSON from the form and format ready for insertion into DB.
     *
     * @param string $formjson The JSON from the form.
     */
    public function processjson($formjson) {

        $record1 = new \stdClass();
        $record1->name         = 'overview';
        $record1->displayorder = '10000';
        $record2 = new \stdClass();
        $record2->name         = 'overview';
        $record2->displayorder = '10000';
        $records = array($record1, $record2);

        return $records;

    }

    public function processform() {
        global $DB;

        $return = true;
        $formdata = $this->formdata;

        // Save workflow and get back id

        // Process step JSON and save records to db.
        try {
            $transaction = $DB->start_delegated_transaction();
            // Insert a record
            $DB->insert_record('foo', $object);
            $DB->insert_record('bar', $otherobject);

            // Assuming the both inserts work, we get to the following line.
            $transaction->allow_commit();
        } catch(\Exception $e) {
            $transaction->rollback($e);
        }

        return $return;
    }

}
