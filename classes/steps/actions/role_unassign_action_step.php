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
 * Role unassignment action step class.
 *
 * @package    tool_trigger
 * @copyright  2021 Catalyst IT
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\actions;

class role_unassign_action_step extends base_action_step {

    use \tool_trigger\helper\datafield_manager;

    /**
     * The fields supplied by this step.
     *
     * @var array
     */
    private static $stepfields = ['role_unassign_result', 'role_unassign_role_id'];

    /**
     * User id field.
     * @var int
     */
    private $useridfield;

    /** Role id field.
     * @var int
     */
    private $roleidfield;

    /**
     * Context id field.
     * @var int
     */
    private $contextidfield;

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    public static function get_step_name() {
        return get_string('roleunassignactionstepname', 'tool_trigger');
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    public static function get_step_desc() {
        return get_string('roleunassignactionstepdesc', 'tool_trigger');
    }

    /**
     * @inheritdoc
     */
    protected function init() {
        $this->useridfield = $this->data['useridfield'];
        $this->roleidfield = $this->data['roleidfield'];
        $this->contextidfield = $this->data['contextidfield'];
    }

    /**
     * @inheritdoc
     */
    public function execute($step, $trigger, $event, $stepresults) {

        $datafields = $this->get_datafields($event, $stepresults);

        $roleid = (int)$this->roleidfield;
        if (empty($roleid)) {
            if (!array_key_exists($this->roleidfield, $datafields)) {
                throw new \invalid_parameter_exception("Specified roleid field not present in the workflow data: "
                    . $this->roleidfield);
            }

            $roleid = $datafields[$this->roleidfield];
        }

        $userid = (int)$this->useridfield;
        if (empty($userid)) {
            if (!array_key_exists($this->useridfield, $datafields)) {
                throw new \invalid_parameter_exception("Specified userid field not present in the workflow data: "
                    . $this->useridfield);
            }

            $userid = $datafields[$this->useridfield];
        }

        $contextid = (int)$this->contextidfield;
        if (empty($contextid)) {
            if (!array_key_exists($this->contextidfield, $datafields)) {
                throw new \invalid_parameter_exception("Specified contextid field not present in the workflow data: "
                    . $this->contextidfield);
            }

            $contextid = $datafields[$this->contextidfield];
        }

        role_unassign($roleid, $userid, $contextid);

        $stepresults['role_unassign_result'] = true;
        $stepresults['role_unassign_role_id'] = $this->roleidfield;

        return [true, $stepresults];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        $mform->addElement('text', 'useridfield', get_string('step_action_role_unassign_useridfield', 'tool_trigger'));
        $mform->setType('useridfield', PARAM_ALPHANUMEXT);
        $mform->addRule('useridfield', get_string('required'), 'required');
        $mform->setDefault('useridfield', 'userid');
        $mform->addHelpButton('useridfield', 'useridfield', 'tool_trigger');

        $mform->addElement('text', 'roleidfield', get_string('step_action_role_unassign_roleidfield', 'tool_trigger'));
        $mform->setType('roleidfield', PARAM_ALPHANUMEXT);
        $mform->addRule('roleidfield', get_string('required'), 'required');
        $mform->setDefault('roleidfield', 'roleid');
        $mform->addHelpButton('roleidfield', 'roleidfield', 'tool_trigger');

        $mform->addElement('text', 'contextidfield', get_string('step_action_role_unassign_contextidfield', 'tool_trigger'));
        $mform->setType('contextidfield', PARAM_ALPHANUMEXT);
        $mform->addRule('contextidfield', get_string('required'), 'required');
        $mform->setDefault('contextidfield', 'contextid');
        $mform->addHelpButton('contextidfield', 'contextidfield', 'tool_trigger');
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
