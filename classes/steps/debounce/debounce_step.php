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
 * Debounce step class.
 *
 * The debounce step is a special step that queues up the workflow to be run after a certain
 * period of time, using ONLY the latest instance of the workflow to occur in the period,
 * with a period reset occuring at each new workflow instance trigger.
 *
 * @package    tool_trigger
 * @copyright  Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\debounce;
use tool_trigger\steps\base\base_step;

defined('MOODLE_INTERNAL') || die;

/**
 * Debounce step class.
 *
 * @package    tool_trigger
 * @copyright  Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class debounce_step extends base_step {
    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_type()
     */
    public static function get_step_type() {
        return base_step::STEPTYPE_DEBOUNCE;
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_type_desc()
     */
    public static function get_step_type_desc() {
        return get_string('debounce_desc', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_type_desc()
     */
    public static function get_step_type_name() {
        return get_string('debounce', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::init()
     */
    protected function init() {

    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::execute()
     */
    public function execute($step, $trigger, $event, $stepresults) {
        //return [$result, $stepresults];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_desc()
     */
    public static function get_step_desc() {
        return get_string('debounce_desc', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public static function get_step_name() {
        return get_string('debounce', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public function make_form($customdata, $ajaxformdata) {
        return new debounce_form(null, $customdata, 'post', '', null, true, $ajaxformdata, $this);
    }

    /**
     * Get a list of fields this step provides.
     *
     * @return array $stepfields The fields this step provides.
     */
    public static function get_fields() {
        return false;
    }

    public function form_definition_extra($form, $mform, $customdata){
        return;
    }
}