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
 * Azure Search search engine settings.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

defined('MOODLE_INTERNAL') || die();

$workflowid = optional_param('workflowid', 0, PARAM_INT);

require_login();

$url = new moodle_url("/admin/tool/trigger/edit.php", array('workflowid' => $workflowid));
$context = context_system::instance();

// Check for caps.
require_capability('tool/trigger:manageworkflows', $context);

// Set up the page.
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
if ($workflowid) {
    $pagetitlestr = get_string('editworkflow', 'tool_trigger');
} else {
    $pagetitlestr = get_string('addworkflow', 'tool_trigger');
}
$PAGE->set_title($pagetitlestr);
$PAGE->set_heading($pagetitlestr);

// Load the javascript.
$PAGE->requires->js_call_amd('tool_trigger/step_select', 'init', array($context->id));

$eventlist = \tool_monitor\eventlist::get_all_eventlist();

// Group the events by plugin.
$pluginlist = \tool_monitor\eventlist::get_plugin_list($eventlist);
$plugineventlist = [];
foreach ($pluginlist as $plugin => $pluginname) {
    foreach ($eventlist[$plugin] as $event => $eventname) {
        // Filter out events which cannot be triggered for some reason.
        if (!$event::is_deprecated()) {
            $plugineventlist[$event] = "${pluginname}: ${eventname}";
        }
    }
}

// Get data ready for mform.
$mform = new \tool_trigger\edit_form(
    null,
    ['plugineventlist' => $plugineventlist]
);

if ($mform->is_cancelled()) {
    // Handle form cancel operation.
    // Redirect back to workflow page.
    redirect(new moodle_url('/admin/tool/trigger/manage.php'));

} else if ($mdata = $mform->get_data()) {
    // Process validated data.
    $workflowprocess = new \tool_trigger\workflow_process($mdata);
    $result = $workflowprocess->processform();

    $cache = \cache::make('tool_trigger', 'eventsubscriptions');
    $cache->purge();

    // Redirect back to workflow page and show success or failure.
    if ($result) {
        redirect(new moodle_url('/admin/tool/trigger/manage.php'), get_string('changessaved'));
    } else {
        redirect(new moodle_url('/admin/tool/trigger/manage.php'), get_tring('errorsavingworkflow'));
    }

} else {
    // This branch is executed if the form is submitted but the data doesn't validate,
    // or on the first display of the form.

    if ($workflowid) {
        $workflowprocess = new \tool_trigger\workflow_process(null);
        $mform->set_data($workflowprocess->to_form_defaults($workflowid));
    }

    // Build the page output.
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();

}
