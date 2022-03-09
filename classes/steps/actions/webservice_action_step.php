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

namespace tool_trigger\steps\actions;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Webservice action step class.
 *
 * @package    tool_trigger
 * @author     Kevin Pham <kevinpham@catalyst-au.net>
 * @copyright  Catalyst IT, 2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservice_action_step extends base_action_step {

    use \tool_trigger\helper\datafield_manager;

    /** @var string $functionname Name of the function to be called */
    protected $functionname;

    /** @var int $username The user in which this action will be actioned in the context of */
    protected $username;

    /** @var int $params parameters that will be used in the corresponding web service function call */
    protected $params;

    /**
     * The fields supplied by this step.
     *
     * @var array
     */
    private static $stepfields = [
        'data',
        'error',
        'exception',
    ];

    protected function init() {
        $this->functionname = $this->data['functionname'];
        $this->username = $this->data['username'];
        $this->params = $this->data['params'];
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    public static function get_step_name() {
        return get_string('webserviceactionstepname', 'tool_trigger');
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    public static function get_step_desc() {
        return get_string('webserviceactionstepdesc', 'tool_trigger');
    }

    /**
     * Returns the user that should execute the webservice function
     *
     * @uses webservice_action_step::$username
     * @return \stdClass user object
     */
    private function get_user() {
        global $DB;
        $username = $this->render_datafields($this->username);

        if (empty($username)) {
            // If {username} is not set, then default it to the main admin user.
            $user = get_admin();
        } else {
            // Assume the role of the provided user given their {username}.
            $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        }

        // This bypasses the sesskey check for the external api call.
        $user->ignoresesskey = true;

        return $user;
    }

    /**
     * Prepare and run the function set in the config and return the results.
     *
     * @uses webservice_action_step::$functionname
     * @uses webservice_action_step::$params
     * @return array results of the function run
     */
    private function run_function() {
        // Passing any data from previous steps through by applying template magic.
        $functionname = $this->render_datafields($this->functionname);
        $params = $this->render_datafields($this->params);

        // Execute the provided function name passing with the given parameters.
        $response = \external_api::call_external_function($functionname, json_decode($params, true));
        return $response;
    }

    /**
     * Runs the configured step.
     *
     * @param $trigger
     * @param $event
     * @param $stepresults - result of previousstep to include in processing this step.
     * @return array if execution was succesful and the response from the execution.
     */
    public function execute($step, $trigger, $event, $stepresults) {
        global $USER;

        $this->update_datafields($event, $stepresults);

        // Store the previous user, setting it back once the step is finished.
        $previoususer = $USER;

        // Set the configured user as the one who will run the function.
        $user = $this->get_user();
        \core\session\manager::set_user($user);
        set_login_session_preferences();

        // Run the function and parse the response to a step result.
        $response = $this->run_function();
        if ($response['error']) {
            return [false, $response['exception']];
        }

        // Restore the previous user to avoid any side-effects occuring in later steps / code.
        \core\session\manager::set_user($previoususer);
        set_login_session_preferences();

        // Return the function call response as is. The shape is already normalised.
        return [true, $response];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        // URL.
        $attributes = ['size' => '50', 'placeholder' => 'my_function_name'];
        $mform->addElement('text', 'functionname', get_string('webserviceactionfunctionname', 'tool_trigger'), $attributes);
        $mform->setType('functionname', PARAM_RAW_TRIMMED);
        $mform->addRule('functionname', get_string('required'), 'required');
        $mform->addHelpButton('functionname', 'webserviceactionfunctionname', 'tool_trigger');

        // Who.
        $attributes = ['placeholder' => 'username'];
        $mform->addElement('text', 'username', get_string('webserviceactionusername', 'tool_trigger'), $attributes);
        $mform->setType('username', PARAM_ALPHANUMEXT);
        $mform->addRule('username', get_string('required'), 'required');
        $mform->setDefault('username', 'admin'); // Set the default to admin?
        $mform->addHelpButton('username', 'webserviceactionusername', 'tool_trigger');

        // Params.
        $attributes = ['cols' => '50', 'rows' => '5'];
        $mform->addElement('textarea', 'params', get_string('webserviceactionparams', 'tool_trigger'), $attributes);
        $mform->setType('params', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('params', 'webserviceactionparams', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_privacy_metadata()
     */
    public static function add_privacy_metadata($collection, $privacyfields) {
        return $collection->add_external_location_link(
            'webservice_action_step',
            $privacyfields,
            'step_action_webservice:privacy:desc'
        );
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
