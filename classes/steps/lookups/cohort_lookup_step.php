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
 * A lookup step that takes a user's ID and returns a string of
 * all the cohorts that the user is currently assigned to
 *
 * @package    tool_trigger
 * @author     Paul Damiani <Paul Damiani@catalyst-au.net>
 * @copyright  Catalyst IT, 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort_lookup_step extends base_lookup_step
{

    use \tool_trigger\helper\datafield_manager;

    /**
     * The data field to get the cohort id from.
     * @var string
     */
    private $cohortidfield = null;

    /**
     * The prefix to put before the new fields added to the workflow data.
     *
     * @var string
     */
    private $outputprefix = null;

    /**
     * The fields supplied by this step.
     * A string containing all the cohorts a user is assigned to.
     *
     * @var array
     */
    private static $stepfields = array(
        'id_[cohortid]'
    );

    protected function init() {
        $this->useridfield = $this->data['useridfield'];
        $this->outputprefix = $this->data['outputprefix'];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::execute()
     */
    public function execute($step, $trigger, $event, $stepresults) {
        global $DB;
        $datafields = $this->get_datafields($event, $stepresults);

        if (!array_key_exists($this->useridfield, $datafields)) {
            throw new \invalid_parameter_exception("Specified userid field not present in the workflow data: "
                . $this->useridfield);
        }

        $sql = 'SELECT cohortid FROM {cohort_members} WHERE userid = :userid';
        $params['userid'] = $datafields[$this->useridfield];
        $cohortdata = $DB->get_records_sql($sql, $params);

        foreach ($cohortdata as $key => $value) {
            $stepresults[$this->outputprefix . 'id_' . $key] = $key;
        }

        return [true, $stepresults];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::form_definition_extra()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        $mform->addElement('text', 'useridfield', get_string('step_lookup_user_useridfield', 'tool_trigger'));
        $mform->setType('useridfield', PARAM_ALPHANUMEXT);
        $mform->addRule('useridfield', get_string('required'), 'required');
        $mform->setDefault('useridfield', 'userid');

        $mform->addElement('text', 'outputprefix', get_string('outputprefix', 'tool_trigger'));
        $mform->setType('outputprefix', PARAM_ALPHANUMEXT);
        $mform->addRule('outputprefix', get_string('required'), 'required');
        $mform->setDefault('outputprefix', 'cohort_');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_desc()
     */
    public static function get_step_desc() {
        return get_string('step_lookup_cohort_desc', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public static function get_step_name() {
        return get_string('step_lookup_cohort_name', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_privacyfields()
     */
    public static function get_privacyfields() {
        return ['user_lookup_step' => 'step_lookup_user:privacy:userdata_desc'];
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
