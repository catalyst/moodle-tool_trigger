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
 * Import workflow form class.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Import workflow form class.
 *
 * @package     tool_trigger
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends \moodleform {

    /**
     * Imported worklow JSON files must be at least this version,
     * to be compatible with the import plugin.
     * Update this value when the workflow schema changes.
     *
     * @var int
     */
    private $importversion = 2018082400;

    /**
     * Build form for importing woekflows.
     *
     * {@inheritDoc}
     * @see \moodleform::definition()
     */
    public function definition() {

        $mform = $this->_form;

        // Workflow file.
        $mform->addElement('filepicker', 'userfile', get_string('workflowfile', 'tool_trigger'), null,
            array('maxbytes' => 256000, 'accepted_types' => '.json'));
        $mform->addRule('userfile', get_string('required'), 'required');
    }

    /**
     * Validate uploaded JSON file.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        global $USER;

        $validationerrors = array();

        // Get the file from the filestystem. $files will always be empty.
        $fs = get_file_storage();

        $context = \context_user::instance($USER->id);
        $itemid = $data['userfile'];

        // This is how core gets files in this case.
        if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $itemid, 'id DESC', false)) {
            $validationerrors['nofile'] = get_string('noworkflowfile', 'tool_trigger');
            return $validationerrors;
        }
        $file = reset($files);

        // Check if file is valid JSON.
        $contentjson = $file->get_content();
        $contentobj = json_decode($contentjson);

        if (!$contentobj) {
            $validationerrors['invalidjson'] = get_string('invalidjson', 'tool_trigger');
            return $validationerrors;
        }

        // Check if file version is compatible.
        $versioncompatible = $this->is_version_compatible($contentobj->pluginversion);
        if (!$versioncompatible) {
            $validationerrors['invalidversion'] = get_string('invalidversion', 'tool_trigger');
            return $validationerrors;
        }

        return $validationerrors;
    }

    /**
     * Get the errors returned during form validation.
     *
     * @return array|mixed
     */
    public function get_errors() {
        $form = $this->_form;
        $errors = $form->_errors;

        return $errors;
    }

    /**
     * Check if the version of the workflow import file
     * is compatible with the installed version of the plugin.
     *
     * @param string $pluginversion
     * @return boolean
     */
    private function is_version_compatible($pluginversion) {
        if ((int)$pluginversion < $this->importversion) {
            return false;
        } else {
            return true;
        }
    }
}
