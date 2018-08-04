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

defined('MOODLE_INTERNAL') || die;

/**
 * Base filter step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class numcompare_filter_step extends base_filter_step {
    use \tool_trigger\helper\datafield_manager;

    const OPERATOR_EQUAL = '==';
    const OPERATOR_NOTEQUAL = '!=';
    const OPERATOR_LT = '<';
    const OPERATOR_LTE = '<=';
    const OPERATOR_GTE = '>=';
    const OPERATOR_GT = '>';



    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::init()
     */
    protected function init() {
        $this->field1 = $this->data['field1'];
        $this->field2 = $this->data['field2'];
        $this->operator = $this->data['operator'];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::execute()
     */
    public function execute($step, $trigger, $event, $stepresults) {
        $this->update_datafields($event, $stepresults);
        $field1val = $this->get_field_value($this->field1);
        $field2val = $this->get_field_value($this->field2);

        $result = null;
        switch($this->operator) {
            case self::OPERATOR_EQUAL:
                $result = ($field1val == $field2val);
                break;
            case self::OPERATOR_NOTEQUAL:
                $result = ($field1val != $field2val);
                break;
            case self::OPERATOR_LT:
                $result = ($field1val < $field2val);
                break;
            case self::OPERATOR_LTE:
                $result = ($field1val <= $field2val);
                break;
            case self::OPERATOR_GTE:
                $result = ($field1val >= $field2val);
                break;
            case self::OPERATOR_GT:
                $result = ($field1val > $field2val);
                break;
            default:
                throw new \coding_exception('Invalid comparison operator ' . $this->operator);
        }
        return [$result, $stepresults];
    }

    private function get_field_value($formval) {
        $formval = trim($formval);

        // If it's a numeric string, then return its numeric value.
        if (is_numeric($formval)) {
            return ($formval + 0);
        }

        // If they entered a {placeholder} string, remove the curly brackets.
        if (strlen($formval) >= 3 && $formval[0] == '{' && substr($formval, -1) == '}') {
            $formval = substr($formval, 1, -1);
        }

        // Now check to see if we have a valid datafield name.
        $allfields = $this->get_datafields();
        if (array_key_exists($formval, $allfields)) {
            $formval = $allfields[$formval];
        } else {
            throw new \invalid_parameter_exception('Found no datafield named ' . $formval);
        }

        if (is_numeric($formval)) {
            return ($formval + 0);
        } else {
            throw new \invalid_parameter_exception('Datafield is not numeric ' . $formval);
        }
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_desc()
     */
    public static function get_step_desc() {
        // TODO: lang string!
        return 'Compare two numbers';
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public static function get_step_name() {
        // TODO: lang string!
        return 'Number comparison';
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        $fields = [];
        // TODO: lang string!
        $fields[] = $mform->createElement('text', 'field1', 'Field 1', ['placeholder' => 'fieldname']);
        $mform->setType('field1', PARAM_ALPHANUMEXT);

        // TODO: lang strings!
        $fields[] =& $mform->createElement('select', 'operator', 'Operator', [
            self::OPERATOR_EQUAL => '=',
            self::OPERATOR_NOTEQUAL => '!=',
            self::OPERATOR_LTE => '<',
            self::OPERATOR_LT => '<=',
            self::OPERATOR_GT => '>',
            self::OPERATOR_GTE => '>='
        ]);

        // TODO: lang string!
        $fields[] =& $mform->createElement('text', 'field2', 'Field 2', ['placeholder' => '1000']);
        $mform->setType('field2', PARAM_ALPHANUMEXT);

        $mform->addGroup($fields, 'numcomparegroup', '', [' '], false);
        $mform->addRule('numcomparegroup', get_string('required'), 'required');
    }

    /**
     * Get a list of fields this step provides.
     *
     * @return array $stepfields The fields this step provides.
     */
    public static function get_fields() {
        return false;

    }
}