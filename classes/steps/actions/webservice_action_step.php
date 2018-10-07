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

require_once($CFG->dirroot . "/webservice/lib.php");

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
     *
     * @param unknown $elements
     * @param unknown $mform
     */
    private function create_webservice_form($elements, $mform) {
        //  Iterate through list of elements and create for entries for each.
        //  if value or iterator is array instead of external_value Object,
        //  then this is a special case like custom profile fields or preferences.
        //  In this case we will start by making the form field a text area, that
        //  users can enter key value pairs.

        foreach ($elements as $elementname => $elementdata) {
            //  Allownull: null = NULL_NOT_ALLOWED, 1 = NULL_ALLOWED.
            //  Required: 0 = VALUE_DEFAULT, 1 = VALUE_REQUIRED, 2 = VALUE_OPTIONAL.

            // We add an underscore to deal with fields that are Javascript reserved words.
            $fieldname = '_' . $elementname;

            if ($elementdata instanceof \external_value) {

                $mform->addElement('text', $fieldname, ucfirst($elementname));
                $mform->setType($fieldname, $elementdata->type);

                if (($elementdata->required == 1 && $elementdata->allownull == 1) || $elementdata->allownull == 0) {
                    $mform->addRule($fieldname, null, 'required', null, 'server');
                }
                // Set default value by using a passed parameter
                $mform->setDefault($fieldname, $elementdata->default);

            } else {
                // We have an array of fields.
                $attributes = array('cols' => '50', 'rows' => '5');
                $mform->addElement('textarea', $fieldname, ucfirst($elementname), $attributes);
                $mform->setType($fieldname, PARAM_RAW_TRIMMED);
            }

        }
    }

    /**
     *
     * @param stdClass $params a part of parameter/return description
     * @param array $resultarray
     * @return array
     */
    private function get_webservice_form_elements($params, $resultarray=array()) {
        // Description object is a list.
        if ($params instanceof \external_multiple_structure) {
            return $this->get_webservice_form_elements($params->content, $resultarray);
        } else if ($params instanceof \external_single_structure) {
            // Description object is an object.
            foreach ($params->keys as $attributname => $attribut) {
                $resultarray[$attributname]= $this->get_webservice_form_elements($params->keys[$attributname]);
            }
            return $resultarray;
        } else { // Description object is a primary type.
            return $params;
        }
    }

    /**
     * Given a Moodle webservice method return a Moodle form that matches the methods parameters.
     *
     * @param object $functionname The name of the webservice method to get the form for.
     * @param \moodleform $mform Moodle form.
     */
    private function get_webservice_form($functionid, $mform) {
        global $DB;

        $function = $DB->get_record('external_functions', array('id' => $functionid), '*', MUST_EXIST);
        $functioninfo = \external_api::external_function_info($function);

        // Iterate thorugh the function info and get a formated object with requried data.
        foreach ($functioninfo->parameters_desc->keys as $paramname => $paramdesc) {
            $elements = $this->get_webservice_form_elements($paramdesc);
        }

        //  In the case where there is only one element returned (which means only one form field),
        //  Make the array of elements explicitly.
        if ($elements instanceof \external_value){
            reset($functioninfo->parameters_desc->keys);
            $attributname = key($functioninfo->parameters_desc->keys);
            $elements = array($attributname => $elements);
        }

        // Use the required data object to make the form for the webservice
        $this->create_webservice_form($elements, $mform);
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        global $USER, $DB, $CFG;

        // User.

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

        $mform->addElement('searchableselector', 'webservice_function', get_string('name'), $functions);
        $mform->addRule('webservice_function', get_string('required'), 'required', null, 'client');

        if ($customdata['functionid'] != 0 ) {
            $this->get_webservice_form($customdata['functionid'], $mform);
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