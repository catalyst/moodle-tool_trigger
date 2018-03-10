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
 * Workflow step select javascript.
 *
 * @module     tool_trigger/workflow
 * @package    tool_trigger
 * @class      Workflow
 * @copyright  2018 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.4
 */

define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/templates', 'core/ajax'],
        function ($, Str, ModalFactory, ModalEvents, Templates, ajax) {

    /**
     * Module level variables.
     */
    var StepSelect = {};
    var modalObj;
    var spinner = '<p class="text-center"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i><span class="sr-only">Loading...</span></p>';

    /**
     * Initialise the class.
     *
     * @public
     */
    StepSelect.init = function() {
        var trigger = $('#id_step_modal_button'); // form button to trigger modal

        //Get the Title String
        Str.get_string('modaltitle', 'tool_trigger').then(function(title) {
            // Create the Modal
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: title,
                body: spinner,
                large: true
            }, trigger)
            .done(function(modal) {
                modalObj = modal;
                modalObj.getRoot().on(ModalEvents.save, updateForm);
                clickHandlers();
                updateBody();
            });
        });

    };
 
    return StepSelect;
});