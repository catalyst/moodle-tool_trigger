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
 * upgrade for trigger plugin.
 *
 * @package     tool_trigger
 * @copyright   Catalyst IT
 * @author      Dan Marsden <dan@danmarsden.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_tool_trigger_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018050700) {

        // Define table tool_trigger_events to be created.
        $table = new xmldb_table('tool_trigger_events');

        // Adding fields to table tool_trigger_events.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('eventname', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextlevel', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextinstanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('link', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_trigger_events.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for tool_trigger_events.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Trigger savepoint reached.
        upgrade_plugin_savepoint(true, 2018050700, 'tool', 'trigger');
    }

    if ($oldversion < 2018050702) {

        // Define field eventid to be added to tool_trigger_queue.
        $table = new xmldb_table('tool_trigger_queue');
        $field = new xmldb_field('eventid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'workflowid');

        // Conditionally launch add field eventid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('laststep', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'tries');

        // Conditionally launch add field eventid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trigger savepoint reached.
        upgrade_plugin_savepoint(true, 2018050702, 'tool', 'trigger');
    }

    if ($oldversion < 2018050703) {

        // Invalid Type set on theses fields (binary) should be int. unfortunately change_type is a bit hard, easier to drop field
        // and recreate.
        // TODO: We should remove this block before production release as it's only needed for us testing with older code.

        // Changing type of field async on table tool_trigger_workflows to int.
        $table = new xmldb_table('tool_trigger_workflows');

        // Launch change of type for field async.
        $field = new xmldb_field('async', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0, 'event');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $dbman->add_field($table, $field);

        $field = new xmldb_field('enabled', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 1, 'async');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $dbman->add_field($table, $field);

        $field = new xmldb_field('draft', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0, 'enabled');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $dbman->add_field($table, $field);

        // Trigger savepoint reached.
        upgrade_plugin_savepoint(true, 2018050703, 'tool', 'trigger');
    }

    return true;
}