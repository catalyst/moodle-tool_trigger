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
 * HTTP Post trigger step form class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\triggers;

defined('MOODLE_INTERNAL') || die;

/**
 * HTTP Post trigger step form class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class http_post_trigger_form extends base_trigger_form {

    public function definition() {
        parent::definition();
        $mform = $this->_form;

        // URL.
        $attributes = array('size' => '50');
        $mform->addElement('text', 'httposttiggerurl', get_string ('httposttiggerurl', 'tool_trigger'), $attributes);
        $mform->setType('httposttiggerurl', PARAM_URL);
        $mform->addRule('httposttiggerurl', get_string('required'), 'required');
        $mform->addHelpButton('httposttiggerurl', 'httposttiggerurl', 'tool_trigger');
        if (isset($this->_customdata['httposttiggerurl'])) {
            $mform->setDefault('httposttiggerurl', $this->_customdata['httposttiggerurl']);
        }

        // Headers.
        $attributes = array('cols' => '50', 'rows' => '5');
        $mform->addElement('textarea', 'httposttiggerheaders', get_string ('httposttiggerheaders', 'tool_trigger'), $attributes);
        $mform->setType('httposttiggerheaders', PARAM_RAW_TRIMMED);
        $mform->addRule('httposttiggerheaders', get_string('required'), 'required');
        $mform->addHelpButton('httposttiggerheaders', 'httposttiggerheaders', 'tool_trigger');
        if (isset($this->_customdata['httposttiggerheaders'])) {
            $mform->setDefault('httposttiggerheaders', $this->_customdata['httposttiggerheaders']);
        }

        // Params.
        $attributes = array('cols' => '50', 'rows' => '5');
        $mform->addElement('textarea', 'httposttiggerparams', get_string ('httposttiggerparams', 'tool_trigger'), $attributes);
        $mform->setType('httposttiggerparams', PARAM_RAW_TRIMMED);
        $mform->addRule('httposttiggerparams', get_string('required'), 'required');
        $mform->addHelpButton('httposttiggerparams', 'httposttiggerparams', 'tool_trigger');
        if (isset($this->_customdata['httposttiggerparams'])) {
            $mform->setDefault('httposttiggerparams', $this->_customdata['httposttiggerparams']);
        }

    }

}
