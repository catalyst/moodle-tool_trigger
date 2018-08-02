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
 * Add / edit workflow form class.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Add / edit workflow form class.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_form extends \moodleform {

    /**
     * Build form for the general setting admin page for plugin.
     *
     * {@inheritDoc}
     * @see \moodleform::definition()
     */
    public function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'workflowid');
        $mform->setType('workflowid', PARAM_INT);
        $mform->setDefault('hidden', 0);

        // Workflow name.
        $mform->addElement('text', 'workflowname', get_string ('workflowname', 'tool_trigger'), 'size="50"');
        $mform->setType('workflowname', PARAM_TEXT);
        $mform->addRule('workflowname', get_string('required'), 'required');
        $mform->addHelpButton('workflowname', 'workflowname', 'tool_trigger');

        // Workflow description.
        $editoroptions = array(
            'subdirs' => 0,
            'maxbytes' => 0,
            'maxfiles' => 0,
            'changeformat' => 0,
            'context' => \context_system::instance(),
            'noclean' => 0,
            'trusttext' => 0
        );
        $mform->addElement('editor', 'workflowdescription', get_string ('workflowdescription', 'tool_trigger'), $editoroptions);
        $mform->setType('workflowdescription', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('workflowdescription', 'workflowdescription', 'tool_trigger');

        // Event.
        $mform->addElement(
            'autocomplete',
            'eventtomonitor',
            get_string('eventtomonitor', 'tool_trigger'),
            // Choices in the menu.
            array_merge(
                // Placeholder string (because Moodle doesn't add it from the 'noselectionstring' automatically.
                ['' => get_string('choosedots')],
                $this->_customdata['plugineventlist']
            ),
            // Form element options.
            ['noselectionstring' => get_string('choosedots')]
        );
        $mform->addHelpButton('eventtomonitor', 'eventtomonitor', 'tool_trigger');
        $mform->addRule('eventtomonitor', get_string('required'), 'required');

        $mform->addElement('hidden', 'draftmode', 0);
        $mform->setType('draftmode', PARAM_INT);

        // Workflow active.
        $mform->addElement('advcheckbox',
            'workflowactive',
            get_string ('workflowactive', 'tool_trigger'),
            'Enable', array(), array(0, 1));
        $mform->setType('workflowactive', PARAM_INT);
        $mform->addHelpButton('workflowactive', 'workflowactive', 'tool_trigger');
        $mform->setDefault('workflowactive', 1);

        // Hidden text field for step JSON.
        $mform->addElement('hidden', 'stepjson');
        $mform->setType('stepjson', PARAM_RAW_TRIMMED);

        // A convenience flag to indicate whether the steps were updated or not.
        $mform->addElement('hidden', 'isstepschanged');
        $mform->setType('isstepschanged', PARAM_BOOL);
        $mform->setDefault('isstepschanged', 0);

        // Workflow steps mini table will be added here, in the
        // "definition_after_data()" function (so that it can include
        // steps from the submission in process, in case we fail
        // validation).

        // Add processing step button.
        $mform->addElement('button', 'stepmodalbutton', get_string('stepmodalbutton', 'tool_trigger'));

        $this->add_action_buttons();
    }

    /**
     * Adds the steps table to the form. (We need to do this in definition_after_data(),
     * so that it will properly re-display the table after form validation fails.)
     *
     * {@inheritDoc}
     * @see \moodleform::definition_after_data()
     */
    public function definition_after_data() {
        global $PAGE;

        $mform = $this->_form;
        $stepdatajson = $mform->getElementValue('stepjson');

        // Render the steps table using the same mustache template as the Modal form.
        $stepstable = $PAGE->get_renderer(
            'tool_trigger',
            'manageworkflows'
        )->render_workflow_steps($stepdatajson);

        // Put the table right before the "add step" button.
        $mform->insertElementBefore(
            $mform->createElement(
                'html',
                '<div id="steps-table">' . $stepstable . '</div>'
            ),
            'stepmodalbutton'
        );
    }

    /**
     * Validation. For now it just makes sure that the stepjson hidden field isn't
     * empty. If it is, it puts an error flag on the "add workflow steps" button.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['stepjson'])) {
            $errors['stepmodalbutton'] = get_string('steprequired', 'tool_trigger');
        }
        return $errors;
    }
}
