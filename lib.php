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
 * Global functions for tool_trigger plugin.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_trigger\steps\base\base_form;

defined('MOODLE_INTERNAL') || die;

function tool_trigger_output_fragment_new_base_form($args) {
    $args = (object) $args;
    $context = $args->context;
    $o = '';

    require_capability('moodle/course:managegroups', $context);

    $mform = new base_form();

    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();

    return $o;
}

function tool_trigger_output_fragment_new_step_form($args) {
    $args = (object) $args;
    $context = $args->context;
    $formdata = json_decode($args->jsonformdata);
    $o = '';

    require_capability('moodle/course:managegroups', $context);
    $customdata = array('type' => $formdata->steptype, 'stepclass' => $formdata->stepval, 'steptext' => $formdata->steptext);
    $formclass = substr($formdata->stepval, 0, (strlen($formdata->stepval) - 4)) . 'form';

    $data = array();
    if (!empty($formdata->data)) {
        parse_str($formdata->data, $data);
    }

    $mform = new $formclass(null, $customdata, 'post', '', null, true, $data);

    if (!empty($data)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }

    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();

    return $o;
}
