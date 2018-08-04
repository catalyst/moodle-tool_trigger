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

namespace tool_trigger\steps\actions;

defined('MOODLE_INTERNAL') || die;

/**
 * Action step that just does a var_dump to the logs.
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT, 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logdump_action_step extends base_action_step {

    /**
     * The fields suplied by this step.
     *
     * @var array
     */
    private static $stepfields = array(
        'vardump',
    );

    public function form_definition_extra(
                                        $form,
                                        $mform,
                                        $customdata) {
        $mform->addElement('html', self::get_step_desc());
    }

    public static function get_step_desc() {
        return get_string('step_action_logdump_desc', 'tool_trigger');
    }

    public static function get_step_name() {
        return get_string('step_action_logdump_name', 'tool_trigger');
    }

    public function execute($step, $trigger, $event, $stepresults) {
        mtrace('logdump step "' . $step->name);
        ob_start();
        var_dump($event->get_data());
        var_dump($event->get_logextra());
        var_dump($stepresults);
        $o = ob_get_contents();
        ob_end_clean();
        mtrace($o);
        $stepresults['vardump'] = $o;
        return [true, $stepresults];
    }

    /**
     * Get a list of fields this step provides.
     *
     * @return array $stepfields The fields this step provides.
     */
    public static function get_fields() {
        return self::$stepfields;

    }

}