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
 * Process trigger system events.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\json;

defined('MOODLE_INTERNAL') || die();

/**
 * Process trigger system events.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class json_export {

    /**
     * Mime type for the downloaded file.
     *
     * @var string $mimetype
     */
    private $mimetype = 'application/json';

    /**
     * File name of the file.
     *
     * @var string $filename
     */
    private $filename;

    /**
     * Workflow name.
     *
     * @var string $workflowname
     */
    private $workflowname;

    /**
     *
     * @var unknown
     */
    private $workflowrecord;

    /**
     * Class constructor
     *
     * @param object $workflowrecord The workflow object with step data.
     */
    public function __construct($workflowrecord) {
        $this->workflowname = $workflowrecord->name;
        $this->workflowrecord = $workflowrecord;
    }


    /**
     * Output file headers to initialise the download of the file.
     *
     * @param string $filename The name of the file.
     */
    private function send_header($filename) {
        global $CFG;

        if (defined('BEHAT_SITE_RUNNING') || PHPUNIT_TEST) {
            // For text based formats - we cannot test the output with behat if we force a file download.
            return;
        }
        if (is_https()) { // HTTPS sites - watch out for IE! KB812935 and KB316431.
            header('Cache-Control: max-age=10');
            header('Pragma: ');
        } else { // Normal http - prevent caching at all cost.
            header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
            header('Pragma: no-cache');
        }
        header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        header("Content-Type: $this->mimetype\n");
        header("Content-Disposition: attachment; filename=\"$filename\"");
    }

    /**
     * Set the filename for the JSON file.
     *
     * @param string $workflowname The ID of the workflow.
     * @param int $now  The Unix timestamp to use in the file name.
     */
    private function get_filename($workflowname, $now=null) {

        if (!$now) {
            $now = time();
        }

        $filename = str_replace(' ', '_', $workflowname);
        $filename = clean_filename($filename);
        $filename .= clean_filename('_' . gmdate("Ymd_Hi", $now));
        $filename .= '.json';

        return $filename;
    }

    /**
     * Takes the workflow record and converts it into JSON.
     *
     * @param object $workflowrecord The workflow record.
     * @return string $workflowjson The record converted to JSON.
     */
    private function get_workflow_json($workflowrecord) {
        $workflowjson = json_encode($workflowrecord);

        return $workflowjson;
    }

    /**
     * Prints the JSON.
     *
     * @param string $workflowjson
     */
    private function print_json_data ($workflowjson) {
        echo $workflowjson;
    }

    /**
     * Download the JSON file.
     */
    public function download_file() {
        $workflowname = $this->workflowname;
        $filename = $this->get_filename($workflowname);
        $workflowjson = $this->get_workflow_json($this->workflowrecord);

        $this->send_header($filename);
        $this->print_json_data($workflowjson);
        exit;
    }

}