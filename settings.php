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
 * Plugin administration pages are defined here.
 *
 * @package     tool_trigger
 * @category    admin
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('tools', new admin_category('tool_trigger', get_string('pluginname', 'tool_trigger')));

    $pluginsettings = new admin_externalpage('tool_trigger_settings',
        get_string('pluginsettings', 'tool_trigger'),
        new moodle_url('/admin/tool/trigger/index.php'));

    $workflowsettings = new admin_externalpage('tool_trigger_worfklowsettings',
        get_string('manage', 'tool_trigger'),
        new moodle_url('/admin/tool/trigger/manage.php'));

    $ADMIN->add('tool_trigger', $pluginsettings);
    $ADMIN->add('tool_trigger', $workflowsettings);

    $settings = null;
}
