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
 * Roles unassign action step class.
 *
 * @package    tool_trigger
 * @copyright  Ilya Tregubov <ilyatregubov@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\actions;

defined('MOODLE_INTERNAL') || die;

/**
 * HTTP Post action step class.
 *
 * @package    tool_trigger
 * @copyright  Ilya Tregubov <ilyatregubov@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class roles_unassign_action_step extends base_action_step {

    use \tool_trigger\helper\datafield_manager;

    /**
     * The fields supplied by this step.
     *
     * @var array
     */
    private static $stepfields = array();

    /**
     * The prefix to put before the new fields added to the workflow data.
     *
     * @var string
     */
    private $inputprefixuser = null;
    private $inputprefixrole = null;

    protected function init() {
        if (!is_null($this->data['inputprefixuser'])) {
            $this->inputprefixuser = $this->data['inputprefixuser'];
        } else {
            $this->inputprefixuser = 'user_';
        }
        if (!is_null ($this->data['inputprefixuser'])) {
            $this->inputprefixrole = $this->data['inputprefixrole'];
        } else {
            $this->inputprefixrole = 'user_';
        }
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_name() {
        return get_string('rolesunassignactionstepname', 'tool_trigger');
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_desc() {
        return get_string('rolesunassignactionstepdesc', 'tool_trigger');
    }

    /**
     * @param $step
     * @param $trigger
     * @param $event
     * @param $stepresults - result of previousstep to include in processing this step.
     * @return array if execution was succesful and the response from the execution.
     */
    public function execute($step, $trigger, $event, $stepresults) {
        global $CFG;

        if ($stepresults['user_suspended'] == 1) {
            foreach ($stepresults[$this->inputprefixrole . 'roles'] as $key => $value) {
                role_unassign_all(array(
                    'userid' => $stepresults[$this->inputprefixrole . 'id'],
                    'contextid' => $value['contextid'],
                    'component' => $value['component'],
                    'itemid' => $value['itemid']));
            }
        }

        return array(true, $stepresults);
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        $mform->addElement('text', 'inputprefixuser', get_string('inputprefixuser', 'tool_trigger'));
        $mform->setType('inputprefixuser', PARAM_ALPHANUMEXT);
        $mform->addRule('inputprefixuser', get_string('required'), 'required');
        $mform->setDefault('inputprefixuser', 'user_');

        $mform->addElement('text', 'inputprefixrole', get_string('inputprefixrole', 'tool_trigger'));
        $mform->setType('inputprefixrole', PARAM_ALPHANUMEXT);
        $mform->addRule('inputprefixrole', get_string('required'), 'required');
        $mform->setDefault('inputprefixrole', 'user_');
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