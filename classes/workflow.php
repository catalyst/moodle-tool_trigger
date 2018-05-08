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
 * Workflow class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger;

defined('MOODLE_INTERNAL') || die();

/**
 * Worklfow class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workflow {

    /**
     * @var \stdClass The rule object form database.
     */
    public $workflow;

    /**
     * @var int The workflow ID.
     */
    public $id;

    /**
     * @var string The event name.
     */
    public $event;

    /**
     * @var int Is this workflow async.
     */
    public $async;

    /**
     * @var int Is this workflow enabled.
     */
    public $active;

    /**
     * @var int Is this workflow in draft mode.
     */
    public $draft;

    /**
     * @var int When was this workflow last triggered.
     */
    public $lasttriggered;

    /**
     * @var string
     */
    public $descriptiontext;

    /**
     * @var int
     */
    public $descriptionformat;

    /**
     * Constructor.
     *
     * @param \stdClass $rule A rule object from database.
     */
    public function __construct($workflow) {
        $this->workflow = $workflow;
        $this->id = $workflow->id;
        $this->event = $workflow->event;
        $this->async = $workflow->async;
        $this->active = $workflow->enabled;
        $this->draft = $workflow->draft;
        $this->lasttriggered = $workflow->timetriggered;

        $description = json_decode($workflow->description);
        $this->descriptiontext = $description->text;
        $this->descriptionformat = $description->format;
    }

    /**
     * Get name of workflow.
     *
     * @param \stdClass $context
     * @returns \string
     */
    public function get_name($context) {
        return format_text($this->workflow->name, FORMAT_HTML, array('context' => $context));
    }

    /**
     * Get description of workflow.
     *
     * @param \stdClass $context
     * @returns \string
     */
    public function get_description($context) {
        return format_text($this->descriptiontext, $this->descriptionformat, array('context' => $context));
    }
}
