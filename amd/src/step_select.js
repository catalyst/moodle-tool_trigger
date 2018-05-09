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

    function updateTable(stepData) {
        // Format data for template.

        // We want one row object for each step.
        var rows = stepData.map(
          function(step, stepidx) {
            // Iterate through the array of form elements, and extract the
            // values of the ones we need for the template.
            return step.reduce(
              function(row, field) {
                if (['type', 'name', 'step'].includes(field.name)) {
                  row[field.name] = field.value;
                }
                return row;
              },
              // Put the step's array index into the row object as the "steporder" field.
              {'steporder': stepidx}
            );
          }
        );
        var tableData = {'rows': rows};
        Templates.render(
            'tool_trigger/workflow_steps',
            tableData
        ).then(function(html) {
            $('#steps-table').html(html);
            setupTableHandlers();
            console.log('set up!');
            console.log(tableData);
        }).fail(function(ex) {
            console.log('Error in updateTable()!', ex);
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
        formDataObj.push({'name': 'step', 'value': $('[name=stepclass] option:selected').text()});
        var stepclass = $('[name=stepclass] option:selected').attr('value');

        // Get and update hidden workflow form element.
        var stepsjson = $('[name=stepjson]').val();
        // Keep the original valid value for use if validation fails.
        var originalstepsjson = $('[name=stepjson]').val();

        if (stepsjson !== '') {
            stepsJsonArr = JSON.parse(stepsjson);
        }

        stepsJsonArr.push(formDataObj);
        stepsjson = JSON.stringify(stepsJsonArr);
        $('[name=stepjson]').val(stepsjson);
        // Set the flag field that indicates there was a change to the steps.
        $('[name=isstepschanged]').val(1);
        // Submit form via ajax to do server side validation.
        var promises = ajax.call([{
            methodname: 'tool_trigger_validate_form',
            args: {stepclass: stepclass, jsonformdata: JSON.stringify(formData.serialize())},
        }]);

        promises[0].done(function(response) {
            updateTable(stepsJsonArr); // Update table in workflow form.
            modalObj.hide(); // Hide the modal.;
        });

        promises[0].fail(function(response) {
            // Reset stepsjson with data prior to validation fail.
            $('[name=stepjson]').val(originalstepsjson);
            $steptype = $('[name=type]').val();
            $stepval = stepclass;
            $steptext = $('[name=stepclass] option:selected').text();
            getStepForm($steptype, $stepval, $steptext, formData.serialize());
        });
    }

    /**
     * Updates the step list in the step modal edit form,
     * with only the steps that correspond to the selected
     * step type.
     *
     * @param array events Array of steps to update selection with.
     */
    function updateSteps(events) {

        // First clear the existing options in the select element.
        $('[name=stepclass]').empty().append($('<option>', {
            value: '',
            text : 'Choose...'
        }));

        // Update the select with applicable events.
        $.each(events, function (i, event) {
            $('[name=stepclass]').append($('<option>', {
                value: event.class,
                text : event.name
            }));
        });
    }

    /**
     * Gets a list of filtered steps based on the selected step type.
     * Triggers updating of the form step select element.
     *
     * @param string varfilter The filter area.
     */
    function getStepsOfType(valfilter) {
        var promises = ajax.call([
            { methodname: 'tool_trigger_step_by_type', args: {'steptype': valfilter} },
        ]);

       promises[0].done(function(response) {
           updateSteps(response);
       });
    }

    /**
     * Gets a list of filtered steps based on the selected step type.
     * Triggers updating of the form step select element.
     *
     * @param string varfilter The filter area.
     */
    function getStepForm(steptype, stepval, steptext, data) {
        if (data === undefined) {
            var data = '';
        }

        var formdata = {
                'steptype' : steptype,
                'stepval' : stepval,
                'steptext' : steptext,
                'data' : data
        };

        var params = {jsonformdata: JSON.stringify(formdata)};
        modalObj.setBody(spinner);
        modalObj.setBody(Fragment.loadFragment('tool_trigger', 'new_step_form', contextid, params));
    }

    /**
     *
     */
    function changeHandlers() {
        // Add event listener for step type select onchange.
        $('body').on('change', '[name=type]', function() {
            getStepsOfType(this.value);
        });

        // Add event listener for step  select onchange.
        $('body').on('change', '[name=stepclass]', function() {
            $steptype = $('[name=type]').val();
            $stepval = this.value;
            $steptext = $('[name=stepclass] option:selected').text();
            getStepForm($steptype, $stepval, $steptext);
        });
    }

    function setupTableHandlers() {
        $('.tool-trigger-step-moveup').on('click', function() {
            var steporder = $(this).data('steporder');

            // Already at the top. Can't move any higher!
            if (steporder === 0) {
                return true;
            }
            // Get and update hidden workflow form element.
            var stepsjson = $('[name=stepjson]').val();
            var steps = [];
            if (stepsjson !== '') {
                steps = JSON.parse(stepsjson);
            }
            // Swap this one and the one above it.
            var posup = steporder - 1;
            var posdown = steporder;
            var goesup = steps[posdown];
            var goesdown = steps[posup];
            steps[posup] = goesup;
            steps[posdown] = goesdown;

            $('[name=stepjson]').val(JSON.stringify(steps));
            // Set the flag field that indicates there was a change to the steps.
            $('[name=isstepschanged]').val(1);

            updateTable(steps);

            return true;
        });
        $('.tool-trigger-step-movedown').on('click', function() {
            var steporder = $(this).data('steporder');

            // Get and update hidden workflow form element.
            var stepsjson = $('[name=stepjson]').val();
            var steps = [];
            if (stepsjson !== '') {
                steps = JSON.parse(stepsjson);
            }

            // Already at the end. Can't move any further!
            if (steporder >= steps.length - 1) {
                return true;
            }

            // Swap this one and the one above it.
            var posup = steporder;
            var posdown = steporder + 1;
            var goesup = steps[posdown];
            var goesdown = steps[posup];
            steps[posup] = goesup;
            steps[posdown] = goesdown;

            $('[name=stepjson]').val(JSON.stringify(steps));
            // Set the flag field that indicates there was a change to the steps.
            $('[name=isstepschanged]').val(1);

            updateTable(steps);

            return true;
        });
        // TODO
//        $('.tool-trigger-step-edit').on('click', function() {
//        });
        $('.tool-trigger-step-delete').on('click', function() {
            var steporder = $(this).data('steporder');

            // Get and update hidden workflow form element.
            var stepsjson = $('[name=stepjson]').val();
            var steps = [];
            if (stepsjson !== '') {
                steps = JSON.parse(stepsjson);
            }

            if (steporder < 0 || steporder > steps.length - 1) {
                return true;
            }

            // Remove it from the array
            steps.splice(steporder, 1);

            $('[name=stepjson]').val(JSON.stringify(steps));
            // Set the flag field that indicates there was a change to the steps.
            $('[name=isstepschanged]').val(1);

            updateTable(steps);

            return true;
        });
    }

    /**
     * Initialise the class.
     *
     * @public
     */
    StepSelect.init = function(context) {
        var trigger = $('#id_step_modal_button'); // form button to trigger modal
        contextid = context;

        setupTableHandlers();

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
                changeHandlers();
                updateBody();
            });
        });

    };
 
    return StepSelect;
});