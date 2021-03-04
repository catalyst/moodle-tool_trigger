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
 * Renderer class for workflow history page.
 *
 * @package    tool_trigger
 * @copyright  Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\output\workflowhistory;

use single_button;

defined('MOODLE_INTERNAL') || die;
require_once(__DIR__.'/run.php');
require_once(__DIR__.'/workflow.php');

/**
 * Renderer class for workflow history page.
 *
 * @package    tool_trigger
 * @copyright  Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Sets the SQL for the table, and renders it.
     *
     * @param \tool_trigger\output\workflowhistory\renderable $renderable the table to render
     * @return void
     */
    public function render_table($workflowid, $run = null) {
        $url = new \moodle_url('/admin/tool/trigger/history.php', ['workflow' => $workflowid, 'run' => $run]);

        // Decide which table to render if run is given.
        if (!empty($run)) {
            $renderable = new \tool_trigger\output\workflowhistory\runhistory_renderable('runhistory', $url);
            $sql = (object) [
                'fields' => '*',
                'from' => '{tool_trigger_run_hist}',
                'where' => 'workflowid = :workflow AND runid = :run',
                'params' => ['workflow' => $workflowid, 'run' => $run]
            ];
        } else {
            // We want to ouput some buttons before drawing the table.
            $this->rerun_all_historic_button($workflowid);
            echo '&nbsp;';
            $this->rerun_all_current_button($workflowid);

            $renderable = new \tool_trigger\output\workflowhistory\workflowhistory_renderable('triggerhistory', $url);
            $sql = (object) [
                'fields' => '*',
                'from' => '{tool_trigger_workflow_hist}',
                'where' => 'workflowid = :workflow',
                'params' => ['workflow' => $workflowid]
            ];
        }

        // Then output the table.
        $renderable->sql = $sql;
        $renderable->out($renderable->pagesize, true);
    }

    public function step_actions_button($step) {
        $workflow = required_param('workflow', PARAM_INT);
        $run = required_param('run', PARAM_INT);

        $btn = '';

        $stepdetailsurl = new \moodle_url('/admin/tool/trigger/stepdetails.php', ['id' => $step->id]);
        $stepdetailslbtn = \html_writer::link($stepdetailsurl,
            get_string('viewstepinfo', 'tool_trigger'), ['class' => 'btn btn-primary']);

        // Historic urls.
        $rerunhisturl = new \moodle_url('/admin/tool/trigger/history.php');
        $rerunhisturl->params(['action' => 'rerunstephist', 'id' => $step->id,
            'sesskey' => sesskey(), 'workflow' => $workflow, 'run' => $run]);

        $rerunandnexthisturl = new \moodle_url('/admin/tool/trigger/history.php');
        $rerunandnexthisturl->params(['action' => 'rerunnexthist', 'id' => $step->id,
            'sesskey' => sesskey(), 'workflow' => $workflow, 'run' => $run]);

        $rerunandfinishhisturl = new \moodle_url('/admin/tool/trigger/history.php');
        $rerunandfinishhisturl->params(['action' => 'rerunfinishhist', 'id' => $step->id,
            'sesskey' => sesskey(), 'workflow' => $workflow, 'run' => $run]);

        $executenexthisturl = new \moodle_url('/admin/tool/trigger/history.php');
        $executenexthisturl->params(['action' => 'executenexthist', 'id' => $step->id,
            'sesskey' => sesskey(), 'workflow' => $workflow, 'run' => $run]);

        // Current urls.
        $reruncurrurl = new \moodle_url('/admin/tool/trigger/history.php');
        $reruncurrurl->params(['action' => 'rerunstepcurr', 'id' => $step->id,
            'sesskey' => sesskey(), 'workflow' => $workflow, 'run' => $run]);

        $rerunandnextcurrurl = new \moodle_url('/admin/tool/trigger/history.php');
        $rerunandnextcurrurl->params(['action' => 'rerunnextcurr', 'id' => $step->id,
            'sesskey' => sesskey(), 'workflow' => $workflow, 'run' => $run]);

        $rerunandfinishcurrurl = new \moodle_url('/admin/tool/trigger/history.php');
        $rerunandfinishcurrurl->params(['action' => 'rerunfinishcurr', 'id' => $step->id,
            'sesskey' => sesskey(), 'workflow' => $workflow, 'run' => $run]);

        $executenextcurrurl = new \moodle_url('/admin/tool/trigger/history.php');
        $executenextcurrurl->params(['action' => 'executenextcurr', 'id' => $step->id,
            'sesskey' => sesskey(), 'workflow' => $workflow, 'run' => $run]);

        $btn .= \html_writer::start_div('btn-group');
        $btn .= $stepdetailslbtn;
        $btn .= \html_writer::start_tag('button', ['class' => 'btn btn-primary dropdown-toggle dropdown-toggle-split',
            'data-toggle' => 'dropdown', 'aria-haspopup' => 'true', 'aria-expanded' => 'false']);
        $btn .= \html_writer::span('toggle-dropdown', 'sr-only');
        $btn .= \html_writer::end_tag('button');
        $btn .= \html_writer::start_div('dropdown-menu dropdown-menu-right');
        $btn .= \html_writer::tag('h6', get_string('actionscurrent', 'tool_trigger'),
            ['class' => 'dropdown-header']);
        $btn .= \html_writer::link($reruncurrurl, get_string('rerunstep', 'tool_trigger'),
            ['class' => 'dropdown-item']);
        $btn .= \html_writer::link($rerunandnextcurrurl, get_string('rerunstepandnext', 'tool_trigger'),
            ['class' => 'dropdown-item']);
        $btn .= \html_writer::link($rerunandfinishcurrurl, get_string('rerunstepandfinish', 'tool_trigger'),
            ['class' => 'dropdown-item']);
        $btn .= \html_writer::link($executenextcurrurl, get_string('executenext', 'tool_trigger'),
            ['class' => 'dropdown-item']);
        $btn .= \html_writer::div('', 'dropdown-divider');
        $btn .= \html_writer::tag('h6', get_string('actionshistoric', 'tool_trigger'),
            ['class' => 'dropdown-header']);
        $btn .= \html_writer::link($rerunhisturl, get_string('rerunstep', 'tool_trigger'),
            ['class' => 'dropdown-item']);
        $btn .= \html_writer::link($rerunandnexthisturl, get_string('rerunstepandnext', 'tool_trigger'),
            ['class' => 'dropdown-item']);
        $btn .= \html_writer::link($rerunandfinishhisturl, get_string('rerunstepandfinish', 'tool_trigger'),
            ['class' => 'dropdown-item']);
        $btn .= \html_writer::link($executenexthisturl, get_string('executenext', 'tool_trigger'),
            ['class' => 'dropdown-item']);
        $btn .= \html_writer::end_div() . \html_writer::end_div();

        return $btn;
    }

    public function run_actions_button($run, $statusonly = false) {
        $btn = '';
        $viewurl = new \moodle_url('/admin/tool/trigger/history.php', array('run' => $run->id, 'workflow' => $run->workflowid));
        $viewbtn = \html_writer::link($viewurl, get_string('viewdetailedrun', 'tool_trigger'), ['class' => 'btn btn-primary']);

        // For deferred and cancelled runs, show only details.
        if ($statusonly) {
            return $viewbtn;
        }

        $reruncurrurl = new \moodle_url('/admin/tool/trigger/history.php',
            ['action' => 'rerunworkflowcurr', 'id' => $run->id, 'workflow' => $run->workflowid, 'sesskey' => sesskey()]);
        $rerunhisturl = new \moodle_url('/admin/tool/trigger/history.php',
            ['action' => 'rerunworkflowhist', 'id' => $run->id, 'workflow' => $run->workflowid, 'sesskey' => sesskey()]);

        $btn .= \html_writer::start_div('btn-group');
        $btn .= $viewbtn;
        $btn .= \html_writer::start_tag('button', ['class' => 'btn btn-primary dropdown-toggle dropdown-toggle-split',
            'data-toggle' => 'dropdown', 'aria-haspopup' => 'true', 'aria-expanded' => 'false']);
        $btn .= \html_writer::span('toggle-dropdown', 'sr-only');
        $btn .= \html_writer::end_tag('button');
        $btn .= \html_writer::start_div('dropdown-menu dropdown-menu-right');
        $btn .= \html_writer::tag('h6', get_string('actionscurrent', 'tool_trigger'),
            ['class' => 'dropdown-header']);
        $btn .= \html_writer::link($reruncurrurl, get_string('rerunworkflow', 'tool_trigger'),
            ['class' => 'dropdown-item']);
        $btn .= \html_writer::div('', 'dropdown-divider');
        $btn .= \html_writer::tag('h6', get_string('actionshistoric', 'tool_trigger'),
            ['class' => 'dropdown-header']);
        $btn .= \html_writer::link($rerunhisturl, get_string('rerunworkflow', 'tool_trigger'),
            ['class' => 'dropdown-item']);
        $btn .= \html_writer::end_div() . \html_writer::end_div();

        return $btn;
    }

    /**
     * This function outputs the rerun all historic errors button.
     *
     * @return void
     */
    private function rerun_all_historic_button($workflowid) {
        $url = new \moodle_url('/admin/tool/trigger/history.php',
            ['action' => 'rerunallhist', 'sesskey' => sesskey(), 'id' => $workflowid, 'workflow' => $workflowid]);
        $btn = new \single_button($url, get_string('rerunallhist', 'tool_trigger'), 'get', true);
        echo $this->render($btn);
    }

    /**
     * This function outputs the rerun all current errors button.
     *
     * @return void
     */
    private function rerun_all_current_button($workflowid) {
        $url = new \moodle_url('/admin/tool/trigger/history.php',
            ['action' => 'rerunallcurr', 'sesskey' => sesskey(), 'id' => $workflowid, 'workflow' => $workflowid]);
        $btn = new \single_button($url, get_string('rerunallcurr', 'tool_trigger'), 'get', true);
        echo $this->render($btn);
    }
}
