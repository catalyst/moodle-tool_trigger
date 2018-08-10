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
 * Trigger workflow settings.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

defined('MOODLE_INTERNAL') || die();

require_login();

admin_externalpage_setup('tool_trigger_settings', '', null, '', array('pagelayout' => 'report'));

$context = context_system::instance();

// Check for caps.
require_capability('tool/trigger:manageworkflows', $context);

// Load the javascript.
$PAGE->requires->js_call_amd('tool_trigger/import_workflow', 'init', array($context->id));

// Build the page output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('workflowoverview', 'tool_trigger'));

// Render the rule list.
$manageurl = new moodle_url('/admin/tool/trigger/manage.php');
$renderable = new \tool_trigger\output\manageworkflows\renderable('tooltrigger', $manageurl);
$renderer = $PAGE->get_renderer('tool_trigger', 'manageworkflows');
echo $renderer->render($renderable);

echo $OUTPUT->footer();
