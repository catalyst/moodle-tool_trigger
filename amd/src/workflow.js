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
 * Workflow edit form javascript.
 *
 * @module     tool_trigger/workflow
 * @package    tool_trigger
 * @class      Workflow
 * @copyright  2018 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.4
 */

define(['jquery', 'core/ajax'], function($, ajax) {

    /**
     * The wrokflow JS object.
     */
    var Workflow = {};

    /**
     * Updates the event list in the workflow edit form,
     * with only the events that correspond to the selected
     * Moodle area.
     *
     * @param array events Array of events to update selection with.
     */
    function updateEventList(events) {

        // First clear the existing options in the select element.
        $('[name=eventtomonitor]').empty().append($('<option>', {
            value: '',
            text : 'Choose...'
        }));

        // Update the select with applicable events.
        $.each(events, function (i, event) {
            $('[name=eventtomonitor]').append($('<option>', {
                value: event.id,
                text : event.name
            }));
        });
    }

    /**
     * Gets a list of filtered events based on the selected area.
     * Triggers updating of the form event select element.
     *
     * @param string varfilter The filter area.
     */
    function getEvents(valfilter) {
        var promises = ajax.call([
            { methodname: 'tool_trigger_get_all_eventlist', args: {} },
        ]);

       promises[0].done(function(response) {
           var events = response.filter(function(el){
               return el.id.startsWith("\\" + valfilter);
           });
           updateEventList(events);
       });
    }

    /**
     * The init function for the module.
     */
    Workflow.init = function () {
        // Add event listener for area select onchange.
        $('[name=areatomonitor]').change(function() {
                getEvents(this.value);
        });
    };

    return Workflow;
});
