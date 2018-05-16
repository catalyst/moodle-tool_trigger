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

use tool_trigger\workflow_manager;

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
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::__construct()
     */
    public function __construct($jsondata = null) {
        parent::__construct($jsondata);
        if ($jsondata) {
            $this->useridfield = $this->data['useridfield'];
            $this->outputprefix = $this->data['outputprefix'];
        }
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::execute()
     */
    public function execute($step, $trigger, $event, $stepresults) {

        $allfields = workflow_manager::get_datafields($event, $stepresults);

        if (!array_key_exists($this->useridfield, $allfields)) {
            throw new \invalid_parameter_exception("Specified userid field not present in the workflow data: "
                    . $this->useridfield);
        }

        $user = \core_user::get_user($allfields[$this->useridfield]);
        if (!$user) {
            throw new \invalid_response_exception("Specified userid not found in the database.");
        }

        $userdata = user_get_user_details($user);
        foreach ($userdata as $key => $value) {
            if (is_scalar($value)) {
                $stepresults[$this->outputprefix . $key] = $value;
            }
        }
        return [true, $stepresults];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::form_definition_extra()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        // TODO: lang string!
        $mform->addElement('text', 'useridfield', "User id data field");
        $mform->setType('useridfield', PARAM_ALPHANUMEXT);
        $mform->addRule('useridfield', get_string('required'), 'required');
        $mform->setDefault('useridfield', 'userid');

        // TODO: lang string!
        $mform->addElement('text', 'outputprefix', "Prefix for added fields");
        $mform->setType('outputprefix', PARAM_ALPHANUMEXT);
        $mform->addRule('outputprefix', get_string('required'), 'required');
        $mform->setDefault('outputprefix', 'user_');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_desc()
     */
    public static function get_step_desc() {
        // TODO: lang string!
        return "This step looks up data about a user.";
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public static function get_step_name() {
        // TODO: lang string!
        return "User lookup";
    }


}
