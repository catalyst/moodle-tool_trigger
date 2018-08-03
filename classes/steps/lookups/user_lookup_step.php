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
 * A lookup step that takes a user's ID and adds standard data about the
 * user.
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT, 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_lookup_step extends base_lookup_step {

    use \tool_trigger\helper\datafield_manager;

    /**
     * The data field to get the user id from.
     * @var string
     */
    private $useridfield = null;

    /**
     * The prefix to put before the new fields added to the workflow data.
     *
     * @var string
     */
    private $outputprefix = null;

    /**
     * The fields suplied by this step.
     * Pretty much everything except "password" and "secret".
     *
     * @var array
     */
    private static $stepfields = array(
            'id',
            'auth',
            'confirmed',
            'policyagreed',
            'deleted',
            'suspended',
            'mnethostid',
            'username',
            'idnumber',
            'firstname',
            'lastname',
            'email',
            'emailstop',
            'icq',
            'skype',
            'yahoo',
            'aim',
            'msn',
            'phone1',
            'phone2',
            'institution',
            'department',
            'address',
            'city',
            'country',
            'lang',
            'calendartype',
            'theme',
            'timezone',
            'firstaccess',
            'lastaccess',
            'lastlogin',
            'currentlogin',
            'lastip',
            'picture',
            'url',
            'description',
            'descriptionformat',
            'mailformat',
            'maildigest',
            'maildisplay',
            'autosubscribe',
            'trackforums',
            'timecreated',
            'timemodified',
            'trustbitmask',
            'imagealt',
            'lastnamephonetic',
            'firstnamephonetic',
            'middlename',
            'alternatename');

    /**
     * Whether to halt execution of the workflow, if the user has been marked "deleted".
     *
     * @var bool
     */
    private $nodeleted;

    protected function init() {
        $this->useridfield = $this->data['useridfield'];
        $this->outputprefix = $this->data['outputprefix'];

        // This is a new field, so it's possible that stored workflow steps may not have a setting for it.
        if (array_key_exists('nodeleted', $this->data)) {
            $this->nodeleted = (bool) $this->data['nodeleted'];
        } else {
            $this->nodeleted = true;
        }
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::execute()
     */
    public function execute($step, $trigger, $event, $stepresults) {
        $datafields = $this->get_datafields($event, $stepresults);

        if (!array_key_exists($this->useridfield, $datafields)) {
            throw new \invalid_parameter_exception("Specified userid field not present in the workflow data: "
                    . $this->useridfield);
        }

        $userfields = implode(',', $this->get_fields());
        $userdata = \core_user::get_user($datafields[$this->useridfield], $userfields);

        // Users are not typically deleted from the database on deletion; they're just flagged as "deleted".
        // So if no user with that ID is found, then throw an exception.
        if (!$userdata) {
            throw new \invalid_parameter_exception('User not found with id ' . $datafields[$this->useridfield]);
        }

        // Have we been asked to exclude deleted users?
        if ($this->nodeleted && $userdata->deleted) {
            return [false, $stepresults];
        }

        foreach ($userdata as $key => $value) {
            if (is_scalar($value)) {
                $stepresults[$this->outputprefix . $key] = $value;
            }
        }

        // Also fetch the user's fullname.
        $stepresults[$this->outputprefix . 'fullname'] = fullname($userdata);

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
        $mform->setDefault('outputprefix', 'user_');

        $mform->addElement('selectyesno', 'nodeleted', get_string('step_lookup_user_nodeleted', 'tool_trigger'));
        $mform->setDefault('nodeleted', 1);
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_desc()
     */
    public static function get_step_desc() {
        return get_string('step_lookup_user_desc', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public static function get_step_name() {
        return get_string('step_lookup_user_name', 'tool_trigger');
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
