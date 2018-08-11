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
 * Admin tool trigger web service external functions and service definitions.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Define the web service functions to install.
$functions = array(
        'tool_trigger_step_by_type' => array(
                'classname'   => 'tool_trigger_external',
                'methodname'  => 'step_by_type',
                'classpath'   => 'admin/tool/trigger/externallib.php',
                'description' => 'Returns all steps matching supplied type',
                'type'        => 'read',
                'capabilities'  => 'tool/trigger:manageworkflows',
                'ajax' => true
        ),
        'tool_trigger_validate_form' => array(
            'classname'   => 'tool_trigger_external',
            'methodname'  => 'validate_form',
            'classpath'   => 'admin/tool/trigger/externallib.php',
            'description' => 'Checks to see if a form contains valid data',
            'type'        => 'read',
            'capabilities'  => 'tool/trigger:manageworkflows',
            'ajax' => true
        ),
        'tool_trigger_process_import_form' => array(
            'classname'   => 'tool_trigger_external',
            'methodname'  => 'process_import_form',
            'classpath'   => 'admin/tool/trigger/externallib.php',
            'description' => 'Creates a new workflow.',
            'type'        => 'write',
            'capabilities'  => 'tool/trigger:manageworkflows',
            'ajax' => true
        ),
);
