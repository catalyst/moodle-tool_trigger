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
 * Renderable class for run history page.
 *
 * @package    tool_trigger
 * @copyright  Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\output\workflowhistory;

use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

class runhistory_renderable extends \table_sql implements \renderable {
    /**
     * @var \context_course|\context_system context of the page to be rendered.
     */
    protected $context;

    /**
     * @var bool Does the user have capability to manage rules at site context.
     */
    protected $hassystemcap;

    /**
     * @var int internal number counter for steps
     */
    protected $stepcounter;

    /**
     * Sets up the table_log parameters.
     *
     * @param string $uniqueid Unique id of form.
     * @param \moodle_url $url Url where this table is displayed.
     * @param int $perpage Number of rules to display per page.
     */
    public function __construct($uniqueid, \moodle_url $url, $perpage = 100) {
        parent::__construct($uniqueid);

        $this->set_attribute('id', 'tooltriggerrunhistory_table');
        $this->set_attribute('class', 'tooltrigger runhistory generaltable generalbox');
        $this->define_columns(array(
                'step',
                'id',
                'name',
                'type',
                'executed',
                'prevstep',
                'actions'
        ));
        $this->define_headers(array(
                get_string('stepnumber', 'tool_trigger'),
                get_string('stepid', 'tool_trigger'),
                get_string('name'),
                get_string('type', 'search'),
                get_string('timeexecuted', 'tool_trigger'),
                get_string('prevstep', 'tool_trigger'),
                get_string('actions')
            )
        );
        $this->pagesize = $perpage;
        $systemcontext = \context_system::instance();
        $this->context = $systemcontext;
        $this->collapsible(false);
        $this->sortable(false, 'id', SORT_DESC);
        $this->pageable(true);
        $this->is_downloadable(false);
        $this->define_baseurl($url);
        $this->stepcounter = 1;
    }

    public function col_step($step) {
        // Add 1 to be human readable.
        return $step->number + 1;
    }

    public function col_id($step) {
        return $step->id;
    }

    public function col_name($step) {
        return $step->name;
    }

    public function col_type($step) {
        return $step->type;
    }

    public function col_executed($step) {
        $format = get_string('strftimedatetimeshort', 'langconfig');
        return userdate($step->executed, $format);
    }

    public function col_prevstep($step) {
        if (!empty($step->prevstepid)) {
            return $step->prevstepid;
        } else {
            return get_string('none');
        }
    }

    public function col_actions($step) {
        global $PAGE;

        $renderer = $PAGE->get_renderer('tool_trigger', 'workflowhistory');
        return $renderer->step_actions_button($step);
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

        echo html_writer::start_tag('div');
        echo html_writer::start_tag('table', $this->attributes);

    }
}
