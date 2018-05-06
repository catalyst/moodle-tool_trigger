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


    /** @var array $buffer buffer of events. */
    protected $buffer = array();

    /** @var int Number of entries in the buffer. */
    protected $count = 0;

    /** @var  eventobservers a reference to a self instance. */
    protected static $instance;

    /**
     * The observer monitoring all the events.
     *
     * @param \core\event\base $event event object.
     */
    public static function process_event(\core\event\base $event) {

        if (empty(self::$instance)) {
            self::$instance = new static();
            // Register shutdown handler - this is useful for buffering, processing events, etc.
            \core_shutdown_manager::register_function(array(self::$instance, 'process_buffer'));
        }

        self::$instance->buffer_event($event);

        return false;

    }

    /**
     * Api to buffer events to store, to reduce db queries.
     *
     * @param \core\event\base $event
     */
    protected function buffer_event(\core\event\base $event) {

        // If there are no subscriptions for this event do not buffer it.
        if (!\tool_trigger\event_processor::event_has_subscriptions($event->eventname)) {
            return false;
        }

        $eventdata = $event->get_data();
        $eventobj = new \stdClass();
        $eventobj->eventname = $eventdata['eventname'];
        $eventobj->contextid = $eventdata['contextid'];
        $eventobj->contextlevel = $eventdata['contextlevel'];
        $eventobj->contextinstanceid = $eventdata['contextinstanceid'];
        if ($event->get_url()) {
            // Get link url if exists.
            $eventobj->link = $event->get_url()->out();
        } else {
            $eventobj->link = '';
        }
        $eventobj->courseid = $eventdata['courseid'];
        $eventobj->timecreated = $eventdata['timecreated'];

        $this->buffer[] = $eventobj;
        $this->count++;
    }

    /**
     * This method process all events stored in the buffer.
     *
     * This is a multi purpose api. It does the following:-
     * 1. Write event data to tool_monitor_events
     * 2. Find out users that need to be notified about rule completion and schedule a task to send them messages.
     */
    public function process_buffer() {
        $events = $this->flush(); // Flush data.

        // TODO: process async events if any exist.

    }

    /**
     * Protected method that flushes the buffer of events and writes them to the database.
     *
     * @return array a copy of the events buffer.
     */
    protected function flush() {
        global $DB;

        // Flush the buffer to the db.
        $events = $this->buffer;
        $DB->insert_records('tool_trigger_events', $events); // Insert the whole chunk into the database.
        $this->buffer = array();
        $this->count = 0;
        return $events;
    }

    /**
     * Returns true if an event in a particular course has a subscription.
     *
     * @param string $eventname the name of the event
     * @param int $courseid the course id
     * @return bool returns true if the event has subscriptions in a given course, false otherwise.
     */
    public static function event_has_subscriptions($eventname) {
        global $DB;

        // Check if we can return these from cache.
        $cache = \cache::make('tool_trigger', 'eventsubscriptions');

        // The SQL we will be using to fill the cache if it is empty.
        $sql = "SELECT DISTINCT(r.eventname)
                  FROM {tool_trigger_workflow}";

        $sitesubscriptions = $cache->get(0);
        // If we do not have the triggers in the cache then return them from the DB.
        if ($sitesubscriptions === false) {
            // Set the array for the cache.
            $sitesubscriptions = array();
            if ($subscriptions = $DB->get_records_sql($sql)) {
                foreach ($subscriptions as $subscription) {
                    $sitesubscriptions[$subscription->eventname] = true;
                }
            }
            $cache->set(0, $sitesubscriptions);
        }

        // Check if a subscription exists for this event.
        if (isset($sitesubscriptions[$eventname])) {
            return true;
        }

        return false;
    }

}
