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
 * Renderable class for manage rules page.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\output\manageworkflows;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Renderable class for manage rules page.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderable extends \table_sql implements \renderable {

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

        $this->set_attribute('id', 'tooltriggerrules_table');
        $this->set_attribute('class', 'tooltrigger managerules generaltable generalbox');
        $this->define_columns(array(
                'name',
                'description',
                'eventname',
                'active',
                'numsteps',
                'lasttriggered',
                'manage'
        ));
        $this->define_headers(array(
                get_string('name', 'tool_trigger'),
                get_string('description', 'tool_trigger'),
                get_string('event', 'tool_trigger'),
                get_string('active', 'tool_trigger'),
                get_string('numsteps', 'tool_trigger'),
                get_string('lasttriggered', 'tool_trigger'),
                get_string('manage', 'tool_trigger'),
            )
        );
        $this->pagesize = $perpage;
        $systemcontext = \context_system::instance();
        $this->context = $systemcontext;
        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(true);
        $this->is_downloadable(false);
        $this->define_baseurl($url);
        $this->workflowid = 0;
    }

    /**
     * Generate content for name column.
     *
     * @param \tool_trigger\workflow $workflow rule object
     * @return string html used to display the column field.
     */
    public function col_name(\tool_trigger\workflow $workflow) {
        return $workflow->get_name($this->context);
    }

    /**
     * Generate content for description column.
     *
     * @param \tool_trigger\workflow $workflow rule object
     * @return string html used to display the column field.
     */
    public function col_description(\tool_trigger\workflow $workflow) {
        return $workflow->get_description($this->context);
    }

    /**
     * Generate content for "last triggered" column.
     *
     * @param \tool_trigger\workflow $workflow rule object
     * @return string html used to display the column field.
     */
    public function col_lasttriggered(\tool_trigger\workflow $workflow) {
        if (!empty($workflow->lasttriggered)) {
            return userdate($workflow->lasttriggered);
        }
    }

    /**
     * Generate content for eventname column.
     *
     * @param \tool_trigger\workflow $workflow rule object
     * @return string html used to display the column field.
     */
    public function col_eventname(\tool_trigger\workflow $workflow) {
        if (class_exists($workflow->event, true) && is_subclass_of($workflow->event, '\core\event\base')) {
            return $workflow->event::get_name();
        } else {
            return $workflow->event;
        }
    }

    /**
     * Generate content for manage column.
     *
     * @param \tool_trigger\workflow $workflow rule object
     * @return string html used to display the manage column field.
     */
    public function col_manage(\tool_trigger\workflow $workflow) {
        global $OUTPUT, $CFG;

        $manage = '';

        $editurl = new \moodle_url($CFG->wwwroot. '/admin/tool/trigger/edit.php', array('workflowid' => $workflow->id));
        $icon = $OUTPUT->render(new \pix_icon('t/edit', get_string('editrule', 'tool_trigger')));
        $manage .= \html_writer::link($editurl, $icon, array('class' => 'action-icon'));

        // The user should always be able to copy the rule if they are able to view the page.
        $copyurl = new \moodle_url($CFG->wwwroot. '/admin/tool/trigger/manageworkflow.php',
                array('workflowid' => $workflow->id, 'action' => 'copy', 'sesskey' => sesskey()));
        $icon = $OUTPUT->render(new \pix_icon('t/copy', get_string('duplicaterule', 'tool_trigger')));
        $manage .= \html_writer::link($copyurl, $icon, array('class' => 'action-icon'));

        $deleteurl = new \moodle_url($CFG->wwwroot. '/admin/tool/trigger/manageworkflow.php', array('workflowid' => $workflow->id,
                'action' => 'delete', 'sesskey' => sesskey()));
        $icon = $OUTPUT->render(new \pix_icon('t/delete', get_string('deleterule', 'tool_trigger')));
        $manage .= \html_writer::link($deleteurl, $icon, array('class' => 'action-icon'));

        $downloadurl = new \moodle_url($CFG->wwwroot. '/admin/tool/trigger/export.php', array('workflowid' => $workflow->id,
            'action' => 'download', 'sesskey' => sesskey()));
        $icon = $OUTPUT->render(new \pix_icon('t/download', get_string('downloadrule', 'tool_trigger')));
        $manage .= \html_writer::link($downloadurl, $icon, array('class' => 'action-icon'));

        return $manage;
    }

    /**
     * Query the reader. Store results in the object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {

        $total = \tool_trigger\workflow_manager::count_workflows();
        $this->pagesize($pagesize, $total);
        $workflows = \tool_trigger\workflow_manager::get_workflows_paginated($this->get_page_start(), $this->get_page_size());
        $this->rawdata = $workflows;
        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars($total > $pagesize);
        }
    }
}
