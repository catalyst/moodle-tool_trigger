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
 * A lot of our tests need to go through a similar set of steps to
 * create an event. This is a trait that does so.
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

trait tool_trigger_user_event_fixture {
    public $user1 = null;
    public $user2 = null;
    public $course = null;
    public $event = null;

    /**
     * Create a "user_profile_viewed" event, of user1 viewing user2's
     * profile. And then run everything else as the cron user.
     */
    public function setup_user_event() {
        $this->resetAfterTest(true);

        // Data for fields that are not automatically filled in by the data generator.
        $extrauserdata = [
            'description' => '<p>My description</p>',
            'descriptionformat' => FORMAT_HTML,
            'url' => 'https://www.example.com',
            'picture' => 1
        ];

        $this->user1 = $this->getDataGenerator()->create_user($extrauserdata);
        $this->user2 = $this->getDataGenerator()->create_user($extrauserdata);
        $this->course = $this->getDataGenerator()->create_course();

        $this->setUser($this->user1);

        $this->event = \core\event\user_profile_viewed::create([
            'objectid' => $this->user2->id,
            'relateduserid' => $this->user2->id,
            'context' => context_user::instance($this->user2->id),
            'other' => [
                'courseid' => $this->course->id,
                'courseshortname' => $this->course->shortname,
                'coursefullname' => $this->course->fullname
            ]
        ]);

        $this->event->trigger();

        // Run as the cron user  .
        cron_setup_user();
    }
}