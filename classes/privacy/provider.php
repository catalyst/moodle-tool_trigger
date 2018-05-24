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
    \core_privacy\local\request\plugin\provider {

    use \tool_log\local\privacy\moodle_database_export_and_delete;

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

        // The plugin itself provides the data from the event.
        $datafields = ['tool_trigger_events' => 'privacy:metadata:events'];

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

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
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

        return $contextlist;
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
}
