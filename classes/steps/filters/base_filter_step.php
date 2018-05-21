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
 * Base filter step class.
 *
 * A filter is a workflow step that applies a test to the workflow instance's
 * data, and halts execution of further steps if the test does not pass.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\filters;

use tool_trigger\steps\base\base_step;

defined('MOODLE_INTERNAL') || die;

/**
 * Base filter step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_filter_step extends base_step {
    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_type()
     */
    public static function get_step_type() {
        return base_step::STEPTYPE_FILTER;
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_type_desc()
     */
    public static function get_step_type_desc() {
        return get_string('filter', 'tool_trigger');
    }
}