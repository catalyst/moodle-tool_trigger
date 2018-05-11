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
 * Process trigger system events.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger;

defined('MOODLE_INTERNAL') || die();

/**
 * Process trigger system events.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_processor {

    // This class works basically the same as a logstore writer plugin.
    use \tool_log\helper\buffered_writer;

    /** @var  static a reference to an instance of this class (using late static binding). */
    protected static $singleton;

    protected function __construct() {
        // Register shutdown handler - this is useful for buffering, processing events, etc.
        \core_shutdown_manager::register_function(array($this, 'flush'));
    }

    /**
     * The observer monitoring all the events.
     *
     * @param \core\event\base $event event object.
     */
    public static function process_event(\core\event\base $event) {

        if (empty(self::$singleton)) {
            self::$singleton = new self();
        }

        // Check whether this an event we're subscribed to,
        // and run the appropriate workflow(s) if so.
        self::$singleton->write($event);

        return false;

    }

    /**
     * The \tool_log\helper\buffered_writer trait uses this to decide whether
     * or not to record an event.
     *
     * @param \core\event\base $event
     * @return boolean
     */
    protected function is_event_ignored(\core\event\base $event) {
        global $DB;

        // Check if we can return these from cache.
        $cache = \cache::make('tool_trigger', 'eventsubscriptions');

        // The SQL we will be using to fill the cache if it is empty.
        $sql = "SELECT DISTINCT(event)
                  FROM {tool_trigger_workflows}";

        $sitesubscriptions = $cache->get(0);
        // If we do not have the triggers in the cache then return them from the DB.
        if ($sitesubscriptions === false) {
            // Set the array for the cache.
            $sitesubscriptions = array();
            if ($subscriptions = $DB->get_records_sql($sql)) {
                foreach ($subscriptions as $subscription) {
                    $sitesubscriptions[$subscription->event] = true;
                }
            }
            $cache->set(0, $sitesubscriptions);
        }

        // Check if a subscription exists for this event.
        if (isset($sitesubscriptions[$event->eventname])) {
            return false;
        }

        return true;
    }

    /**
     * Used by the \tool_log\helper\buffered_writer trait to insert records
     * into the database.
     *
     * @param array $evententries raw event data
     */
    protected function insert_event_entries($evententries) {
        global $DB;
        $DB->insert_records('tool_trigger_events', $evententries);
    }

    /**
     * Used by the \tool_log\helper\buffered_writer trait.
     *
     * @param string $name Config name
     * @param mixed $default default value
     *
     * @return mixed config value if set, else the default value.
     */
    protected function get_config($name, $default = null) {
        // We don't actually need to return any config values for our purposes.
        return false;
    }
}
