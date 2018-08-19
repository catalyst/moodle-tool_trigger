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
 * This page lets admins manage workflows.
 *
 * @package    tool_trigger
 * @copyright  2014 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @copyright  2018 onwards Catalyst IT
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('tool/trigger:manageworkflows', $context);

$workflowid = required_param('workflowid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$confirm = optional_param('confirm', false, PARAM_BOOL);
$status = optional_param('status', 0, PARAM_BOOL);

if (!in_array($action, ['delete', 'copy'])) {
    print_error('invalidaction');
}

$workflow = \tool_trigger\workflow_manager::get_workflow($workflowid);
if (!$workflow) {
    print_error('invaliditemid');
}
$workflowname = $workflow->get_name($context);

// Set up the page.
$url = new moodle_url(
    '/admin/tool/trigger/manageworkflow.php',
    ['workflowid' => $workflowid]
);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($workflowname);
$PAGE->set_heading($workflowname);

require_sesskey();

$workflowmanager = new \tool_trigger\workflow_manager();

switch ($action) {
    case 'copy':
        $newworkflow = $workflowmanager->copy_workflow($workflow);
        redirect(
            new moodle_url('/admin/tool/trigger/manage.php'),
            get_string('workflowcopysuccess', 'tool_trigger'),
            \core\output\notification::NOTIFY_SUCCESS
        );
        break;
    case 'delete':
        if ($confirm) {
            $workflowmanager->delete_workflow($workflowid);
            redirect(
                new moodle_url('/admin/tool/trigger/manage.php'),
                get_string('workflowdeletesuccess', 'tool_trigger'),
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            echo $OUTPUT->header();

            $confirmurl = new moodle_url(
                $CFG->wwwroot. '/admin/tool/trigger/manageworkflow.php',
                [
                    'workflowid' => $workflowid,
                    'action' => 'delete',
                    'confirm' => true,
                    'sesskey' => sesskey()
                ]
            );
            $cancelurl = new moodle_url($CFG->wwwroot. '/admin/tool/trigger/manage.php');
            $strconfirm = get_string('workflowdeleteareyousure', 'tool_trigger', $workflow->get_name($context));

            echo $OUTPUT->confirm($strconfirm, $confirmurl, $cancelurl);
            echo $OUTPUT->footer();
            exit();
        }
        break;
    default:
}
