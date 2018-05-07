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
 * HTTP Post trigger step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\triggers;

use tool_trigger\steps\triggers\base_trigger_step;

defined('MOODLE_INTERNAL') || die;

/**
 * HTTP Post trigger step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class http_post_trigger_step extends base_trigger_step {

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_name() {
       return get_string('httpposttriggerstepname', 'tool_trigger');
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_desc() {
        return get_string('httpposttriggerstepdesc', 'tool_trigger');
    }

    public function execute($step, $trigger) {
        // TODO: DO SOMETHING HERE.
        mtrace("execute trigger");
        return true;
    }
}