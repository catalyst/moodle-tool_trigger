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
 * HTTP Post trigger step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\triggers;

use tool_trigger\steps\base\base_step;

defined('MOODLE_INTERNAL') || die;

/**
 * HTTP Post trigger step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class http_post_trigger_step extends base_trigger_step {

    protected $url;
    protected $headers;
    protected $params;

    public function __construct($jsondata) {
        parent::__construct($jsondata);
        if ($this->data) {
            $this->url = $this->data->url;
            $this->headers = $this->data->httpheaders;
            $this->params = $this->data->httpparams;
        }
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_name() {
        return get_string('httpposttriggerstepname', 'tool_trigger');
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_desc() {
        return get_string('httpposttriggerstepdesc', 'tool_trigger');
    }

    /**
     * @param $step
     * @param $trigger
     * @param $event
     * @param $previousstepresult - result of previousstep to include in processing this step.
     * @return array if execution was succesful and the response from the execution.
     */
    public function execute($step, $trigger, $event, $previousstepresult) {
        $c = new \curl();
        $headers = \tool_trigger\workflow_manager::fill_in_datafield_placeholders(
            $this->headers,
            $event,
            $previousstepresult
        );

        // Need to send the headers as an array.
        $headers = explode("\n", str_replace("\r\n", "\n", $headers));
        $c->setHeader($headers);

        // urlencode the values of any substitutions being placed into the URL
        // or the POST params
        $urlencodecallback = function($v, $k) { return urlencode($v); };

        $url = \tool_trigger\workflow_manager::fill_in_datafield_placeholders(
            $this->url,
            $event,
            $previousstepresult,
            $urlencodecallback
        );

        // TODO: This may need some tweaking. The "params" that Moodle sends
        // to curl are via the CURLOPT_POSTFIELDS setting. Which means that
        // it either needs to be a urlencoded string "para1=val1&para2=val2",
        // or it needs to be an associative array. Since we're just taking
        // a block of text from the user, that means they'll need to write that
        // annoying urlencoded string themselves.
        $params = \tool_trigger\workflow_manager::fill_in_datafield_placeholders(
            $this->params,
            $event,
            $previousstepresult,
            $urlencodecallback
        );

        $response = $c->post($url, $params);
        if ($response) {
            $previousstepresult['http_response'] = implode("\n", $c->getResponse());
        }
        return array($response, $previousstepresult);
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {

        // URL.
        $attributes = array('size' => '50', 'placeholder' => 'https://www.example.com/api', 'type' => 'url');
        $mform->addElement('text', 'url', get_string ('httposttriggerurl', 'tool_trigger'), $attributes);
        $mform->setType('url', PARAM_URL);
        $mform->addRule('url', get_string('required'), 'required');
        $mform->addHelpButton('url', 'httposttriggerurl', 'tool_trigger');

        // Headers.
        $attributes = array('cols' => '50', 'rows' => '5');
        $mform->addElement('textarea', 'httpheaders', get_string ('httposttriggerheaders', 'tool_trigger'), $attributes);
        $mform->setType('httpheaders', PARAM_RAW_TRIMMED);
        $mform->addRule('httpheaders', get_string('required'), 'required');
        $mform->addHelpButton('httpheaders', 'httposttriggerheaders', 'tool_trigger');

        // Params.
        $attributes = array('cols' => '50', 'rows' => '5');
        $mform->addElement('textarea', 'httpparams', get_string ('httposttriggerparams', 'tool_trigger'), $attributes);
        $mform->setType('httpparams', PARAM_RAW_TRIMMED);
        $mform->addRule('httpparams', get_string('required'), 'required');
        $mform->addHelpButton('httpparams', 'httposttriggerparams', 'tool_trigger');
    }
}