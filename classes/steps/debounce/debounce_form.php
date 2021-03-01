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
 * Debounce step form class.
 *
 * @package    tool_trigger
 * @copyright  Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\debounce;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Debounce step form class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class debounce_form extends \tool_trigger\steps\base\base_form {

    public function definition() {
        parent::definition();

        $mform = $this->_form;

        // Now add custom loader for context filter debouncing.
        if (isset($this->_customdata['event'])) {
            $triggerfields = $this->get_trigger_fields(
                $this->_customdata['event'],
                $this->_customdata['stepclass'],
                $this->_customdata['existingsteps'],
                $this->_customdata['steporder']
            );
        } else {
            $triggerfields = [];
        }

        // Mash all the fields from the different bits together.
        if (!empty($triggerfields)) {
            $fields = array_map(function($el) {
                return $el['field'];
            }, $triggerfields['fields']);

            // Now duplicate the fieldnames into the keys.
            // Pretty frontend, pretty data structure :).
            $fields = array_combine($fields, $fields);
        } else {
            $fields = [];
        }

        // Event.
        $mform->addElement(
            'autocomplete',
            'debouncecontext',
            get_string('debouncecontext', 'tool_trigger'),
            // Choices in the menu.
            $fields,
            // Form element options.
            [
                'noselectionstring' => get_string('choosedots'),
                'multiple' => true,
            ]
        );
        $mform->setDefault('debouncecontext', ['relateduserid', 'eventname', 'userid']); // BROKEN??
        $mform->addHelpButton('debouncecontext', 'debouncecontext', 'tool_trigger');
        $mform->addRule('debouncecontext', get_string('required'), 'required', null, 'client');

        // Add debounce period.
        $mform->addElement('duration', 'debounceduration', get_string('debounceduration', 'tool_trigger'));
        $mform->setDefault('debounceduration', 30 * MINSECS);
        $mform->addRule('debounceduration', get_string('required'), 'required', null, 'client');
    }

}
