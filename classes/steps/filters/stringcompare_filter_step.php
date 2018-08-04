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
class stringcompare_filter_step extends base_filter_step {
    use \tool_trigger\helper\datafield_manager;

    /**
     * Constant exactly matches.
     *
     * @var string
     */
    const OPERATOR_EQUAL = 'exactly matches';

    /**
     * Constant starts with.
     *
     * @var string
     */
    const OPERATOR_STARTS_WITH = 'starts with';

    /**
     * Constant ends with.
     *
     * @var string
     */
    const OPERATOR_ENDS_WITH = 'ends with';

    /**
     * Constant contains.
     *
     * @var string
     */
    const OPERATOR_CONTAINS = 'contains';

    /**
     * Constant matches regex.
     *
     * @var string
     */
    const OPERATOR_REGEX = 'matches regex';

    /**
     * The first field for comparison. May contain datafield placeholders.
     *
     * @var string
     */
    protected $field1;

    /**
     * The second field for comparison. May contain datafield placeholders.
     *
     * @var string
     */
    protected $field2;

    /**
     * The type of comparison to perform. Must be one of the OPERATOR_* constants
     * defined in this class.
     *
     * @var string
     */
    protected $operator;

    /**
     * Whether we're expecting the string to match (true) or to *not* match (false).
     *
     * @var bool
     */
    protected $wantmatch = true;

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::init()
     */
    protected function init() {
        $this->field1 = $this->data['field1'];
        $this->field2 = $this->data['field2'];
        $this->operator = $this->data['operator'];
        $this->wantmatch = (bool) $this->data['wantmatch'];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::execute()
     */
    public function execute($step, $trigger, $event, $stepresults) {
        $this->update_datafields($event, $stepresults);
        $field1val = $this->render_datafields($this->field1);
        $field2val = $this->render_datafields($this->field2);

        $ismatch = null;
        switch($this->operator) {
            case self::OPERATOR_EQUAL:
                $ismatch = ($field1val === $field2val);
                break;
            case self::OPERATOR_STARTS_WITH:
                $ismatch = (0 === mb_strpos($field1val, $field2val));
                break;
            case self::OPERATOR_ENDS_WITH:
                // Look for the position of the last occurrence of field2 in field1.
                $pos = mb_strrpos($field1val, $field2val);

                if ($pos === false) {
                    // If it's not there at all, then return false.
                    $ismatch = false;
                } else {
                    // If it is there, then see if its position is such that it's exactly at the end of the string.
                    $ismatch = ($pos === (mb_strlen($field1val) - mb_strlen($field2val)));
                }
                break;
            case self::OPERATOR_CONTAINS:
                $ismatch = (false !== mb_strpos($field1val, $field2val));
                break;
            case self::OPERATOR_REGEX:
                $regex = '/' . str_replace('/', '\/', $field2val) . '/msu';
                $ismatch = @preg_match($regex, $field1val);
                if (false === $ismatch) {
                    throw new \invalid_parameter_exception('Invalid regex: ' . $regex);
                }
                $ismatch = (bool) $ismatch;
                break;
            default:
                throw new \coding_exception('Invalid comparison operator ' . $this->operator);
        }

        // Check whether they wanted the pattern to match, or not match.
        $result = ($ismatch == $this->wantmatch);

        return [$result, $stepresults];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_desc()
     */
    public static function get_step_desc() {
        // TODO: lang string!
        return 'Compare two strings';
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public static function get_step_name() {
        // TODO: lang string!
        return 'String comparison';
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        $fields = [];
        // TODO: lang string!
        $fields[] = $mform->createElement('text', 'field1', 'Field 1', ['placeholder' => '{user_username}']);
        $mform->setType('field1', PARAM_RAW);

        $fields[] = $mform->createElement('select', 'wantmatch', 'Reverse match?', [
            1 => 'does',
            0 => 'does not'
        ]);

        // TODO: lang strings!
        $fields[] =& $mform->createElement('select', 'operator', 'Operator', [
            self::OPERATOR_EQUAL => 'exactly match',
            self::OPERATOR_CONTAINS => 'contain',
            self::OPERATOR_STARTS_WITH => 'start with',
            self::OPERATOR_ENDS_WITH => 'end with',
            self::OPERATOR_REGEX => 'match regex'
        ]);

        // TODO: lang string!
        $fields[] =& $mform->createElement('text', 'field2', 'Field 2', ['placeholder' => 'admin']);
        $mform->setType('field2', PARAM_RAW);

        $mform->addGroup($fields, 'stringcomparegroup', '', [' '], false);
        $mform->addRule('stringcomparegroup', get_string('required'), 'required');
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