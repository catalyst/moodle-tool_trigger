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

namespace tool_trigger\steps\lookups;

defined('MOODLE_INTERNAL') || die;

/**
 * A lookup step that takes a ID and looks up context data.
 *
 * @package    tool_trigger
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_lookup_step extends base_lookup_step {

    use \tool_trigger\helper\datafield_manager;

    /**
     * The data field to get the ID from.
     * @var string
     */
    private $instanceid = null;

    /**
     * The data field to get the ID from.
     * @var string
     */
    private $contextlevel = null;

    /**
     * The prefix to put before the new fields added to the workflow data.
     *
     * @var string
     */
    private $outputprefix = null;

    /**
     * The fields suplied by this step.
     *
     * @var array
     */
    private static $stepfields = [
        'id',
        'contextlevel',
        'instanceid',
        'path',
        'depth',
    ];

    protected function init() {
        $this->instanceid = $this->data['instanceid'];
        $this->contextlevel = $this->data['contextlevel'];
        $this->outputprefix = $this->data['outputprefix'];

        if ($this->contextlevel == CONTEXT_MODULE && !empty((int)$this->instanceid)) {
            $data = get_course_and_cm_from_instance($this->instanceid, $this->data['mod']);
            $this->instanceid  = $data[1]->id;
        }
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::execute()
     */
    public function execute($step, $trigger, $event, $stepresults) {

        if (empty((int)$this->instanceid)) {
            $allfields = $this->get_datafields($event, $stepresults);
            if (!array_key_exists($this->instanceid, $allfields)) {
                throw new \invalid_parameter_exception("Specified instanceid field not present in the workflow data: "
                    . $this->instanceid);
            }

            $this->instanceid = $allfields[$this->instanceid];
        }

        $classname = \context_helper::get_class_for_level($this->contextlevel);
        $context = $classname::instance($this->instanceid, IGNORE_MISSING);

        if (!$context) {
            return [false, $stepresults];
        }

        foreach ($context as $key => $value) {
            $stepresults[$this->outputprefix . $key] = $value;
        }

        return [true, $stepresults];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::form_definition_extra()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        $mform->addElement('text', 'instanceid', get_string('step_lookup_context_instanceid', 'tool_trigger'));
        $mform->setType('instanceid', PARAM_ALPHANUMEXT);
        $mform->addRule('instanceid', get_string('required'), 'required');
        $mform->setDefault('instanceid', 'course_id');
        $mform->addHelpButton('instanceid', 'instanceid', 'tool_trigger');

        $leveloptions = [];
        $contextlevels = \context_helper::get_all_levels();
        foreach ($contextlevels as $level => $classname) {
            $leveloptions[$level] = \context_helper::get_level_name($level);
        }

        $mform->addElement('select', 'contextlevel', get_string('step_lookup_context_contextlevel', 'tool_trigger'), $leveloptions);
        $mform->setType('contextlevel', PARAM_INT);
        $mform->setDefault('contextlevel', 10);


        $modoptions = [];
        foreach (get_list_of_plugins('mod') as $mod) {
            $modoptions[$mod] = get_string('pluginname', 'mod_' . $mod);
        }

        $mform->addElement('select', 'mod', get_string('step_lookup_context_mod', 'tool_trigger'), $modoptions);
        $mform->setType('mod', PARAM_ALPHANUMEXT);
        $mform->disabledIf('mod', 'contextlevel', 'noteq', '');

        $mform->addElement('text', 'outputprefix', get_string('outputprefix', 'tool_trigger'));
        $mform->setType('outputprefix', PARAM_ALPHANUMEXT);
        $mform->addRule('outputprefix', get_string('required'), 'required');
        $mform->setDefault('outputprefix', 'context_');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_desc()
     */
    public static function get_step_desc() {
        return get_string('step_lookup_context_desc', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public static function get_step_name() {
        return get_string('step_lookup_context_name', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_privacyfields()
     */
    public static function get_privacyfields() {
        return ['context_lookup_step' => 'step_lookup_context:privacy:contextdata_desc'];
    }

    /**
     * Get a list of fields this step provides.
     *
     * @return array $stepfields The fields this step provides.
     */
    public static function get_fields() {
        return self::$stepfields;
    }
}
