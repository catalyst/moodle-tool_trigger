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
 * A lookup step that takes a course's ID and adds standard data about the
 * course.
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT, 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_lookup_step extends base_lookup_step {

    use \tool_trigger\helper\datafield_manager;

    /**
     * The data field to get the course id from.
     * @var string
     */
    private $courseidfield = null;

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
    private static $stepfields = array(
        'id',
        'category',
        'sortorder',
        'fullname',
        'shortname',
        'idnumber',
        'summary',
        'summaryformat',
        'format',
        'showgrades',
        'newsitems',
        'startdate',
        'enddate',
        'marker',
        'maxbytes',
        'legacyfiles',
        'showreports',
        'visible',
        'visibleold',
        'groupmode',
        'groupmodeforce',
        'defaultgroupingid',
        'lang',
        'calendartype',
        'theme',
        'timecreated',
        'timemodified',
        'requested',
        'enablecompletion',
        'completionnotify',
        'cacherev'
    );

    protected function init() {
        $this->courseidfield = $this->data['courseidfield'];
        $this->outputprefix = $this->data['outputprefix'];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::execute()
     */
    public function execute($step, $trigger, $event, $stepresults) {
        global $DB;

        $allfields = $this->get_datafields($event, $stepresults);

        if (!array_key_exists($this->courseidfield, $allfields)) {
            throw new \invalid_parameter_exception("Specified courseid field not present in the workflow data: "
                    . $this->courseidfield);
        }

        $coursedata = $DB->get_record('course', ['id' => $allfields[$this->courseidfield]]);

        if (!$coursedata) {
            // If the course has been deleted, there's no point re-running the task.
            return [false, $stepresults];
        }

        foreach ($coursedata as $key => $value) {
            $stepresults[$this->outputprefix . $key] = $value;
        }
        return [true, $stepresults];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::form_definition_extra()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        $mform->addElement('text', 'courseidfield', get_string('step_lookup_course_courseidfield', 'tool_trigger'));
        $mform->setType('courseidfield', PARAM_ALPHANUMEXT);
        $mform->addRule('courseidfield', get_string('required'), 'required');
        $mform->setDefault('courseidfield', 'courseid');

        $mform->addElement('text', 'outputprefix', get_string('outputprefix', 'tool_trigger'));
        $mform->setType('outputprefix', PARAM_ALPHANUMEXT);
        $mform->addRule('outputprefix', get_string('required'), 'required');
        $mform->setDefault('outputprefix', 'course_');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_desc()
     */
    public static function get_step_desc() {
        return get_string('step_lookup_course_desc', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public static function get_step_name() {
        return get_string('step_lookup_course_name', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_privacyfields()
     */
    public static function get_privacyfields() {
        return ['course_lookup_step' => 'step_lookup_course:privacy:coursedata_desc'];
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
