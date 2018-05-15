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

namespace tool_trigger\steps\triggers;

defined('MOODLE_INTERNAL') || die;

/**
 * Trigger step that just does a var_dump to the logs.
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT, 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logdump_trigger_step extends base_trigger_step {
    public function form_definition_extra(
                                        $form,
                                        $mform,
                                        $customdata) {
        // TODO: lang string!
        $mform->addElement('html', "This step dumps all workflow data to the cron log.");
    }

    public static function get_step_desc() {
        // TODO: lang string!
        return 'log dump step';
    }

    public static function get_step_name() {
        // TODO: lang string!
        return 'log dump step';
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
        return [true, $stepresults];
    }


}