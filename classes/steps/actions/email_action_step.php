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
 * email action step class.
 *
 * @package    tool_trigger
 * @copyright  Catalyst IT
 * @author     Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\actions;

defined('MOODLE_INTERNAL') || die;

/**
 * email action step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class email_action_step extends base_action_step {

    use \tool_trigger\helper\datafield_manager;

    /**
     * @var string
     */
    protected $emailto;

    /**
     * @var string
     */
    protected $emailsubject;

    /**
     * @var string
     */
    protected $emailcontent;

    /**
     * @var string
     */
    protected $messageplain;

    /**
     * The fields suplied by this step.
     *
     * @var array
     */
    private static $stepfields = array(
            'email_action_messageid',
            'email_action_courseid',
            'email_action_subject',
            'email_action_fullmessage',
            'email_action_fullmessageformat',
            'email_action_fullmessagehtml',
            'email_action_smallmessage',
            'email_action_userfrom_id',
            'email_action_userfrom_email',
            'email_action_userto_id',
            'email_action_userto_email'
    );

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::__construct()
     */
    protected function init() {
        $this->emailto = $this->data['emailto'];
        $this->emailsubject = $this->data['emailsubject'];
        $this->emailcontent = $this->data['emailcontent'];
        $this->messageplain = $this->data['emailcontent'];
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_name() {
        return get_string('emailactionstepname', 'tool_trigger');
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_desc() {
        return get_string('emailactionstepdesc', 'tool_trigger');
    }

    /**
     * @param $step
     * @param $trigger
     * @param $event
     * @param $stepresults - result of previousstep to include in processing this step.
     * @return array if execution was succesful and the response from the execution.
     */
    public function execute($step, $trigger, $event, $stepresults) {
        global $DB;

        $this->update_datafields($event, $stepresults);
        $emailto = $this->render_datafields($this->emailto);
        $emailsubject = $this->render_datafields($this->emailsubject);
        $emailcontent = $this->render_datafields($this->emailcontent);
        $messageplain = $this->render_datafields($this->emailcontent);

        // Check we have a valid email address.
        if ($emailto == clean_param($emailto, PARAM_EMAIL)) {

            // Check if user exists and use user record.
            $user = $DB->get_record('user', array('email' => $emailto, 'deleted' => 0));

            // If user not found, use noreply as a base.
            if (empty($user)) {
                $user = \core_user::get_noreply_user();
                $user->firstname = $emailto;
                $user->email = $emailto;
                $user->maildisplay = 1;
                $user->emailstop = 0;
            }
            $from = \core_user::get_support_user();

            $eventdata = new \core\message\message();
            $eventdata->courseid = $event->courseid;
            $eventdata->userfrom = $from;
            $eventdata->userto = $user;
            $eventdata->subject = $emailsubject;
            $eventdata->fullmessage = $messageplain;
            $eventdata->fullmessageformat = FORMAT_HTML;
            $eventdata->fullmessagehtml = $emailcontent;
            $eventdata->smallmessage = $emailsubject;
            // Required for messaging framework.
            $eventdata->name = 'tool_trigger';
            $eventdata->component = 'tool_trigger';

            $msgid = message_send($eventdata);
            if (!$msgid) {
                throw new \invalid_response_exception('Tried but failed to send message.');
            }

            $stepresults['email_action_messageid'] = $msgid;
            foreach ((array)$eventdata as $key => $value) {
                if (is_scalar($value)) {
                    $stepresults['email_action_' . $key] = $value;
                }
            }
            $stepresults['email_action_userfrom_id'] = $eventdata->userfrom->id;
            $stepresults['email_action_userfrom_email'] = $eventdata->userfrom->email;
            $stepresults['email_action_userto_id'] = $eventdata->userto->id;
            $stepresults['email_action_userto_email'] = $eventdata->userto->email;
        } else {
            $stepresults['email_action_messageid'] = false;
        }

        return array(true, $stepresults);
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {

        // To!
        $mform->addElement('text', 'emailto', get_string ('emailto', 'tool_trigger'));
        $mform->setType('emailto', PARAM_RAW_TRIMMED);
        $mform->addRule('emailto', get_string('required'), 'required');
        $mform->addHelpButton('emailto', 'emailto', 'tool_trigger');

        // Subject!
        $mform->addElement('text', 'emailsubject', get_string ('emailsubject', 'tool_trigger'));
        $mform->setType('emailsubject', PARAM_RAW_TRIMMED);
        $mform->addRule('emailsubject', get_string('required'), 'required');
        $mform->addHelpButton('emailsubject', 'emailsubject', 'tool_trigger');

        // Params!
        $attributes = array('cols' => '50', 'rows' => '5');
        $mform->addElement('textarea', 'emailcontent', get_string ('emailcontent', 'tool_trigger'), $attributes);
        $mform->setType('emailcontent', PARAM_RAW_TRIMMED);
        $mform->addRule('emailcontent', get_string('required'), 'required');
        $mform->addHelpButton('emailcontent', 'emailcontent', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_privacy_metadata()
     */
    public static function add_privacy_metadata($collection, $privacyfields) {
        return $collection->add_external_location_link(
            'email_action_step',
            $privacyfields,
            'step_action_email:privacy:desc'
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