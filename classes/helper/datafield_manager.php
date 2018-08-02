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
 * A lookup step that takes a user's ID and adds standard data about the user.
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT, 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\helper;

defined('MOODLE_INTERNAL') || die;

/**
 * A lookup step that takes a user's ID and adds standard data about the
 * user.
 *
 * @package    tool_trigger
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @copyright  Catalyst IT, 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait datafield_manager {

    protected $datafields = [];

    /**
     * Get the data fields.
     *
     * @param array $event
     * @param array $stepresults
     * @return array $datafields The data fields.
     */
    public function get_datafields($event = null, $stepresults = null) {
        if ($event !== null && $stepresults !== null) {
            $this->update_datafields($event, $stepresults);
        }

        $datafields = $this->datafields;

        return $datafields;
    }

    /**
     * Combines the scalar values from the workflow's event data, and lookup
     * data, into a single associative array with regularized names for each
     * item. These can then be used as a way for workflow authors to identify
     * a particular field of data, and as placeholders for substitution into
     * output templates.
     *
     * These are all combined into a single array, with lookup values overwriting
     * event values. $event->get_data()['other'] values are prefaced with
     * "other_", e.g. $event->get_data()['other']['teacherid'] would become
     * "other_teacherid".
     *
     * NOTE: As tempting as it may be, this cannot be used during the step
     * "edit" phase. That's because events do not include a machine-readable
     * list of their "other" fields. So, we have to look at an instantiated
     * event object in order to get those; and the way to properly instantiate
     * an event is different for every event type!
     *
     * (Also our own workflow steps don't provide a machine-readable list of
     * the fields they add, either. But we could implement that.)
     *
     * @param \core\event\base $event (Read-only) The deserialized event object that triggered this execution
     * @param array $stepresults (Read-Write) Data aggregated from the return values of previous steps in
     * the workflow.
     */
    public function update_datafields($event, $stepresults) {
        $newfields = [];
        if (is_array($event->get_data())) {
            $newfields = array_merge($newfields, $event->get_data());
        }
        if (is_array($event->get_logextra())) {
            $newfields = array_merge($newfields, $event->get_logextra());
        }
        if (isset($newfields['other']) && is_array($newfields['other'])) {
            foreach ($newfields['other'] as $key => $value) {
                if (is_scalar($value)) {
                    $newfields["other_{$key}"] = $value;
                }
            }
            unset($newfields['other']);
        }

        foreach ($stepresults as $key => $value) {
            if (is_scalar($value)) {
                $newfields[$key] = $value;
            }
        }

        $this->datafields = $newfields;
    }

    /**
     * Searches a "template" string for placeholders that are surrounded in curly brackets
     * e.g.: {firstname}. If there's a matching data field with the same name, we replace
     * the placeholder with the value of the data field.
     *
     * @param string $templatestr
     * @param \core\event\base $event (Optional) If supplied (along with $stepresults), then update the datafields before rendering.
     * @param array $stepresults If supplied (along with $event), then update the fields before rendering.
     * @param callable $transformcallback An optional callback function to transform each datafield's value
     * before swapping it in. (For example, to urlencode them.) Should have the signature function($value, $fieldname).
     * @return string
     */
    public function render_datafields($templatestr, $event = null, $stepresults = null, $transformcallback = null) {
        if ($event !== null && $stepresults !== null) {
            $this->update_datafields($event, $stepresults);
        }

        // Define a callback function for use with preg_replace_callback(). It'll check
        // each {tag} in the template string to see whether the tag matches the name of
        // one of our datafields. If so, it will replace the {tag} with the value of the
        // datafield; otherwise, it'll leave {tag} in place.
        //
        // (The "use($transformcallback)" syntax tells PHP to let this anonymous function
        // inherit the $transormcallback variable. We need to do this, because we can't
        // pass it as a parameter to the anonymous function since preg_callback is calling
        // it, not us.) ($this gets inherited by the anonymous function directly)
        //
        // TODO: We could get a slight performance improvement by using a normal private
        // function instead of declaring an anonymous function on each call of this
        // method. But there's no non-hacky way to pass $transformcallback in, in that case.
        $callback = function ($matches) use ($transformcallback) {
            if (array_key_exists($matches[1], $this->datafields)) {
                $value = $this->datafields[$matches[1]];

                // If we've been supplied with a $transformcallback function, then
                // pass the datafield value through $transformcallback, and use the
                // return value.
                if (null !== $transformcallback) {
                    $value = $transformcallback($value, $matches[1]);
                }

                return $value;
            } else {
                // No match! Leave the template string in place.
                return $matches[0];
            }
        };

        return preg_replace_callback(
            '/\{([-_A-Za-z0-9]+)\}/u',
            $callback,
            $templatestr
        );
    }
}