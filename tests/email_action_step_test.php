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

namespace tool_trigger;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/fixtures/user_event_fixture.php');

/**
 * Test of the email action
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_trigger\steps\actions\email_action_step
 */
final class email_action_step_test extends \advanced_testcase {
    use \tool_trigger_user_event_fixture;

    /**
     * Messages sink for testing.
     * @var \phpunit_message_sink
     */
    private $sink;

    /**
     * Create a "user_profile_viewed" event, of user1 viewing user2's
     * profile. And then run everything else as the cron user.
     */
    public function setup(): void {
        $this->setup_user_event();

        // Set up the email sync.
        unset_config('noemailever');
        $this->sink = $this->redirectMessages();
    }

    public function test_execute_basic(): void {
        $settings = [
            'emailto' => $this->user1->email,
            'emailsubject' => 'Subject of the email',
            'emailcontent_editor[text]' => 'Content of the email',
            'emailcontent_editor[format]' => 0,
        ];
        $step = new \tool_trigger\steps\actions\email_action_step(json_encode($settings));

        // Run the step.
        [$status] = $step->execute(null, null, $this->event, []);
        $this->assertTrue($status);

        // Retrieve the messages sent (should be just one).
        $messages = $this->sink->get_messages();
        $this->assertEquals(1, count($messages));

        // Retrieve the first (and only) message.
        $message = reset($messages);
        $this->assertEquals(
            \core_user::get_support_user()->id,
            $message->useridfrom
        );
        $this->assertEquals(
            $this->user1->id,
            $message->useridto
        );
        $this->assertEquals(
            $settings['emailsubject'],
            $message->subject
        );
        $this->assertEquals(
            $settings['emailcontent_editor[text]'],
            $message->fullmessage
        );
    }

    public function test_execute_external_email_address(): void {
        $settings = [
            'emailto' => 'testusernotinmoodle@example.com',
            'emailsubject' => 'Subject of the email',
            'emailcontent_editor[text]' => 'Content of the email',
            'emailcontent_editor[format]' => 0,
        ];
        $step = new \tool_trigger\steps\actions\email_action_step(json_encode($settings));

        // Execute the step.
        [$status, $stepresults] = $step->execute(null, null, $this->event, []);
        $this->assertTrue($status);

        // Retrieve the message.
        $messages = $this->sink->get_messages();
        $this->assertEquals(1, count($messages));
        $message = reset($messages);

        // If the email doesn't match a user in the database, it should send it to a mock user
        // with values based on the noreply user, but with the email changed.
        $this->assertEquals(
            \core_user::get_noreply_user()->id,
            $message->useridto
        );
        // The to email should be visible in the fields added to stepresults.
        $this->assertEquals(
            $settings['emailto'],
            $stepresults['email_action_userto_email']
        );
    }

    public function test_execute_multiple_email_addresses(): void {
        $emailstosend = [$this->user1->email, $this->user2->email, 'testusernotinmoodle@example.com'];
        $settings = [
            'emailto' => implode(",", $emailstosend),
            'emailsubject' => 'Subject of the email',
            'emailcontent_editor[text]' => 'Content of the email',
            'emailcontent_editor[format]' => 0,
        ];
        $step = new \tool_trigger\steps\actions\email_action_step(json_encode($settings));

        // Execute the step.
        [$status, $stepresults] = $step->execute(null, null, $this->event, []);
        $this->assertTrue($status);

        // Retrieve the messages.
        $messages = $this->sink->get_messages();
        $this->assertEquals(count($emailstosend), count($messages));

        $this->assertEquals($this->user1->id, $messages[0]->useridto);
        $this->assertEquals($this->user2->id, $messages[1]->useridto);
    }

    public function test_execute_multiple_email_addresses_with_datafields(): void {
        $emailstosend = [$this->user1->email, $this->user2->email, 'testusernotinmoodle@example.com'];
        $settings = [
            'emailto' => '{user_emails}',
            'emailsubject' => 'Subject of the email',
            'emailcontent_editor[text]' => 'Content of the email',
            'emailcontent_editor[format]' => 0,
        ];
        $step = new \tool_trigger\steps\actions\email_action_step(json_encode($settings));

        // Ensure that this step can accept a string of emails from a previous step.
        // Not currently possible with existing steps.
        $emailstosend = [$this->user1->email, $this->user2->email];
        $prevstepresults = [
            'user_emails' => implode(',', $emailstosend),
        ];

        // Execute the step.
        [$status] = $step->execute(null, null, $this->event, $prevstepresults);
        $this->assertTrue($status);

        // Retrieve the messages.
        $messages = $this->sink->get_messages();
        $this->assertEquals(count($emailstosend), count($messages));
    }

    public function test_execute_with_datafields(): void {
        $settings = [
            'emailto' => '{user_email}',
            'emailsubject' => 'User {userid} looked at your profile',
            'emailcontent_editor[text]' =>
                'user {userid}, in course {other_courseid} aka "{other_courseshortname}" aka "{other_coursefullname}"',
            'emailcontent_editor[format]' => 0,
        ];
        $step = new \tool_trigger\steps\actions\email_action_step(json_encode($settings));

        $prevstepresults = [
            // In practice, this value would have been added by a previous user_lookup step.
            'user_email' => $this->user2->email,
        ];

        // Run the step.
        [$status] = $step->execute(null, null, $this->event, $prevstepresults);
        $this->assertTrue($status);

        // Retrieve the messages sent (should be just one).
        $messages = $this->sink->get_messages();
        $this->assertEquals(1, count($messages));

        // Retrieve the first (and only) message.
        $message = reset($messages);
        $this->assertEquals(
            \core_user::get_support_user()->id,
            $message->useridfrom
        );
        $this->assertEquals(
            $this->user2->id,
            $message->useridto
        );
        $eventdata = $this->event->get_data();
        $this->assertEquals(
            sprintf('User %s looked at your profile', $eventdata['userid']),
            $message->subject
        );
        $this->assertEquals(
            sprintf(
                'user %s, in course %s aka "%s" aka "%s"',
                $eventdata['userid'],
                $eventdata['other']['courseid'],
                $eventdata['other']['courseshortname'],
                $eventdata['other']['coursefullname']
            ),
            $message->fullmessage
        );
    }
}
