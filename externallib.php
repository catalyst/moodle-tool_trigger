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
 * Admin tool trigger Web Service
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");

/**
 * Admin tool trigger Web Service
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_trigger_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function step_by_type_parameters() {
        return new external_function_parameters(
            array(
                'steptype' => new external_value(PARAM_ALPHA, 'The type of step to get.'),
            )
        );
    }

    /**
     * Returns all steps matching provided type.
     *
     */
    public static function step_by_type($steptype) {
        global $USER;

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Capability checking.
        if (!has_capability('tool/trigger:manageworkflows', $context)) {
            throw new moodle_exception('cannot_access_api');
        }

        // Validate_parameters.
        $params = self::validate_parameters(
            self::step_by_type_parameters(),
            ['steptype' => $steptype]
        );

        // Execute API call.
        $wfmanager = new \tool_trigger\workflow_manager();
        $steps = $wfmanager->get_steps_by_type($params['steptype']);

        // Turn this into a nested array, so that the ordering can survive JSON-encoding.
        // (Because a PHP associative array becomes a JSON object, and according to the
        // specification, the order of the keys in a JS/JSON object is not meant to be
        // meaningful, while the order of the elements in a JS/JSON array is.)
        $output = [];
        foreach ($steps as $class => $namestr) {
            $output[] = [
                'class' => $class,
                'name' => $namestr
            ];
        }
        return $output;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function step_by_type_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'class' => new external_value(PARAM_TEXT, 'Event identifier'),
                    'name' => new external_value(PARAM_TEXT, 'Event Name'),
                )
                )
            );
    }

    /**
     * Describes the parameters for validate_form webservice.
     * @return external_function_parameters
     */
    public static function validate_form_parameters() {
        return new external_function_parameters(
            array(
                'stepclass' => new external_value(PARAM_RAW, 'The step class being validated'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array')
            )
        );
    }

    /**
     * Validate the form.
     *
     * @param string stepclass The step class being validated
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function validate_form($stepclass, $jsonformdata) {
        global $USER;

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::validate_form_parameters(),
            ['stepclass' => $stepclass, 'jsonformdata' => $jsonformdata]);

        // Validate the stepclass name.
        $workflowmanager = new \tool_trigger\workflow_manager();
        $step = $workflowmanager->validate_and_make_step($stepclass);

        $data = array();
        if (!empty($params['jsonformdata'])) {
            $serialiseddata = json_decode($params['jsonformdata']);
            parse_str($serialiseddata, $data);
        }

        // Create the form and trigger validation.
        $mform = $step->make_form(array(), $data);

        if (!$mform->is_validated()) {
            // Generate a warning.
            throw new moodle_exception('erroreditstep', 'tool_trigger');
        }
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     */
    public static function validate_form_returns() {
        return new external_value(PARAM_RAW, 'form errors');
    }


    /**
     * Describes the parameters for validate_form webservice.
     * @return external_function_parameters
     */
    public static function process_import_form_parameters() {
        return new external_function_parameters(
            array(
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array')
            )
            );
    }

    /**
     * Validate the form.
     *
     * @param string stepclass The step class being validated
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function process_import_form($jsonformdata) {
        global $USER;

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::process_import_form_parameters(),
            ['jsonformdata' => $jsonformdata]);

        $data = array();
        if (!empty($params['jsonformdata'])) {
            $serialiseddata = json_decode($params['jsonformdata']);
            parse_str($serialiseddata, $data);
        }

        // Create the form and trigger validation.
        $mform = new \tool_trigger\import_form(null, null, 'post', '', null, true, $data);

        $returnmsg = new \stdClass();

        if (!$mform->is_validated()) {
            // Generate a warning.
            $returnmsg->message = $mform->get_errors();
            $returnmsg->errorcode = 'errorimportworkflow';

        } else {  // Form is valid process.
            // Use submitted JSON file to create a new workflow.
            $filecontent = $mform->get_file_content('userfile');
            $workflowobj = \tool_trigger\workflow_process::import_prep($filecontent);

            $workflowprocess = new \tool_trigger\workflow_process($workflowobj);
            $result = $workflowprocess->processform();  // Add the workflow.

            if ($result) { // Sucessfully imported workflow.

                $cache = \cache::make('tool_trigger', 'eventsubscriptions');
                $cache->purge();

                $returnmsg->message = array('success' => get_string('workflowimported', 'tool_trigger'));
                $returnmsg->errorcode = 'success';

            } else { // Processing failure.
                // Throw a proper error, here as this shouldn't fail.
                throw new moodle_exception('errorimportworkflow', 'tool_trigger');
            }

        }

        return json_encode($returnmsg);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     */
    public static function process_import_form_returns() {
        return new external_value(PARAM_RAW, 'form errors');
    }
}