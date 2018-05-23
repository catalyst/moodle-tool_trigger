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
     * Build form.
     */
    public function definition() {
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
            $attributes = array('cols' => '50', 'rows' => '5');
            $mform->addElement('textarea', 'description', get_string ('stepdescription', 'tool_trigger'), $attributes);
            $mform->setType('description', PARAM_ALPHAEXT);
            $mform->addRule('description', get_string('required'), 'required');
            $mform->addHelpButton('description', 'stepdescription', 'tool_trigger');

            // Additional fields specific to the step type.
            $this->step->form_definition_extra($this, $this->_form, $this->_customdata);

            // TODO: If it's helpful, add additional hooks into other form methods like validate() and definition_after_data().
            //
            // Although, if a step needs to customize the form that much, it may be better off
            // just using its own form subclass.
        }
    }

}