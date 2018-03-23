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
        $mform->addElement('select', 'type', get_string('steptype', 'tool_trigger'), $steptype);
        $mform->addHelpButton('type', 'steptype', 'tool_trigger');
        $mform->addRule('type', get_string('required'), 'required');
        if (isset($this->_customdata['type'])) {
            $mform->setDefault('type', $this->_customdata['type']);
        }

        // Step.
        $steps = array(
            '' =>  get_string('choosedots'),
            '/steps/triggers/log_step' =>  'LOG', // Hard coded until we build steps. TODO: fix.
        );
        $mform->addElement('select', 'stepclass', get_string('stepclass', 'tool_trigger'), $steps);
        $mform->addHelpButton('stepclass', 'stepclass', 'tool_trigger');
        $mform->addRule('stepclass', get_string('required'), 'required');
        if (isset($this->_customdata['stepclass'])) {
            $mform->setDefault('stepclass', $this->_customdata['stepclass']);
        }

        // Name.
        $attributes=array('size'=>'50');
        $mform->addElement('text', 'name', get_string ('stepname', 'tool_trigger'), $attributes);
        $mform->setType('name', PARAM_ALPHAEXT);
        $mform->addRule('name', get_string('required'), 'required');
        $mform->addHelpButton('name', 'stepname', 'tool_trigger');
        if (isset($this->_customdata['name'])) {
            $mform->setDefault('name', $this->_customdata['name']);
        }

        // Description.
        $attributes=array('cols' => '50', 'rows' => '5');
        $mform->addElement('textarea', 'description', get_string ('stepdescription', 'tool_trigger'), $attributes);
        $mform->setType('description', PARAM_ALPHAEXT);
        $mform->addRule('description', get_string('required'), 'required');
        $mform->addHelpButton('description', 'stepdescription', 'tool_trigger');
        if (isset($this->_customdata['description'])) {
            $mform->setDefault('description', $this->_customdata['description']);
        }

    }

}