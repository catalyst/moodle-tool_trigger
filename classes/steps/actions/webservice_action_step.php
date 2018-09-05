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
 * HTTP Post action step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\actions;

defined('MOODLE_INTERNAL') || die;

/**
 * Webservice action step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservice_action_step extends base_action_step {

    use \tool_trigger\helper\datafield_manager;

    /**
     * The fields suplied by this step.
     *
     * @var array
     */
    private static $stepfields = array(

    );

    protected function init() {

    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_name() {
        return get_string('webserviceactionstepname', 'tool_trigger');
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_desc() {
        return get_string('webserviceactionstepdesc', 'tool_trigger');
    }

    /**
     * @param $step
     * @param $trigger
     * @param $event
     * @param $stepresults - result of previousstep to include in processing this step.
     * @return array if execution was succesful and the response from the execution.
     */
    public function execute($step, $trigger, $event, $stepresults) {


        return array(true, $stepresults);
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        global $USER, $DB, $CFG;
        require_once($CFG->dirroot . "/webservice/lib.php");

        // Webservice.
        $webservicemanager = new \webservice();
        $functions = $DB->get_records('external_functions', null, 'name ASC');

        // We add the descriptions to the functions.
        foreach ($functions as $functionid => $functionname) {
            // Retrieve full function information.
            $function = \external_api::external_function_info($functionname);
            if (empty($function->deprecated)) {
                $functions[$functionid] = $function->name;
            } else {
                // Exclude the deprecated ones.
                unset($functions[$functionid]);
            }
        }

        $mform->addElement('searchableselector', 'fids', get_string('name'), $functions);
        $mform->addRule('fids', get_string('required'), 'required', null, 'client');

        // User.
        if (empty($data->nouserselection)) {

            //check if the number of user is reasonable to be displayed in a select box
            $usertotal = $DB->count_records('user',
                array('deleted' => 0, 'suspended' => 0, 'confirmed' => 1));

            if ($usertotal < 500) {
                list($sort, $params) = users_order_by_sql('u');
                // User searchable selector - return users who are confirmed, not deleted, not suspended and not a guest.
                $sql = 'SELECT u.id, ' . get_all_user_name_fields(true, 'u') . '
                        FROM {user} u
                        WHERE u.deleted = 0
                        AND u.confirmed = 1
                        AND u.suspended = 0
                        AND u.id != :siteguestid
                        ORDER BY ' . $sort;
                $params['siteguestid'] = $CFG->siteguest;
                $users = $DB->get_records_sql($sql, $params);
                $options = array();
                foreach ($users as $userid => $user) {
                    $options[$userid] = fullname($user);
                }
                $mform->addElement('searchableselector', 'user', get_string('user'), $options);
                $mform->setType('user', PARAM_INT);
            } else {
                //simple text box for username or user id (if two username exists, a form error is displayed)
                $mform->addElement('text', 'user', get_string('usernameorid', 'webservice'));
                $mform->setType('user', PARAM_RAW_TRIMMED);
            }
            $mform->addRule('user', get_string('required'), 'required', null, 'client');
        }

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