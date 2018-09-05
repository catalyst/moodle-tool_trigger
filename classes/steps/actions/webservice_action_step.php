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
 * HTTP Post action step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\actions;

defined('MOODLE_INTERNAL') || die;

/**
 * Webservice action step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservice_post_action_step extends base_action_step {

    use \tool_trigger\helper\datafield_manager;

    /**
     * The fields suplied by this step.
     *
     * @var array
     */
    private static $stepfields = array(

    );

    protected function init() {

    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_name() {
        return get_string('webserviceactionstepname', 'tool_trigger');
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_desc() {
        return get_string('webserviceactionstepdesc', 'tool_trigger');
    }

    /**
     * @param $step
     * @param $trigger
     * @param $event
     * @param $stepresults - result of previousstep to include in processing this step.
     * @return array if execution was succesful and the response from the execution.
     */
    public function execute($step, $trigger, $event, $stepresults) {


        return array(true, $stepresults);
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {

        // Webservice.
        $attributes = array('size' => '50', 'placeholder' => 'https://www.example.com/api', 'type' => 'url');
        $mform->addElement('text', 'url', get_string ('httpostactionurl', 'tool_trigger'), $attributes);
        // PARAM_URL will reject some templated urls.
        // TODO: Put some validation on this field?
        $mform->setType('url', PARAM_RAW_TRIMMED);
        $mform->addRule('url', get_string('required'), 'required');
        $mform->addHelpButton('url', 'httpostactionurl', 'tool_trigger');

        // User.
        $attributes = array('cols' => '50', 'rows' => '2');
        $mform->addElement('textarea', 'httpheaders', get_string ('httpostactionheaders', 'tool_trigger'), $attributes);
        $mform->setType('httpheaders', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('httpheaders', 'httpostactionheaders', 'tool_trigger');

    }

    /**
     * Get a list of fields this step provides.
     *
     * @return array $stepfields The fields this step provides.
     */
    public static function get_fields() {
        return self::$stepfields;
    }
}