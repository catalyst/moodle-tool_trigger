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
 * Base step form class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\base;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Base step form class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class base_form extends \moodleform {

    /**
     * An instance of a step class. Used to allow steps to customize the
     * interface.
     *
     * @var \tool_trigger\steps\base\base_step
     */
    protected $step = false;

    /**
     * {@inheritDoc}
     * @see \moodleform::__construct()
     * @param \tool_trigger\steps\base\base_step $step A step instance, to add
     * fields to this form.
     */
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null,
            $editable = true, $ajaxformdata = null, $step = false) {
        $this->step = $step;
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Determine if the step being added or edited it the first
     * step for the workflow.
     * If existing steps is empty this is the first step.
     * If there is only one existing step and it is the
     * same as the $stepclass then there is only one step
     * and we are editing it.
     *
     * @param string $stepclass The name of the step being added or edited.
     * @param array $existingsteps The existing steps for the workflow.
     * @return bool $isfirst Is this the first step or not.
     */
    private function is_first_step($stepclass, $existingsteps) {
        $isfirst = false;
        $existingstepscount = count($existingsteps);

        if ($existingstepscount == 0 ) {
            $isfirst = true;
        }
        if ($existingstepscount == 1 && $existingsteps[0]['stepclass'] == $stepclass) {
            $isfirst = true;
        }

        return $isfirst;
    }

    /**
     * Format the fields retrieved from the stpe class
     * for use in the mustache template.
     *
     * @param array $stepfields The fields returned from the step class.
     * @param string $outputprefix The prefix to prepend to the fields.
     * @return array $explodedfields The formatted fields.
     */
    private function explode_fields($stepfields, $outputprefix) {
        $explodedfields = array();
        foreach ($stepfields as $field) {
            $explodedfields[] = array('field' => $outputprefix.$field);
        }

        return $explodedfields;
    }

    /**
     * Get the avialble fields for all steps in this workflow.
     *
     * @param string $eventname The eventname the workflow listens for
     * @param string $stepclass The class of the step being added or edited.
     * @param array $existingsteps The array of existing steps in workflow.
     * @return array $fields The returned fields available.
     */
    private function get_trigger_fields($eventname, $stepclass, $existingsteps, $steporder) {
        // Get all fields for this workflows event.
        $fields = array();
        $learnprocess = new \tool_trigger\learn_process();
        $fields['fields'] = $learnprocess->get_event_fields_with_type($eventname);
        $fields['steps'] = array();

        // Add notification for no event fields.
        if (empty($fields['fields'])) {
            $fields['fields'] = array('nofields' => true);
        }

        $isfirst = $this->is_first_step($stepclass, $existingsteps);

        if (!$isfirst) {
            foreach ($existingsteps as $step) {

                // Don't show fields for steps that may exist after this one.
                if ($step['steporder'] >= $steporder && $steporder != -1) {
                    break;
                }

                $stepfields = $step['stepclass']::get_fields();
                if ($stepfields) {
                    $stepfieldarray = $this->explode_fields($stepfields, $step['outputprefix']);
                    $steparray = array(
                        'stepname' => $step['stepdesc'],
                        'fields' => $stepfieldarray
                    );
                    $fields['steps'][] = $steparray;
                }
            }

        }

        return $fields;
    }

    /**
     * Build form.
     */
    public function definition() {
        global $OUTPUT;
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        $mform->addElement('hidden', 'steporder');
        $mform->setType('steporder', PARAM_INT);
        $mform->setDefault('steporder', -1);

        // Step type.
        $steptype = array(
            '' => get_string('choosedots'),
            'lookups' => get_string('lookup', 'tool_trigger'),
            'filters' => get_string('filter', 'tool_trigger'),
            'actions' => get_string('action', 'tool_trigger'),
        );
        $mform->addElement('select', 'type', get_string('steptype', 'tool_trigger'), $steptype);
        $mform->addHelpButton('type', 'steptype', 'tool_trigger');
        $mform->addRule('type', get_string('required'), 'required');
        if (isset($this->_customdata['type'])) {
            $mform->setDefault('type', $this->_customdata['type']);
        }

        // Step.
        $steps = array(
            '' => get_string('choosedots'),
        );
        if (isset($this->_customdata['stepclass'])) {
            $steps[$this->_customdata['stepclass']] = $this->_customdata['steptext'];
        }
        if (isset($this->_customdata['steps'])) {
            $steps = array_merge($steps, $this->_customdata['steps']);
        }

        $mform->addElement('select', 'stepclass', get_string('stepclass', 'tool_trigger'), $steps);
        $mform->addHelpButton('stepclass', 'stepclass', 'tool_trigger');
        $mform->addRule('stepclass', get_string('required'), 'required');
        if (isset($this->_customdata['stepclass'])) {
            $mform->setDefault('stepclass', $this->_customdata['stepclass']);
        }

        // If a step class has already been instantiated, add more step details.
        if ($this->step) {
            // Name.
            $attributes = array('size' => '50');
            $mform->addElement('text', 'name', get_string ('stepname', 'tool_trigger'), $attributes);
            $mform->setType('name', PARAM_ALPHAEXT);
            $mform->addRule('name', get_string('required'), 'required');
            $mform->addHelpButton('name', 'stepname', 'tool_trigger');

            // Description.
            $attributes = array('cols' => '50', 'rows' => '2');
            $mform->addElement('textarea', 'description', get_string ('stepdescription', 'tool_trigger'), $attributes);
            $mform->setType('description', PARAM_ALPHAEXT);
            $mform->addRule('description', get_string('required'), 'required');
            $mform->addHelpButton('description', 'stepdescription', 'tool_trigger');

            // Get available fields.
            // If this is the first step in the workflow it will just be the events fields.
            // Otherwise it will also have the fields from all the prvious steps.

            if (isset($this->_customdata['event'])) {
                $triggerfields = $this->get_trigger_fields(
                    $this->_customdata['event'],
                    $this->_customdata['stepclass'],
                    $this->_customdata['existingsteps'],
                    $this->_customdata['steporder']
                    );
                $fieldhtml = $OUTPUT->render_from_template('tool_trigger/trigger_fields', $triggerfields);
                $mform->addElement('html', $fieldhtml);
            }

            // Additional fields specific to the step type.
            $this->step->form_definition_extra($this, $this->_form, $this->_customdata);

            // TODO: If it's helpful, add additional hooks into other form methods like validate() and definition_after_data().
            //
            // Although, if a step needs to customize the form that much, it may be better off
            // just using its own form subclass.
        }
    }

}