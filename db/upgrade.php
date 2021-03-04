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

    if ($oldversion < 2019021100) {

        // Convert all old email text fields to new Atto fields.
        $sql = 'SELECT * FROM {tool_trigger_steps} WHERE stepclass = \'\tool_trigger\steps\actions\email_action_step\'';
        $rs = $DB->get_recordset_sql($sql, array());
        foreach ($rs as $record) {
            $data = json_decode($record->data, true);
            $data['emailcontent_editor[text]'] = $data['emailcontent'];
            unset($data['emailcontent']);
            $data['emailcontent_editor[format]'] = 1;
            $jsondata = json_encode($data);
            $record->data = $jsondata;
            $DB->update_record('tool_trigger_steps', $record);
        }
        $rs->close();
        // Trigger savepoint reached.
        upgrade_plugin_savepoint(true, 2019021100, 'tool', 'trigger');
    }

    if ($oldversion < 2019102200) {

        // A new realtime field to the workflow table.
        $table = new xmldb_table('tool_trigger_workflows');
        $field = new xmldb_field('realtime', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trigger savepoint reached.
        upgrade_plugin_savepoint(true, 2019102200, 'tool', 'trigger');
    }

    if ($oldversion < 2020050100) {

        // Define table tool_trigger_workflow_hist to be created.
        $table = new xmldb_table('tool_trigger_workflow_hist');

        // Adding fields to table tool_trigger_workflow_hist.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('workflowid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('number', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '15', null, XMLDB_NOTNULL, null, null);
        $table->add_field('eventid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('event', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('failedstep', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table tool_trigger_workflow_hist.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('eventid', XMLDB_KEY_FOREIGN, array('eventid'), 'tool_trigger_events', array('id'));

        // Conditionally launch create table for tool_trigger_workflow_hist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table tool_trigger_run_hist to be created.
        $table = new xmldb_table('tool_trigger_run_hist');

        // Adding fields to table tool_trigger_run_hist.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('workflowid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('runid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('stepclass', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('steporder', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('executed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('prevstepid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('number', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('results', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('stepconfigid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_trigger_run_hist.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('workflowid', XMLDB_KEY_FOREIGN, array('workflowid'), 'tool_trigger_workflows', array('id'));
        $table->add_key('run', XMLDB_KEY_FOREIGN, array('runid'), 'tool_trigger_workflow_hist', array('id'));

        // Adding indexes to table tool_trigger_run_hist.
        $table->add_index('class', XMLDB_INDEX_NOTUNIQUE, array('stepclass'));

        // Conditionally launch create table for tool_trigger_run_hist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define field debug to be added to tool_trigger_workflows.
        $table = new xmldb_table('tool_trigger_workflows');
        $field = new xmldb_field('debug', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'draft');

        // Conditionally launch add field debug.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trigger savepoint reached.
        upgrade_plugin_savepoint(true, 2020050100, 'tool', 'trigger');
    }

    if ($oldversion < 2020061900) {

        // Define field errorstep to be added to tool_trigger_workflow_hist.
        $table = new xmldb_table('tool_trigger_workflow_hist');
        $field = new xmldb_field('errorstep', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'failedstep');

        // Conditionally launch add field errorstep.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trigger savepoint reached.
        upgrade_plugin_savepoint(true, 2020061900, 'tool', 'trigger');
    }

    if ($oldversion < 2021022300) {

        // Define field executiontime to be added to tool_trigger_queue.
        $table = new xmldb_table('tool_trigger_queue');
        $field = new xmldb_field('executiontime', XMLDB_TYPE_INTEGER, '15', null, null, null, null, 'timemodified');

        // Conditionally launch add field executiontime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trigger savepoint reached.
        upgrade_plugin_savepoint(true, 2021022300, 'tool', 'trigger');
    }

    return true;
}
