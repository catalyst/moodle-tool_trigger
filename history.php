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
 * Trigger workflow history.
 *
 * @package    tool_trigger
 * @copyright  Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_trigger_worfklowsettings', '', null, '', array('pagelayout' => 'report'));

$context = context_system::instance();

$PAGE->set_url(new moodle_url('/admin/tool/trigger/history.php'));

// Check for caps.
require_capability('tool/trigger:manageworkflows', $context);

$workflowid = required_param('workflow', PARAM_INT);
$runid = optional_param('run', null, PARAM_INT);
$action = optional_param('action', null, PARAM_TEXT);
if (!empty($action)) {
    $actionid = required_param('id', PARAM_INT);
}

if (!empty($action) && confirm_sesskey()) {
    // Here we will handle page actions for steps and workflows.
    $confirm = optional_param('confirm', false, PARAM_BOOL);

    switch ($action) {
        // Current config actions.
        case 'rerunworkflowcurr':
            // Rerun a workflow with current config.
            if ($confirm) {
                \tool_trigger\event_processor::execute_workflow_from_event_current($actionid);

            } else {
                $confirmurl = new moodle_url('/admin/tool/trigger/history.php');
                $confirmurl->params(['confirm' => 1, 'workflow' => $workflowid, 'action' => $action, 'id' => $actionid]);
                $cancelurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid]);
                $string = get_string('rerunworkflowconfirm', 'tool_trigger');
            }
            break;

        case 'rerunstepcurr':
            // Rerun a step with current config for step.
            if ($confirm) {
                \tool_trigger\event_processor::execute_current_step($actionid);
            } else {
                $confirmurl = new moodle_url('/admin/tool/trigger/history.php');
                $confirmurl->params(['confirm' => 1, 'workflow' => $workflowid,
                    'run' => $runid, 'action' => $action, 'id' => $actionid]);
                $cancelurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid, 'run' => $runid]);
                $string = get_string('rerunstepconfirm', 'tool_trigger');
            }
            break;


        case 'rerunnextcurr':
            // Rerun a step and next.
            if ($confirm) {
                \tool_trigger\event_processor::execute_step_and_continue_current($actionid);
            } else {
                $confirmurl = new moodle_url('/admin/tool/trigger/history.php');
                $confirmurl->params(['confirm' => 1, 'workflow' => $workflowid,
                    'run' => $runid, 'action' => $action, 'id' => $actionid]);
                $cancelurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid, 'run' => $runid]);
                $string = get_string('rerunstepandnextconfirm', 'tool_trigger');
            }
            break;

        case 'rerunfinishcurr':
            // Rerun a step and finish workflow.
            if ($confirm) {
                // Call execute_step_and_continue_historic with the complete run flag set.
                \tool_trigger\event_processor::execute_step_and_continue_current($actionid, true);
            } else {
                $confirmurl = new moodle_url('/admin/tool/trigger/history.php');
                $confirmurl->params(['confirm' => 1, 'workflow' => $workflowid,
                    'run' => $runid, 'action' => $action, 'id' => $actionid]);
                $cancelurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid, 'run' => $runid]);
                $string = get_string('rerunstepandfinishconfirm', 'tool_trigger');
            }
            break;

        case 'executenextcurr':
            if ($confirm) {
                \tool_trigger\event_processor::execute_next_step_current($actionid);
            } else {
                $confirmurl = new moodle_url('/admin/tool/trigger/history.php');
                $confirmurl->params(['confirm' => 1, 'workflow' => $workflowid,
                    'run' => $runid, 'action' => $action, 'id' => $actionid]);
                $cancelurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid, 'run' => $runid]);
                $string = get_string('executenextconfirm', 'tool_trigger');
            }
            break;

        // Historic actions.
        case 'rerunworkflowhist':
            // Rerun a workflow with current config.
            if ($confirm) {
                \tool_trigger\event_processor::execute_workflow_from_event_historic($actionid);

            } else {
                $confirmurl = new moodle_url('/admin/tool/trigger/history.php');
                $confirmurl->params(['confirm' => 1, 'workflow' => $workflowid, 'action' => $action, 'id' => $actionid]);
                $cancelurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid]);
                $string = get_string('rerunworkflowconfirm', 'tool_trigger');
            }
            break;

        case 'rerunstephist':
            // Rerun a step.
            if ($confirm) {
                \tool_trigger\event_processor::execute_historic_step($actionid);
            } else {
                $confirmurl = new moodle_url('/admin/tool/trigger/history.php');
                $confirmurl->params(['confirm' => 1, 'workflow' => $workflowid,
                    'run' => $runid, 'action' => $action, 'id' => $actionid]);
                $cancelurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid, 'run' => $runid]);
                $string = get_string('rerunstepconfirm', 'tool_trigger');
            }
            break;

        case 'rerunnexthist':
            // Rerun a step and next.
            if ($confirm) {
                \tool_trigger\event_processor::execute_step_and_continue_historic($actionid);
            } else {
                $confirmurl = new moodle_url('/admin/tool/trigger/history.php');
                $confirmurl->params(['confirm' => 1, 'workflow' => $workflowid,
                    'run' => $runid, 'action' => $action, 'id' => $actionid]);
                $cancelurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid, 'run' => $runid]);
                $string = get_string('rerunstepandnextconfirm', 'tool_trigger');
            }
            break;

        case 'rerunfinishhist':
            // Rerun a step and finish workflow.
            if ($confirm) {
                // Call execute_step_and_continue_historic with the complete run flag set.
                \tool_trigger\event_processor::execute_step_and_continue_historic($actionid, true);
            } else {
                $confirmurl = new moodle_url('/admin/tool/trigger/history.php');
                $confirmurl->params(['confirm' => 1, 'workflow' => $workflowid,
                    'run' => $runid, 'action' => $action, 'id' => $actionid]);
                $cancelurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid, 'run' => $runid]);
                $string = get_string('rerunstepandfinishconfirm', 'tool_trigger');
            }
            break;

        case 'executenexthist':
            if ($confirm) {
                \tool_trigger\event_processor::execute_next_step_historic($actionid);
            } else {
                $confirmurl = new moodle_url('/admin/tool/trigger/history.php');
                $confirmurl->params(['confirm' => 1, 'workflow' => $workflowid,
                    'run' => $runid, 'action' => $action, 'id' => $actionid]);
                $cancelurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid, 'run' => $runid]);
                $string = get_string('executenextconfirm', 'tool_trigger');
            }
            break;

        case 'rerunallcurr':
            if ($confirm) {
                \tool_trigger\event_processor::rerun_all_error_runs($workflowid);
            } else {
                $confirmurl = new moodle_url('/admin/tool/trigger/history.php');
                $confirmurl->params(['confirm' => 1, 'action' => $action, 'id' => $actionid,
                    'workflow' => $workflowid, 'id' => $actionid]);
                $cancelurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid, 'run' => $runid]);
                $string = get_string('rerunallcurrconfirm', 'tool_trigger');
            }
            break;

        case 'rerunallhist':
            if ($confirm) {
                \tool_trigger\event_processor::rerun_all_error_runs($workflowid, true);
            } else {
                $confirmurl = new moodle_url('/admin/tool/trigger/history.php');
                $confirmurl->params(['confirm' => 1, 'action' => $action, 'id' => $actionid,
                    'workflow' => $workflowid, 'id' => $actionid]);
                $cancelurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid, 'run' => $runid]);
                $string = get_string('rerunallhistconfirm', 'tool_trigger');
            }
            break;
    }

    // If not confirmed, output the confirm page, with the params set in the switch, then exit.
    if (!$confirm) {
        echo $OUTPUT->header();
        echo $OUTPUT->confirm($string, $confirmurl, $cancelurl);
        echo $OUTPUT->footer();
        exit();
    } else {
        // Redirect to a page with clean params to prevent accidental actions from reloads.
        redirect(new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid, 'run' => $runid]));
    }
}

// Manually inject navigation nodes based on the page params.
$navbarurl = new moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid]);
$PAGE->navbar->add(get_string('workflowviewhistory', 'tool_trigger'), $navbarurl);

if (!empty($runid)) {
    $navbarurl = new moodle_url('/admin/tool/trigger/history.php',
        ['workflow' => $workflowid, 'run' => $runid]);
    $PAGE->navbar->add(get_string('viewdetailedrun', 'tool_trigger'), $navbarurl);
}

// Build the page output if not performing an action and being redirected.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('workflowviewhistory', 'tool_trigger'));
$renderer = $PAGE->get_renderer('tool_trigger', 'workflowhistory');
$renderer->render_table($workflowid, $runid);
echo $OUTPUT->footer();
