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
 * Worklfow manager class.
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
     * Get workflows.
     *
     * @param int $limitfrom Limit from which to fetch worklfows.
     * @param int $limitto  Limit to which workflows need to be fetched.
     * @return array List of worklfows .
     */
    public static function get_workflows($limitfrom = 0, $limitto = 0) {
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

    public function get_steps_with_names($stepclasses) {
        $stepnames = array();

        foreach ($stepclasses as $stepclass) {

            $stepclass = '\tool_trigger\steps\triggers\\' . $stepclass;
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
        $stepswithnames = $this->get_steps_with_names($matchedsteps);

        return $stepswithnames;
    }

}