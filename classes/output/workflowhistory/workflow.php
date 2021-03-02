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
 * Renderable class for workflow history page.
 *
 * @package    tool_trigger
 * @copyright  Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\output\workflowhistory;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

class workflowhistory_renderable extends \table_sql implements \renderable {
    /**
     * @var \context_course|\context_system context of the page to be rendered.
     */
    protected $context;

    /**
     * @var bool Does the user have capability to manage rules at site context.
     */
    protected $hassystemcap;

    /**
     * Sets up the table_log parameters.
     *
     * @param string $uniqueid Unique id of form.
     * @param \moodle_url $url Url where this table is displayed.
     * @param int $perpage Number of rules to display per page.
     */
    public function __construct($uniqueid, \moodle_url $url, $perpage = 100) {
        parent::__construct($uniqueid);

        $this->set_attribute('id', 'tooltriggerworkflowhistory_table');
        $this->set_attribute('class', 'tooltrigger workflowhistory generaltable generalbox');
        $this->define_columns(array(
                'id',
                'number',
                'eventid',
                'username',
                'description',
                'time',
                'runstatus',
                'actions'
        ));
        $this->define_headers(array(
                get_string('runid', 'tool_trigger'),
                get_string('triggernumber', 'tool_trigger'),
                get_string('eventid', 'tool_trigger'),
                get_string('username'),
                get_string('eventdescription', 'tool_trigger'),
                get_string('time'),
                get_string('runstatus', 'tool_trigger'),
                get_string('actions')
            )
        );
        $this->pagesize = $perpage;
        $systemcontext = \context_system::instance();
        $this->context = $systemcontext;
        $this->collapsible(false);
        $this->sortable(false, 'number', SORT_DESC);
        $this->pageable(true);
        $this->is_downloadable(false);
        $this->define_baseurl($url);
    }

    public function col_id($run) {
        return $run->id;
    }

    public function col_number($run) {
        return $run->number;
    }

    public function col_eventid($run) {
        return $run->eventid;
    }

    public function col_username($run) {
        $eventdata = json_decode($run->event);
        $user = \core_user::get_user($eventdata->userid);
        return fullname($user);
    }

    public function col_description($run) {
        // Get the event class from info.
        $evententry = json_decode($run->event);
        $processor = new \tool_trigger\event_processor();
        $eventobject = $processor->restore_event($evententry);

        return $eventobject->get_description();
    }

    public function col_time($run) {
        $format = get_string('strftimedatetimeshort', 'langconfig');
        return userdate($run->timecreated, $format);
    }

    public function col_runstatus($run) {
        global $DB;
        // Return a badge for the status.
        if (!empty($run->errorstep)) {
            return \html_writer::tag('span', get_string('errorstep', 'tool_trigger', $run->errorstep + 1),
                array('class' => 'badge badge-warning'));
            // Handle debounce statuses.
        } else if (!empty($run->failedstep) && ((int) $run->failedstep === \tool_trigger\task\process_workflows::STATUS_CANCELLED)) {
            return \html_writer::tag('span', get_string('cancelled'), array('class' => 'badge badge-info'));
        } else if (!empty($run->failedstep) && ((int) $run->failedstep === \tool_trigger\task\process_workflows::STATUS_DEFERRED)) {
            return \html_writer::tag('span', get_string('deferred', 'tool_trigger'), array('class' => 'badge badge-info'));
        } else if (!empty($run->failedstep)) {
            return \html_writer::tag('span', get_string('failedstep', 'tool_trigger', $run->failedstep + 1),
                array('class' => 'badge badge-danger'));
        } else {
            // Find the number of steps executed.
            $sql = "SELECT MAX(number) FROM {tool_trigger_run_hist} WHERE runid = ?";
            $res = $DB->get_field_sql($sql, [$run->id]);
            // Output a plain passed badge if no data found, rather than a number badge.
            if (!empty($res)) {
                $string = get_string('runpassed', 'tool_trigger', $res + 1);
            } else {
                $string = get_string('runpassednonum', 'tool_trigger');
            }
            return \html_writer::tag('span', $string, array('class' => 'badge badge-success'));
        }
    }

    public function col_actions($run) {
        global $PAGE;

        $renderer = $PAGE->get_renderer('tool_trigger', 'workflowhistory');

        $statusonly = !empty($run->failedstep) &&
            ((int) $run->failedstep === \tool_trigger\task\process_workflows::STATUS_CANCELLED ||
            (int) $run->failedstep === \tool_trigger\task\process_workflows::STATUS_DEFERRED);

        return $renderer->run_actions_button($run, $statusonly);
    }

    /**
     * This is a copy of the tablelib implementation,
     * but removes no-overflow class from the parent div.
     * Allows dropdowns to overflow.
     */
    public function start_html() {
        global $OUTPUT;

        // Render button to allow user to reset table preferences.
        echo $this->render_reset_button();

        // Do we need to print initial bars?
        $this->print_initials_bar();

        // Paging bar.
        if ($this->use_pages) {
            $pagingbar = new \paging_bar($this->totalrows, $this->currpage, $this->pagesize, $this->baseurl);
            $pagingbar->pagevar = $this->request[TABLE_VAR_PAGE];
            echo $OUTPUT->render($pagingbar);
        }

        if (in_array(TABLE_P_TOP, $this->showdownloadbuttonsat)) {
            echo $this->download_buttons();
        }

        $this->wrap_html_start();
        // Start of main data table.

        echo \html_writer::start_tag('div');
        echo \html_writer::start_tag('table', $this->attributes);
    }
}
