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
     * Retrieves the steps serialized to JSON in the stepsjson hidden form field.
     */
    function getParentFormSteps() {
        var stepsjson = $('[name=stepjson]').val();
        var steps = [];
        if (stepsjson !== '') {
            steps = JSON.parse(stepsjson);
        }
        return steps;
    }

    /**
     * Updates the steps stored in the hidden form field
     */
    function setCurrentFormSteps(steps) {
        $('[name=stepjson]').val(JSON.stringify(steps));
        // Set the flag field that indicates there was a change to the steps.
        $('[name=isstepschanged]').val(1);
    }

    /**
     * Updates the body of the modal window.
     *
     * @private
     */
    function updateModalBody() {
        var formdata = {};
        var params = {jsonformdata: JSON.stringify(formdata)};
        modalObj.setBody(spinner);
        modalObj.setBody(Fragment.loadFragment('tool_trigger', 'new_base_form', contextid, params));
    }

    function updateTable(stepData) {
        // Format data for template.

        // Filter out only the fields we want for each step, and make sure the "steporder" values
        // are correct.
        var rows = stepData.map(
            function(step, stepidx) {
                return {
                    type: step.type,
                    name: step.name,
                    step: step.step,
                    steporder: stepidx
                };
            }
        );
        var tableData = {'rows': rows};
        Templates.render(
            'tool_trigger/workflow_steps',
            tableData
        ).then(function(html) {
            $('#steps-table').html(html);
            setupTableHandlers();
        }).fail(function(ex) {
            console.log('Error in updateTable()!', ex);
            // TODO: Deal with this exception (I recommend core/notify exception function for this).
        });
    }

    /**
     * Updates Moodle form with selected information.
     * @private
     */
    function processModalForm(e) {
        e.preventDefault(); // Stop modal from closing.

        // Form data.
        var $stepform = modalObj.getRoot().find('form');
        // Use jQuery().serializeArray() to collect the values of all the form fields.
        // Then convert from its array-of-objects output format into a single object.
        var curstep = $stepform.serializeArray().reduce(
            function(finalobj, field) {

                // Filter out the sesskey and formslib system fields.
                if (field.name !== 'sesskey' && !field.name.startsWith('_qf__')) {
                    finalobj[field.name] = field.value;
                }
                return finalobj;
            },
            {}
        );

        // Add the description string for the stepclass, in order to make later rendering
        // easier...
        curstep['step'] = $('[name=stepclass] option:selected').text();

        // Submit form via ajax to do server side validation.
        ajax.call([{
            methodname: 'tool_trigger_validate_form',
            args: {
                stepclass: curstep['stepclass'],
                jsonformdata: JSON.stringify($stepform.serialize())
            },
        }])[0].done(function(response) {

            // Validation succeeded! Update the parent form's hidden steps data, and update
            // the table.
            var steps = getParentFormSteps();

            if (curstep.steporder >= 0) {
                // If we were editing an existing step, swap it into place in the list.
                steps[curstep.steporder] = curstep;
            } else {
                //If we were creating a new step, add it to the end of the list.
                steps.push(curstep);
                curstep.steporder = steps.length - 1;
            }
            setCurrentFormSteps(steps); // Update steps in hidden form field
            updateTable(steps); // Update table in workflow form.
            modalObj.hide(); // Hide the modal.;

        }).fail(function(response) {

            // Validation failed! Don't close the modal, don't update anything on the parent
            // form.
            renderStepForm(curstep['type'], curstep['stepclass'], '', $stepform.serialize());
        });
    }

    /**
     * Updates the step list in the step modal edit form,
     * with only the steps that correspond to the selected
     * step type.
     *
     * @param array events Array of steps to update selection with.
     */
    function updateStepOptions(events) {

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
        ajax.call([
            { methodname: 'tool_trigger_step_by_type', args: {'steptype': valfilter} },
        ])[0].done(function(response) {
           updateStepOptions(response);
       });
    }

    /**
     * Render the correct form for a particular step (or type of step)
     *
     * @param {string} steptype The step category (triggers, filters, lookups)
     * @param {string} stepclass The step class (\tool_trigger\steps\triggers\http_post_trigger_step, ...)
     * @param {Object} formdefaults Default values to display in a new form
     * @param {string} formsubmission Serialized (via jQuery().serialize()) form submission values to load
     * into the form, when re-displaying a form that has failed validation.
     */
    function renderStepForm(steptype, stepclass, formdefaults, formsubmission) {
        if (formdefaults === undefined) {
            formdefaults = '';
        }
        if (formsubmission === undefined) {
            formsubmission = '';
        }

        modalObj.setBody(spinner);
        modalObj.setBody(
            Fragment.loadFragment(
                'tool_trigger',
                'new_step_form',
                contextid,
                {
                    'steptype' : steptype,
                    'stepclass' : stepclass,
                    'defaults': JSON.stringify(formdefaults),
                    'ajaxformdata': formsubmission
                }
            )
        );
    }

    /**
     *
     */
    function setupModalChangeHandlers() {
        // Add event listener for step type select onchange.
        $('body').on('change', '[name=type]', function() {
            getStepsOfType(this.value);
        });

        // Add event listener for step  select onchange.
        $('body').on('change', '[name=stepclass]', function() {
            steptype = $('[name=type]').val();
            stepclass = this.value;
            renderStepForm(steptype, stepclass);
        });
    }

    function setupTableHandlers() {
        $('.tool-trigger-step-moveup').on('click', function() {
            var steps = getParentFormSteps();

            // Already at the top. Can't move any higher!
            var steporder = $(this).data('steporder');
            if (steporder === 0) {
                return true;
            }

            // Swap this one and the one above it.
            var posup = steporder - 1;
            var posdown = steporder;
            var goesup = steps[posdown];
            var goesdown = steps[posup];
            goesup.steporder = posup;
            goesdown.steporder = posdown;
            steps[posup] = goesup;
            steps[posdown] = goesdown;

            setCurrentFormSteps(steps);
            updateTable(steps);

            return true;
        });
        $('.tool-trigger-step-movedown').on('click', function() {
            var steps = getParentFormSteps();

            // Already at the end. Can't move any further!
            var steporder = $(this).data('steporder');
            if (steporder >= steps.length - 1) {
                return true;
            }

            // Swap this one and the one above it.
            var posup = steporder;
            var posdown = steporder + 1;
            var goesup = steps[posdown];
            var goesdown = steps[posup];
            goesup.steporder = posup;
            goesdown.steporder = posdown;
            steps[posup] = goesup;
            steps[posdown] = goesdown;

            setCurrentFormSteps(steps);
            updateTable(steps);

            return true;
        });
        $('.tool-trigger-step-delete').on('click', function() {
            var steps = getParentFormSteps();

            // Remove it from the array
            var steporder = $(this).data('steporder');
            steps.splice(steporder, 1);
            // Adjust the steporder of all subsequent steps.
            if (steporder <= steps.length) {
                steps.slice(steporder).forEach(
                    function(step) {
                        step.steporder = step.steporder - 1;
                    }
                );
            }

            setCurrentFormSteps(steps);
            updateTable(steps);

            return true;
        });
        $('.tool-trigger-step-edit').on('click', function() {
            modalObj.setBody(spinner);
            modalObj.show();
            var steps = getParentFormSteps();
            var steporder = $(this).data('steporder');
            var step = steps[steporder];

            renderStepForm(
                step['type'],
                step['stepclass'],
                step
            );
        });
    }

    function setupModalTrigger($elements) {
    }

    /**
     * Initialise the class.
     *
     * @public
     */
    StepSelect.init = function(context) {
        // Save the context ID in a closure variable.
        contextid = context;

        //Get the Title String
        Str.get_string('modaltitle', 'tool_trigger').then(function(title) {
            // Create the Modal
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: title,
                body: spinner,
                large: true
            }, $('#id_step_modal_button'))
            .done(function(modal) {
                modalObj = modal;
                modalObj.getRoot().on(ModalEvents.save, processModalForm);
                modalObj.getRoot().on(ModalEvents.hidden, updateModalBody);
                setupModalChangeHandlers();
                updateModalBody();
            });
        });

        // Setup click handlers on the edit/delete icons in the steps table
        setupTableHandlers();
    };
 
    return StepSelect;
});