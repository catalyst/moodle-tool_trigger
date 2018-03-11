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

define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events','core/templates', 'core/ajax', 'core/fragment'],
        function ($, Str, ModalFactory, ModalEvents, Templates, ajax, Fragment) {

    /**
     * Module level variables.
     */
    var StepSelect = {};
    var contextid;
    var modalObj;
    var stepData;
    var spinner = '<p class="text-center"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i><span class="sr-only">Loading...</span></p>';

    /**
     * Updates the body of the modal window.
     *
     * @private
     */
    function updateBody() {
        var formdata = {};
        var params = {jsonformdata: JSON.stringify(formdata)};
        modalObj.setBody(spinner);
        modalObj.setBody(Fragment.loadFragment('tool_trigger', 'new_base_form', contextid, params));
    }

    function updateTable(tableData) {
        console.log(tableData);
        Templates.render('tool_trigger/workflow_steps', tableData).then(function(html) {
            $('#steps-table').html(html);
            }).fail(function(ex) {
                // TODO: Deal with this exception (I recommend core/notify exception function for this).
            });
    }

    /**
     * Updates Moodle form with slected video information.
     * @private
     */
    function processForm(e) {
        e.preventDefault(); // Stop modal from closing.
        var stepsJsonArr = [];

        // Form data.
        var formData = modalObj.getRoot().find('form');
        var formDataObj = formData.serializeArray();

        // Get and update hidden workflow form element
        var stepsjson = $('[name=stepjson]').val();
        console.log(stepsjson);
        if (stepsjson !== '') {
            stepsJsonArr = JSON.parse(stepsjson);
        }
        stepsJsonArr.push(formDataObj);
        stepsjson = JSON.stringify(stepsJsonArr);
        $('[name=stepjson]').val(stepsjson);

        // TODO: Submit form via ajax to do server side validation.

        // Update table in workflow form.
        updateTable(stepsJsonArr);

        modalObj.hide(); // Hide the modal.
    }

    /**
     * Initialise the class.
     *
     * @public
     */
    StepSelect.init = function(context) {
        var trigger = $('#id_step_modal_button'); // form button to trigger modal
        contextid = context;

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
                modalObj.getRoot().on(ModalEvents.save, processForm);
                modalObj.getRoot().on(ModalEvents.hidden, updateBody);
                updateBody();
            });
        });

    };
 
    return StepSelect;
});