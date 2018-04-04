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
 * Base trigger step form class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\triggers;

use tool_trigger\steps\base\base_form;

defined('MOODLE_INTERNAL') || die;

/**
 * Base trigger step form class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class base_trigger_form extends base_form {
    public function definition() {
        parent::definition();
        $mform = $this->_form;

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
