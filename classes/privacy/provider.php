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
 * Privacy provider for Trigger admin tool
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  2018 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_trigger\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\userlist;

/**
 * Provider for the tool_trigger plugin.
 *
 * TODO: Currently this class has made the get_metadata() method extensible by step classes,
 * but not any of the other methods. This is fine as long as none of the steps store data,
 * but if we do implement steps that store data, we'll need to make the methods related to that
 * be extensible as well.
 *
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  2018 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    use \tool_log\local\privacy\moodle_database_export_and_delete {
        delete_data_for_all_users_in_context as trait_delete_data_for_all_users_in_context;
        delete_data_for_user as trait_delete_data_for_user;
    }

    /**
     * Returns metadata about this plugin's privacy policy.
     *
     * @param   collection $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $wfm = new \tool_trigger\workflow_manager();
        $steplist = $wfm->get_step_class_names();

        // Information about the tool_trigger_events table, which logs events.
        $collection->add_database_table(
            'tool_trigger_events',
            [
                'eventname' => 'privacy:metadata:events:eventname',
                'userid' => 'privacy:metadata:events:userid',
                'relateduserid' => 'privacy:metadata:events:relateduserid',
                'anonymous' => 'privacy:metadata:events:anonymous',
                'other' => 'privacy:metadata:events:other',
                'timecreated' => 'privacy:metadata:events:timecreated',
                'origin' => 'privacy:metadata:events:origin',
                'ip' => 'privacy:metadata:events:ip',
                'realuserid' => 'privacy:metadata:events:realuserid',
            ],
            'privacy:metadata:events'
        );

        $collection->add_database_table(
            'tool_trigger_learn_events',
            [
                'eventname' => 'privacy:metadata:learnevents:eventname',
                'userid' => 'privacy:metadata:learnevents:userid',
                'relateduserid' => 'privacy:metadata:learnevents:relateduserid',
                'anonymous' => 'privacy:metadata:learnevents:anonymous',
                'other' => 'privacy:metadata:learnevents:other',
                'timecreated' => 'privacy:metadata:learnevents:timecreated',
                'origin' => 'privacy:metadata:learnevents:origin',
                'ip' => 'privacy:metadata:learnevents:ip',
                'realuserid' => 'privacy:metadata:learnevents:realuserid',
            ],
            'privacy:metadata:learnevents'
        );

        // The plugin itself provides the data from the event.
        $datafields = ['tool_trigger_events' => 'privacy:metadata:events'];
        $datafields = ['tool_trigger_learn_events' => 'privacy:metadata:learnevents'];

        // Get a list of the sensitive data that each step provides.
        foreach ($steplist as $stepclass) {
            $stepfields = $stepclass::get_privacyfields();
            if (null !== $stepfields) {
                $datafields = array_merge($datafields, $stepfields);
            }
        }

        // Allow each step to declare anything it does with that data.
        foreach ($steplist as $stepclass) {
            $stepclass::add_privacy_metadata($collection, $datafields);
        }

        // Add history table information.
        $collection->add_database_table(
            'tool_trigger_workflow_hist',
            [
                'timecreated' => 'privacy:metadata:workflowhistory:timecreated',
                'event' => 'privacy:metadata:workflowhistory:event',
            ],
            'privacy:metadata:workflowhistory'
        );

        $collection->add_database_table(
            'tool_trigger_run_hist',
            [
                'executed' => 'privacy:metadata:runhistory:executed',
                'results' => 'privacy:metadata:runhistory:results',
            ],
            'privacy:metadata:runhistory'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     * This list will always contain historic data contexts, as they are based off event contexts.
     *
     * @param   int         $userid     The user to search.
     * @return  contextlist   $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "
            SELECT l.contextid
              FROM {tool_trigger_events} l
             WHERE l.userid = :userid1
                OR l.relateduserid = :userid2
                OR l.realuserid = :userid3";

        $contextlist->add_from_sql($sql, [
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
        ]);

        $sql = "
            SELECT l.contextid
              FROM {tool_trigger_learn_events} l
             WHERE l.userid = :userid1
                OR l.relateduserid = :userid2
                OR l.realuserid = :userid3";

        $contextlist->add_from_sql($sql, [
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * We want to let the \moodle_database_export_and_delete trait take care of finding
     * the event records to delete. But once that's done, we also need to delete any
     * workflow queue entries for those events.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        self::trait_delete_data_for_all_users_in_context($context);
        self::delete_orphaned_queue_entries();
    }

    /**
     * We want to let the \moodle_database_export_and_delete trait take care of finding
     * the event records to delete. But once that's done, we also need to delete any
     * workflow queue entries for those events.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        self::trait_delete_data_for_user($contextlist);
        self::delete_orphaned_queue_entries();
    }

    /**
     * Delete workflow queue entries that point to event records that have been deleted.
     * We have to do a blind "delete all orphaned records" query, because
     * \moodle_database_export_and_delete doesn't give us a list of the IDs of the
     * records it's about to delete.
     */
    private static function delete_orphaned_queue_entries() {
        global $DB;
        $DB->execute("
            DELETE
              FROM {tool_trigger_queue} q
             WHERE NOT EXISTS (
                SELECT 1
                  FROM {tool_trigger_events} e
                 WHERE q.eventid = e.id
            )"
        );

        $DB->execute("
            DELETE
              FROM {tool_trigger_queue} q
             WHERE NOT EXISTS (
                SELECT 1
                  FROM {tool_trigger_learn_events} e
                 WHERE q.eventid = e.id
            )");

        // Now delete historic trigger runs that are orphaned.
        $DB->execute("
            DELETE
              FROM {tool_trigger_workflow_hist} wfh
             WHERE NOT EXISTS (
                SELECT 1
                  FROM {tool_trigger_events} e
                 WHERE wfh.eventid = e.id
            )");

        $DB->execute("
            DELETE
              FROM {tool_trigger_workflow_hist} wfh
             WHERE NOT EXISTS (
                SELECT 1
                  FROM {tool_trigger_learn_events} e
                 WHERE wfh.eventid = e.id
            )");

        // Finally, delete the historic steps that are now orphaned.
        $DB->execute("
            DELETE
              FROM {tool_trigger_run_hist} rh
             WHERE NOT EXISTS (
                SELECT 1
                  FROM {tool_trigger_workflow_hist} wfh
                 WHERE rh.runid = wfh.id
            )");
    }

    /**
     * Get the database object.
     *
     * @return array Containing moodle_database, string, or null values.
     */
    protected static function get_database_and_table() {
        global $DB;
        return [$DB, 'tool_trigger_events'];
    }

    /**
     * Get the path to export the logs to.
     *
     * @return array
     */
    protected static function get_export_subcontext() {
        return [get_string('pluginname', 'tool_trigger')];
    }

    /**
     * Get the list of users who have data within a context.
     * This list will always contain historic user data, as they are based off event users.
     *
     * @param userlist $userlist the userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $params = ['userid' => $context->instanceid];

        $sql = "SELECT userid FROM {tool_trigger_events} WHERE userid = :userid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT userid FROM {tool_trigger_learn_events} WHERE userid = :userid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $userids = $userlist->get_userids();
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        // Only delete events that do not have an unfinished queue still waiting.
        list($insql, $inparams) = $DB->get_in_or_equal($userids);
        array_unshift($inparams, \tool_trigger\task\process_workflows::STATUS_READY_TO_RUN);
        $sql = "
            DELETE
              FROM {tool_trigger_events}
             WHERE NOT EXISTS (
                       SELECT 1
                         FROM {tool_trigger_queue}
                        WHERE {tool_trigger_queue}.eventid = {tool_trigger_events}.id
                              AND {tool_trigger_queue}.status = ?
                   ) AND userid $insql";
        $DB->execute($sql, $inparams);

        // Also remove user information from learnt events.
        list($insql, $inparams) = $DB->get_in_or_equal($userids);
        $select = "userid $insql";
        $DB->delete_records_select('tool_trigger_learn_events', $select, $inparams);

        // Now delete historic data that was based off events now deleted.
        $DB->execute("
            DELETE
              FROM {tool_trigger_workflow_hist}
             WHERE NOT EXISTS (
                SELECT 1
                  FROM {tool_trigger_events}
                 WHERE {tool_trigger_workflow_hist}.eventid = {tool_trigger_events}.id
            )");

        $DB->execute("
            DELETE
              FROM {tool_trigger_workflow_hist}
             WHERE NOT EXISTS (
                SELECT 1
                  FROM {tool_trigger_learn_events}
                 WHERE {tool_trigger_workflow_hist}.eventid = {tool_trigger_learn_events}.id
            )");

        // Finally, delete the historic steps that are now orphaned.
        $DB->execute("
            DELETE
              FROM {tool_trigger_run_hist}
             WHERE NOT EXISTS (
                SELECT 1
                  FROM {tool_trigger_workflow_hist}
                 WHERE {tool_trigger_run_hist}.runid = {tool_trigger_workflow_hist}.id
            )");
    }
}
