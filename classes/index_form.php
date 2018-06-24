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
 * Main Admin settings form class.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger;

use html_writer;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Main Admin settings form class.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index_form extends \moodleform {

    /**
     * Build form for the general setting admin page for plugin.
     */
    public function definition() {
        $config = get_config('tool_trigger');
        $mform = $this->_form;

        // Cleanup Heading.
        $mform->addElement('header', 'cleanupsettings', get_string('cleanupsettings', 'tool_trigger'));
        $desccontent = html_writer::div(get_string('cleanupsettingsdesc', 'tool_trigger'), 'form_description');
        $mform->addElement('html', $desccontent);

        $cleanupoptions = array(
            21600 => get_string('numhours', '', 6),
            43200 => get_string('numhours', '', 12),
            86400 => get_string('numhours', '', 24),
            172800 => get_string('numdays', '', 2),
            604800 => get_string('numdays', '', 7)
        );
        $mform->addElement('select', 'timetocleanup', get_string('timetocleanup', 'tool_trigger'), $cleanupoptions);
        $mform->addHelpButton('timetocleanup', 'timetocleanup', 'tool_trigger');
        if (isset($config->timetocleanup)) {
            $mform->setDefault('timetocleanup', $config->timetocleanup);
        } else {
            $mform->setDefault('timetocleanup', 86400);
        }

        // Learning mode settings.
        $mform->addElement('header', 'learningsettings', get_string('learningsettings', 'tool_trigger'));
        $desccontent = html_writer::div(get_string('learningsettingsdesc', 'tool_trigger'), 'form_description');
        $mform->addElement('html', $desccontent);

        $mform->addElement('advcheckbox', 'learning',  get_string ('learning', 'tool_trigger'), 'Enable', array(), array(0, 1));
        $mform->setType('learning', PARAM_INT);
        $mform->addHelpButton('learning', 'learning', 'tool_trigger');
        if (isset($config->learning)) {
            $mform->setDefault('learning', $config->learning);
        } else {
            $mform->setDefault('learning', 0);
        }

        $this->add_action_buttons();
    }

}
