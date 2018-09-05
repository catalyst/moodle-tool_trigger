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

define(
  ['jquery', 'core/str', 'core/modal_factory', 'core/modal_events','core/templates', 'core/ajax', 'core/fragment',
      'core/notification'],
        function ($, Str, ModalFactory, ModalEvents, Templates, ajax, Fragment, Notification) {

            /**
             * Module level variables.
             */
            var ImportWorkflow = {};
            var contextid;
            var modalObj;
            var spinner = '<p class="text-center">'
                + '<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i><span class="sr-only">Loading...</span>'
                + '</p>';

            /**
             * Updates the body of the modal window.
             *
             * @private
             */
            function updateModalBody() {
                var formdata = {};
                var params = {jsonformdata: JSON.stringify(formdata)};
                modalObj.setBody(spinner);
                modalObj.setBody(Fragment.loadFragment('tool_trigger', 'new_import_form', contextid, params));
            }

            /**
             * Updates Moodle form with selected information.
             * @private
             */
            function processModalForm(e) {
                e.preventDefault(); // Stop modal from closing.

                // Form data.
                var fileform = modalObj.getRoot().find('form').serialize();
                modalObj.setBody(spinner);

                // Submit form via ajax to do server side validation.
                ajax.call([{
                    methodname: 'tool_trigger_process_import_form',
                    args: {
                        jsonformdata: JSON.stringify(fileform)
                    },
                }])[0].done(function(responsejson) {
                    var responseobj = JSON.parse(responsejson);

                    if (responseobj.errorcode == 'success') {
                        // Validation succeeded! Update the list of workflows.
                        location.reload(true);  // We're lazy so we'll just reload the page.
                    } else {
                        Object.keys(responseobj.message).forEach(function(key) {
                            Notification.addNotification({
                                message: responseobj.message[key],
                                type: 'error'
                            });
                        });
                    }

                    modalObj.hide(); // Hide the modal.

                }).fail(function() {
                    // Validation failed!
                    Notification.addNotification({
                        message: Str.get_string('errorimportworkflow', 'tool_trigger'),
                        type: 'error'
                    });

                    modalObj.hide(); // Hide the modal.
                });
            }

            /**
             * Initialise the class.
             *
             * @public
             */
            ImportWorkflow.init = function(context) {
                // Save the context ID in a closure variable.
                contextid = context;

                // Get the Title String.
                Str.get_string('importmodaltitle', 'tool_trigger').then(function(title) {
                    // Create the Modal.
                    ModalFactory.create({
                        type: ModalFactory.types.SAVE_CANCEL,
                        title: title,
                        body: spinner,
                        large: true
                    }, $('[name=importbtn]'))
                    .done(function(modal) {
                        modalObj = modal;
                        modalObj.getRoot().on(ModalEvents.save, processModalForm);
                        modalObj.getRoot().on(ModalEvents.hidden, updateModalBody);
                        updateModalBody();
                    });
                });

            };

            return ImportWorkflow;
        });
