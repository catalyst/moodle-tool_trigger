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
     * Build form.
     */
    public function definition() {
        $mform = $this->_form;

        // Step type.
        $steptype = array(
            '' =>  get_string('choosedots'),
            'trigger' =>  get_string('trigger', 'tool_trigger'),
            'filter' =>  get_string('filter', 'tool_trigger'),
            'lookup' =>  get_string('lookup', 'tool_trigger'),
        );
        $mform->addElement('select', 'steptype', get_string('steptype', 'tool_trigger'), $steptype);
        $mform->addHelpButton('steptype', 'steptype', 'tool_trigger');
        $mform->addRule('steptype', get_string('required'), 'required');
        if (isset($this->_customdata['steptype'])) {
            $mform->setDefault('steptype', $this->_customdata['steptype']);
        }

        // Step.
        $steps = array(
            '' =>  get_string('choosedots'),
            '/steps/trigger/log_step' =>  'LOG', // Hard coded until we build steps. TODO: fix.
        );
        $mform->addElement('select', 'step', get_string('step', 'tool_trigger'), $steps);
        $mform->addHelpButton('step', 'step', 'tool_trigger');
        $mform->addRule('step', get_string('required'), 'required');
        if (isset($this->_customdata['step'])) {
            $mform->setDefault('step', $this->_customdata['step']);
        }

        // Name.
        $attributes=array('size'=>'50');
        $mform->addElement('text', 'stepname', get_string ('stepname', 'tool_trigger'), $attributes);
        $mform->setType('stepname', PARAM_ALPHAEXT);
        $mform->addRule('stepname', get_string('required'), 'required');
        $mform->addHelpButton('stepname', 'stepname', 'tool_trigger');
        if (isset($this->_customdata['stepname'])) {
            $mform->setDefault('stepname', $this->_customdata['stepname']);
        }

        // Description.
        $attributes=array('cols' => '50', 'rows' => '5');
        $mform->addElement('textarea', 'stepdescription', get_string ('stepdescription', 'tool_trigger'), $attributes);
        $mform->setType('stepdescription', PARAM_ALPHAEXT);
        $mform->addRule('stepdescription', get_string('required'), 'required');
        $mform->addHelpButton('stepdescription', 'stepdescription', 'tool_trigger');
        if (isset($this->_customdata['stepdescription'])) {
            $mform->setDefault('stepdescription', $this->_customdata['stepdescription']);
        }

    }

}