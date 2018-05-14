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

/**
 * Renders the top part of the "new workflow step" modal form. (The part with
 * the "Step type" and "Step" menus.
 *
 * @param array $args
 * @param context $args['context']
 * @return string
 */
function tool_trigger_output_fragment_new_base_form($args) {
    require_capability('tool/trigger:manageworkflows', $args['context']);

    $mform = new base_form();

    ob_start();
    $mform->display();
    $o = ob_get_contents();
    ob_end_clean();

    return $o;
}

/**
 * Renders the full form for a particular step class, for the "new workflow
 * step" modal form.
 *
 * @param array   $args
 * @param context $args['context']
 * @param string  $args['steptype'] The steptype to display in the form.
 * @param string  $args['stepclass'] The stepclass to display in the form.
 * @param string  $args['defaults'] Default values to populate the form with (JSON-encoded array)
 * @param string  $args['ajaxformdata'] Serialized form submission data, if the form
 * is being redisplayed after failed validation.
 * @return string
 */
function tool_trigger_output_fragment_new_step_form($args) {
    $context = $args['context'];
    require_capability('tool/trigger:manageworkflows', $context);

    $steptype = clean_param($args['steptype'], PARAM_ALPHA);

    // TODO: whitelist the stepclass values!
    $stepclass = clean_param($args['stepclass'], PARAM_RAW);
    $stepclassobj = new $stepclass();
    $stepname = $stepclassobj->get_step_name();

    $formclass = substr($stepclass, 0, -1 * strlen('step')) . 'form';

    $customdata = array(
        'type'      => $steptype,
        'stepclass' => $stepclass,
        'steptext'  => $stepname
    );

    $ajaxformdata = array();
    if (!empty($args['ajaxformdata'])) {
        // Don't need to clean/validate these, because formslib will do that.
        parse_str($args['ajaxformdata'], $ajaxformdata);
    }

    $mform = new $formclass(null, $customdata, 'post', '', null, true, $ajaxformdata);

    if (!empty($args['defaults'])) {
        // Don't need to clean/validate these, because formslib will do that.
        $mform->set_data(json_decode($args['defaults'], true));
    }

    if (!empty($ajaxformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }

    ob_start();
    $mform->display();
    $o = ob_get_contents();
    ob_end_clean();

    return $o;
}
