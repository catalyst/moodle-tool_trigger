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
 * Base step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\base;

defined('MOODLE_INTERNAL') || die;

/**
 * Base step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_step {

    protected $data = [];

    private $stepfields = array();

    public function __construct($jsondata = null) {
        if ($jsondata) {
            $this->data = json_decode($jsondata, true);
            $this->init();
        }
    }

    /**
     * Set up instance variables based on jsondata.
     */
    protected function init() {
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    abstract static public function get_step_name();

    /**
     * Returns the step description.
     *
     * @return string human readable step description.
     */
    abstract static public function get_step_desc();

    /**
     * @var string
     */
    const STEPTYPE_ACTION = 'actions';

    /**
     * @var string
     */
    const STEPTYPE_LOOKUP = 'lookups';

    /**
     * @var string
     */
    const STEPTYPE_FILTER = 'filters';

    /**
     * Returns which type of step it is. This must match be "actions", "lookups", or "filters",
     * and the way its' currently implemented, it must also match the classes's namespace and the directory it's in.
     *
     * @return string
     */
    abstract static public function get_step_type();

    /**
     * Returns a language string with the printable description of the type of step.
     *
     * @return string
     */
    abstract static public function get_step_type_desc();

    /**
     * Execute.
     *
     * @param \stdClass $step The `tool_trigger_steps` record for this step instance
     * @param \stdClass $trigger The `tool_trigger_queue` record for this execution
     * of the workflow.
     * @param \core\event\base $event (Read-only) The deserialized event object that triggered this execution
     * @param array $stepresults (Read-Write) Data aggregated from the return values of previous steps in
     * the workflow.
     * @return array<bool, array> Returns an array. The first element is a boolean
     * indicating whether or not the step was executed successfully; the second element should
     * be the $previousstepresult object, optionally mutated to provide data to
     * later steps.
     */
    abstract public function execute($step, $trigger, $event, $stepresults);

    /**
     * Instantiate a form for this step.
     *
     * If all you need to do is add fields to the form, then you should be able to get by
     * with this default implementation, and override the "form_definition()" method to your
     * step's class.
     *
     * If you want more control over other parts of the form, then override this method
     * to return a custom subclass of \base_form instead.
     *
     * @param mixed $customdata
     * @param mixed $ajaxformdata
     * @return \moodleform
     */
    public function make_form($customdata, $ajaxformdata) {
        return new base_form(null, $customdata, 'post', '', null, true, $ajaxformdata, $this);
    }

    /**
     * A callback to add fields to the step definition form, specific to each step class.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @param mixed $customdata
     */
    abstract public function form_definition_extra($form, $mform, $customdata);

    /**
     * For the privacy API, return a brief description of the types of data this step makes available for export to external
     * systems.
     *
     * This return value should be in the same format as the "privacyfields" used in
     * \core_privacy\local\metadata\collection::add_external_location_link()
     *
     * @return null|array
     */
    public static function get_privacyfields() {
        return null;
    }

    /**
     * For the privacy API, add any privacy metadata about how this step sends data to external sources, to other Moodle plugins,
     * or saves data.
     * @param \core_privacy\local\metadata\collection $collection
     * @param array $privacyfields A list of the fields of privacy data made available for export by tool_trigger and all
     * registered steps.
     * @return \core_privacy\local\metadata\collection
     */
    public static function add_privacy_metadata($collection, $privacyfields) {
        return $collection;
    }

    /**
     * Get a list of fields this step provides.
     * Needs to be overriden in each step class
     * otherwise exception is thrown.
     *
     * @throws \Exception
     */
    public static function get_fields() {
        throw new \Exception('Not implemented');

    }
}