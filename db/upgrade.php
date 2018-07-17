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

    if ($oldversion < 2018063004) {

        // Add new index to table tool_trigger_events.
        $table = new xmldb_table('tool_trigger_events');
        $table->add_index('eventname', XMLDB_INDEX_NOTUNIQUE, array('eventname'));

        // Conditionally launch create table for tool_trigger_events.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add table tool_trigger_learn_events.
        $table = new xmldb_table('tool_trigger_learn_events');

        // Adding fields to table tool_trigger_learn_events.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('eventname', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null);
        $table->add_field('component', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('action', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('target', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('objecttable', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('objectid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('crud', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('edulevel', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextlevel', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextinstanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('relateduserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('anonymous', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('other', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('origin', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('ip', XMLDB_TYPE_CHAR, '45', null, null, null, null);
        $table->add_field('realuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('contextid', XMLDB_KEY_FOREIGN, array('contextid'), 'context', array('id'));

        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, array('timecreated'));
        $table->add_index('eventname', XMLDB_INDEX_NOTUNIQUE, array('eventname'));

        // Conditionally launch create table for tool_trigger_events.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Trigger savepoint reached.
        upgrade_plugin_savepoint(true, 2018063004, 'tool', 'trigger');
    }

    if ($oldversion < 2018070101) {

        // Define table tool_trigger_event_fields to be created.
        $table = new xmldb_table('tool_trigger_event_fields');

        // Adding fields to table tool_trigger_event_fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('eventname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('jsonfields', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_trigger_event_fields.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table tool_trigger_event_fields.
        $table->add_index('eventname', XMLDB_INDEX_UNIQUE, ['eventname']);

        // Conditionally launch create table for tool_trigger_event_fields.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Trigger savepoint reached.
        upgrade_plugin_savepoint(true, 2018070101, 'tool', 'trigger');
    }

    if ($oldversion < 2018071700) {

        // Add events fields from fixture file to database.
        $learnprocess = new \tool_trigger\learn_process();
        $learnprocess->process_fixtures();

        // Trigger savepoint reached.
        upgrade_plugin_savepoint(true, 2018071700, 'tool', 'trigger');
    }

    return true;
}