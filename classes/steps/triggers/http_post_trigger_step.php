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

    /**
     * @param $step
     * @param $trigger
     * @param $event
     * @param $previousstepresult - result of previousstep to include in processing this step.
     * @return array if execution was succesful and the response from the execution.
     */
    public function execute($step, $trigger, $event, $previousstepresult) {
        // TODO: DO SOMETHING HERE.
        mtrace("execute trigger");
        return array(true, $previousstepresult);
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {

        // URL.
        $attributes = array('size' => '50', 'placeholder' => 'https://www.example.com/api', 'type' => 'url');
        $mform->addElement('text', 'httposttiggerurl', get_string ('httposttiggerurl', 'tool_trigger'), $attributes);
        $mform->setType('httposttiggerurl', PARAM_URL);
        $mform->addRule('httposttiggerurl', get_string('required'), 'required');
        $mform->addHelpButton('httposttiggerurl', 'httposttiggerurl', 'tool_trigger');

        // Headers.
        $attributes = array('cols' => '50', 'rows' => '5');
        $mform->addElement('textarea', 'httposttiggerheaders', get_string ('httposttiggerheaders', 'tool_trigger'), $attributes);
        $mform->setType('httposttiggerheaders', PARAM_RAW_TRIMMED);
        $mform->addRule('httposttiggerheaders', get_string('required'), 'required');
        $mform->addHelpButton('httposttiggerheaders', 'httposttiggerheaders', 'tool_trigger');

        // Params.
        $attributes = array('cols' => '50', 'rows' => '5');
        $mform->addElement('textarea', 'httposttiggerparams', get_string ('httposttiggerparams', 'tool_trigger'), $attributes);
        $mform->setType('httposttiggerparams', PARAM_RAW_TRIMMED);
        $mform->addRule('httposttiggerparams', get_string('required'), 'required');
        $mform->addHelpButton('httposttiggerparams', 'httposttiggerparams', 'tool_trigger');
    }
}