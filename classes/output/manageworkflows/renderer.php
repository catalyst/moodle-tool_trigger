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
 * Renderer class for manage rules page.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\output\manageworkflows;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderer class for manage rules page.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    /**
     * Get html to display on the page.
     *
     * @param renderable $renderable renderable widget
     *
     * @return string to display on the mangerules page.
     */
    protected function render_renderable(renderable $renderable) {
        $o = $this->render_add_button($renderable->workflowid);
        $o .= '&nbsp;';
        $o .= $this->render_import_button();
        $o .= $this->render_table($renderable);

        return $o;
    }

    /**
     * Get html to display on the page.
     *
     * @param renderable $renderable renderable widget
     *
     * @return string to display on the mangerules page.
     */
    protected function render_table(renderable $renderable) {
        $o = '';
        ob_start();
        $renderable->out($renderable->pagesize, true);
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }

    /**
     * Html to add a button for adding a new workflow.
     *
     * @param int $workflowid The workflow the button applies too.
     * @return string html for the button.
     */
    protected function render_add_button($workflowid) {
        global $CFG;

        $button = \html_writer::tag('button', get_string('addworkflow', 'tool_trigger'), ['class' => 'btn btn-primary']);
        $addurl = new \moodle_url($CFG->wwwroot. '/admin/tool/trigger/edit.php', array('workflowid' => $workflowid));
        return \html_writer::link($addurl, $button);
    }

    /**
     * Html to add a button for importing workflow.
     *
     * @return string html for the button.
     */
    protected function render_import_button() {
        global $CFG;

        $button = \html_writer::tag(
            'button',
            get_string('importworkflow', 'tool_trigger'),
            ['class' => 'btn btn-primary', 'name' => 'importbtn']
            );
        return \html_writer::link('#', $button);
    }

    /**
     * Html to add a link to go to the subscription page.
     *
     * @param \moodle_url $manageurl The url of the subscription page.
     *
     * @return string html for the link to the subscription page.
     */
    public function render_subscriptions_link($manageurl) {
        echo \html_writer::start_div();
        $a = \html_writer::link($manageurl, get_string('managesubscriptions', 'tool_trigger'));
        $link = \html_writer::tag('span', get_string('managesubscriptionslink', 'tool_trigger', $a));
        echo $link;
        echo \html_writer::end_div();
    }

    /**
     * Renders the table of steps in the "edit workflow" page.
     *
     * @param string $stepdatajson The JSON-encoded data from the "stepjson" hidden form element.
     * @return string|boolean
     */
    public function render_workflow_steps($stepdatajson) {
        if (!$stepdatajson) {
            return '';
        }
        $stepdata = json_decode($stepdatajson, true);
        if ($stepdata === null || !is_array($stepdata)) {
            return '';
        }
        $wfm = new \tool_trigger\workflow_manager();

        // Extract only the fields needed for the template.
        $rows = [];
        foreach ($stepdata as $step) {
            $row = [
                'name' => $step['name'],
                'steporder' => $step['steporder']
            ];
            if ($wfm->validate_step_class($step['stepclass'])) {
                $row['typedesc'] = $step['stepclass']::get_step_type_desc();
                $row['stepdesc'] = $step['stepclass']::get_step_name();
            } else {
                $row['typedesc'] = '(' . $step['type'] . ')';
                $row['stepdesc'] = '(' . $step['step'] . ')';
            }
            $rows[] = $row;
        }

        return $this->render_from_template('tool_trigger/workflow_steps', ['rows' => array_values($rows)]);
    }
}
