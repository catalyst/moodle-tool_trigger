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

    $ADMIN->add('tools', new admin_category('toolstrigger',
        new lang_string('pluginname', 'tool_trigger')));

    $settings = new admin_settingpage('tooltriggersettings',
        new lang_string('settings'));

    $cleanupoptions = array(
        21600 => new lang_string('numhours', '', 6),
        43200 => new lang_string('numhours', '', 12),
        86400 => new lang_string('numhours', '', 24),
        172800 => new lang_string('numdays', '', 2),
        604800 => new lang_string('numdays', '', 7)
    );
    $settings->add(new admin_setting_configselect('tool_trigger/timetocleanup',
        new lang_string('timetocleanup', 'tool_trigger'),
        null, '86400', $cleanupoptions));

    // Add the category to the admin tree.
    $ADMIN->add('toolstrigger', $settings);

    $pluginsettings = new admin_externalpage('tool_trigger_settings',
        get_string('manageworkflow', 'tool_trigger'),
        new moodle_url('/admin/tool/trigger/index.php'));

    $ADMIN->add('toolstrigger', $pluginsettings);


    $settings = null;
}
