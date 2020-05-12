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

    $settings = new admin_settingpage('tool_trigger_settings',
        get_string('pluginsettings', 'tool_trigger'));

    // Cleanup settings.
    $settings->add(new admin_setting_heading('tool_trigger/cleanupsettings',
        get_string('cleanupsettings', 'tool_trigger'),
        get_string('cleanupsettingsdesc', 'tool_trigger')));

    $settings->add(new admin_setting_configduration('tool_trigger/timetocleanup',
        get_string('timetocleanup', 'tool_trigger'),
        get_string('timetocleanup_help', 'tool_trigger'), DAYSECS, DAYSECS));

    // Learning mode settings.
    $settings->add(new admin_setting_heading('tool_trigger/learningsettings',
        get_string('learningsettings', 'tool_trigger'),
        get_string('learningsettingsdesc', 'tool_trigger')));

    $settings->add(new admin_setting_configcheckbox('tool_trigger/learning',
        get_string('learning', 'tool_trigger'),
        get_string('learning_help', 'tool_trigger'), 0));

    $settings->add(new admin_setting_heading('tool_trigger/historysettings',
        get_string('historysettings', 'tool_trigger'),
        get_string('historysettingsdesc', 'tool_trigger')));

    $settings->add(new admin_setting_configduration('tool_trigger/historyduration',
        get_string('historyduration', 'tool_trigger'),
        get_string('historydurationdesc', 'tool_trigger'), 1 * WEEKSECS, WEEKSECS));

    $workflowsettings = new admin_externalpage('tool_trigger_worfklowsettings',
        get_string('manage', 'tool_trigger'),
        new moodle_url('/admin/tool/trigger/manage.php'));

    $ADMIN->add('tool_trigger', $settings);
    $ADMIN->add('tool_trigger', $workflowsettings);

    $settings = null;
}
