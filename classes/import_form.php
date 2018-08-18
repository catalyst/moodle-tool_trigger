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
 * Import workflow form class.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Import workflow form class.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends \moodleform {

    /**
     * Build form for importing woekflows.
     *
     * {@inheritDoc}
     * @see \moodleform::definition()
     */
    public function definition() {

        $mform = $this->_form;

        // Workflow file.
        $mform->addElement('filepicker', 'userfile', get_string('workflowfile', 'tool_trigger'), null,
            array('maxbytes' => 256000, 'accepted_types' => '.json'));
        $mform->addRule('userfile', get_string('required'), 'required');
    }

    //Custom validation should be added here
    function validation($data, $files) {
        return array('userfile' => 'you are all fuckers');
    }

    public function get_errors() {
        $form = $this->_form;
        $errors = $form->_errors;

        return $errors;
    }
}
