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
 * Filter form class for workflowhistory.
 *
 * @package    tool_trigger
 * @copyright  Catalyst IT, 2021
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\output\workflowhistory;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Filter form class for workflowhistory.
 *
 * @package    tool_trigger
 * @copyright  Catalyst IT, 2021
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'workflow');
        $mform->setType('workflow', PARAM_INT);

        $mform->addElement('header', 'filterheader', get_string('filter', 'tool_trigger'));
        $mform->setExpanded('filterheader', true);

        $filterarr = [
            $mform->createElement('advcheckbox', 'filterpassed', '', get_string('filterpassed', 'tool_trigger'), ['group' => 'filter']),
            $mform->createElement('advcheckbox', 'filterdeferred', '', get_string('filterdeferred', 'tool_trigger'), ['group' => 'filter']),
            $mform->createElement('advcheckbox', 'filterfailed', '', get_string('filterfailed', 'tool_trigger'), ['group' => 'filter']),
            $mform->createElement('advcheckbox', 'filtererrored', '', get_string('filtererrored', 'tool_trigger'), ['group' => 'filter']),
            $mform->createElement('advcheckbox', 'filtercancelled', '', get_string('filtercancelled', 'tool_trigger'), ['group' => 'filter']),
        ];

        $mform->addGroup($filterarr, 'filterarr', get_string('filterlabelrunstatus', 'tool_trigger'), [' '], false);
        $this->add_checkbox_controller('filter');

        $mform->addElement('text', 'filteruser', get_string('filterlabeluser', 'tool_trigger'), ['placeholder' => '']);
        $mform->setType('filteruser', PARAM_TEXT);

        $buttonarray = [
            $mform->createElement('submit', 'filtersubmit', get_string('filtersubmit', 'tool_trigger')),
            $mform->createElement('cancel', 'filterreset', get_string('filterreset', 'tool_trigger')),
        ];

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }
}
