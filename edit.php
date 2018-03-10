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
$PAGE->set_title(get_string('addworkflow', 'tool_trigger'));
$PAGE->set_heading(get_string('addworkflow', 'tool_trigger'));

// Load the javascript.
$PAGE->requires->js_call_amd('tool_trigger/workflow', 'init');

// Get plugin list.
$pluginlist = \tool_monitor\eventlist::get_plugin_list();

// Modify the list to add the choosers.
$pluginlist = array_merge(array('' => get_string('choosedots')), $pluginlist);

// Get data ready for mform.
$form = new \tool_trigger\edit_form(null, array('pluginlist' => $pluginlist));

// Build the page output.
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
