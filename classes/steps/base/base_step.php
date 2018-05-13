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
 * Base step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\base;

defined('MOODLE_INTERNAL') || die;

/**
 * Base step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_step {

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    abstract static public function get_step_name();

    /**
     * Returns the step description.
     *
     * @return string human readable step description.
     */
    abstract static public function get_step_desc();

    /**
     * @param \stdClass $step The `tool_trigger_steps` record for this step instance
     * @param \stdClass $trigger The `tool_trigger_queue` record for this execution
     * of the workflow.
     * @param \core\event\base $event The deserialized event that triggered this execution
     * @param \stdClass $previousstepresult Data aggregated from previous steps, to include in
     * processing this step.
     * @return array<bool, \stdClass> Returns an array. The first element is a boolean
     * indicating whether or not the step was executed successfully; the second element should
     * be the $previousstepresult object, optionally mutated to provide data to
     * later steps.
     */
    abstract public function execute($step, $trigger, $event, $previousstepresult);
}