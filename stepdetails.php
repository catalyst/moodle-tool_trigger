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
 * Trigger Historical step details.
 *
 * @package    tool_trigger
 * @copyright  Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
admin_externalpage_setup('tool_trigger_worfklowsettings', '', null, '', array('pagelayout' => 'report'));

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/admin/tool/trigger/stepdetails.php'));

// Get page information from DB.
$stepid = required_param('id', PARAM_INT);
$step = $DB->get_record('tool_trigger_run_hist', ['id' => $stepid]);
$eventdata = json_decode($DB->get_field('tool_trigger_workflow_hist', 'event', ['id' => $step->runid]));
$processor = new \tool_trigger\event_processor();
$event = $processor->restore_event($eventdata);
// Re-encode the JSON data to make it presentable.
$results = json_encode(json_decode($step->results), JSON_PRETTY_PRINT);

$pagecontent = '';
if (!$step) {
    $pagecontent .= $OUTPUT->heading(get_string('stepnotfound', 'tool_trigger'));
} else {
    // Output workflow id, run id, triggering event name + desc.
    // Then step id, step results.
    $pagecontent .= html_writer::tag('h4', get_string('workflowid', 'tool_trigger', $step->workflowid));
    $pagecontent .= html_writer::tag('h4', get_string('triggernumberembed', 'tool_trigger', $step->runid));
    $pagecontent .= html_writer::tag('h4', get_string('eventdescription', 'tool_trigger') . ': ');
    $pagecontent .= html_writer::tag('pre', var_export($event->get_name(), true));
    $pagecontent .= html_writer::tag('pre', var_export($event->get_description(), true)) . '<br>';
    $pagecontent .= html_writer::tag('h4', get_string('sttepidembed', 'tool_trigger', $step->id));
    $pagecontent .= html_writer::tag('h4', get_string('stepresults', 'tool_trigger'));
    $pagecontent .= html_writer::tag('pre', var_export($results, true));
}

$backbutton = new single_button(new moodle_url('/admin/tool/trigger/history.php',
    ['workflow' => $step->workflowid, 'run' => $step->runid]),
    get_string('back'));

// Manually inject navigation nodes based on the page params.
$workflowurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $step->workflowid]);
$PAGE->navbar->add(get_string('workflowviewhistory', 'tool_trigger'), $workflowurl);

$runurl = new moodle_url('/admin/tool/trigger/history.php',
    ['workflow' => $step->workflowid, 'run' => $step->runid]);
$PAGE->navbar->add(get_string('viewdetailedrun', 'tool_trigger'), $runurl);

$stepdetailsurl = new moodle_url('/admin/tool/trigger/stepdetails.php', ['id' => $stepid]);
$PAGE->navbar->add(get_string('viewstepinfo', 'tool_trigger'), $stepdetailsurl);

// Now output the page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('viewstepinfo', 'tool_trigger'));
echo '<br>';
echo $pagecontent;
echo $OUTPUT->render($backbutton);
echo $OUTPUT->footer();
