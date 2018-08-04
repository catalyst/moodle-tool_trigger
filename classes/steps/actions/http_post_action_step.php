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
 * HTTP Post action step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\actions;

defined('MOODLE_INTERNAL') || die;

/**
 * HTTP Post action step class.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class http_post_action_step extends base_action_step {

    use \tool_trigger\helper\datafield_manager;

    protected $url;
    protected $headers;
    protected $params;

    /**
     * The fields suplied by this step.
     *
     * @var array
     */
    private static $stepfields = array(
        'http_response_status_code',
        'http_response_status_message',
        'http_response_body',
    );

    protected function init() {
        $this->url = $this->data['url'];
        $this->headers = $this->data['httpheaders'];
        $this->params = $this->data['httpparams'];
        $this->jsonencode = $this->data['jsonencode'];
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_name() {
        return get_string('httppostactionstepname', 'tool_trigger');
    }

    /**
     * Returns the step name.
     *
     * @return string human readable step name.
     */
    static public function get_step_desc() {
        return get_string('httppostactionstepdesc', 'tool_trigger');
    }

    private $httphandler = null;

    /**
     * Kinda hacky... unit testing requires us to specify a different http handler for guzzle to use.
     * That's really the only reason we need this method!
     *
     * @param callable $handler
     */
    public function set_http_client_handler($handler) {
        $this->httphandler = $handler;
    }

    /**
     * Instantiate an http client.
     *
     * @return \GuzzleHttp\Client
     */
    public function get_http_client() {
        $clientconfig = [];
        if ($this->httphandler) {
            $clientconfig['handler'] = $this->httphandler;
        }

        return new \GuzzleHttp\Client($clientconfig);
    }

    /**
     * @param $step
     * @param $trigger
     * @param $event
     * @param $stepresults - result of previousstep to include in processing this step.
     * @return array if execution was succesful and the response from the execution.
     */
    public function execute($step, $trigger, $event, $stepresults) {
        global $CFG;
        require_once($CFG->dirroot . '/admin/tool/trigger/guzzle/autoloader.php');

        $this->update_datafields($event, $stepresults);

        $headers = $this->render_datafields($this->headers);
        $headers = explode("\n", str_replace("\r\n", "\n", $headers));
        $headers = \GuzzleHttp\headers_from_lines($headers);

        // ... urlencode the values of any substitutions being placed into the URL
        // or the POST params.
        $urlencodecallback = function($v) {
            return urlencode($v);
        };

        $url = $this->render_datafields($this->url, null, null, $urlencodecallback);

        // TODO: This may need some tweaking. If this is going to just be a normal POST
        // request, then the user has to provide us with something like
        // "val1={tag1}&val2={tag2}&val3=something", which is not great to type.
        if ($this->jsonencode == 0) { // Optionally JSON encode parameters.
            $params = $this->render_datafields($this->params, null, null, $urlencodecallback);
        } else {
            $formparams = $this->render_datafields($this->params, null, null, null);
            parse_str($formparams, $output);
            $params = json_encode($output);
        }

        $request = new \GuzzleHttp\Psr7\Request('POST', $url, $headers, $params);
        $client = $this->get_http_client();

        try {
            $response = $client->send($request);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
        }

        $stepresults['http_response_status_code'] = $response->getStatusCode();
        $stepresults['http_response_status_message'] = $response->getReasonPhrase();
        $stepresults['http_response_body'] = $response->getBody();
        return array(true, $stepresults);
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {

        // URL.
        $attributes = array('size' => '50', 'placeholder' => 'https://www.example.com/api', 'type' => 'url');
        $mform->addElement('text', 'url', get_string ('httpostactionurl', 'tool_trigger'), $attributes);
        // PARAM_URL will reject some templated urls.
        // TODO: Put some validation on this field?
        $mform->setType('url', PARAM_RAW_TRIMMED);
        $mform->addRule('url', get_string('required'), 'required');
        $mform->addHelpButton('url', 'httpostactionurl', 'tool_trigger');

        // Headers.
        $attributes = array('cols' => '50', 'rows' => '2');
        $mform->addElement('textarea', 'httpheaders', get_string ('httpostactionheaders', 'tool_trigger'), $attributes);
        $mform->setType('httpheaders', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('httpheaders', 'httpostactionheaders', 'tool_trigger');

        // Params.
        $attributes = array('cols' => '50', 'rows' => '5');
        $mform->addElement('textarea', 'httpparams', get_string ('httpostactionparams', 'tool_trigger'), $attributes);
        $mform->setType('httpparams', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('httpparams', 'httpostactionparams', 'tool_trigger');

        // Params as JSON.
        $mform->addElement('advcheckbox', 'jsonencode', get_string ('jsonencode', 'tool_trigger'),
                'Enable', array(), array(0, 1));
        $mform->setType('jsonencode', PARAM_INT);
        $mform->addHelpButton('jsonencode', 'jsonencode', 'tool_trigger');
        $mform->setDefault('jsonencode', 0);
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_privacy_metadata()
     */
    public static function add_privacy_metadata($collection, $privacyfields) {
        return $collection->add_external_location_link(
            'http_post_action_step',
            $privacyfields,
            'step_action_httppost:privacy:desc'
        );
    }

    /**
     * Get a list of fields this step provides.
     *
     * @return array $stepfields The fields this step provides.
     */
    public static function get_fields() {
        return self::$stepfields;

    }
}