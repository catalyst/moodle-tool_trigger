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

namespace tool_trigger\steps\lookups;

defined('MOODLE_INTERNAL') || die;

/**
 * A lookup step that takes a category ID and adds all data about the category.
 *
 * @package    tool_trigger
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_cat_lookup_step extends base_lookup_step {

    use \tool_trigger\helper\datafield_manager;

    /**
     * The data field to get the category id from.
     * @var string
     */
    private $categoryidfield = null;

    /**
     * The prefix to put before the new fields added to the workflow data.
     *
     * @var string
     */
    private $outputprefix = null;

    /**
     * The fields supplied by this step.
     *
     * @var array
     */
    private static $stepfields = [
        'id',
        'name',
        'idnumber',
        'description',
        'descriptionformat',
        'parent',
        'sortorder',
        'coursecount',
        'visible',
        'visibleold',
        'timemodified',
        'depth',
        'path',
        'theme',
        'contextid'
    ];

    protected function init() {
        $this->categoryidfield = $this->data['categoryidfield'];
        $this->outputprefix = $this->data['outputprefix'];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::execute()
     */
    public function execute($step, $trigger, $event, $stepresults) {
        global $DB;

        $categoryid = (int)$this->categoryidfield;

        if (empty($categoryid)) {
            $allfields = $this->get_datafields($event, $stepresults);

            if (!array_key_exists($this->categoryidfield, $allfields)) {
                throw new \invalid_parameter_exception("Specified category field not present in the workflow data: "
                    . $this->categoryidfield);
            }

            $categoryid = $allfields[$this->categoryidfield];
        }

        $categorydata = $DB->get_record('course_categories', ['id' => $categoryid]);
        $context = \context_coursecat::instance($categoryid, IGNORE_MISSING);

        if (!$categorydata) {
            // If the course has been deleted, there's no point re-running the task.
            return [false, $stepresults];
        }

        if (!$context) {
            // If the context not exist for some reason, there's no point re-running the task.
            return [false, $stepresults];
        }

        $categorydata->contextid = $context->id;

        foreach ($categorydata as $key => $value) {
            $stepresults[$this->outputprefix . $key] = $value;
        }
        return [true, $stepresults];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::form_definition_extra()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        $mform->addElement('text', 'categoryidfield', get_string('step_lookup_category_categoryidfield', 'tool_trigger'));
        $mform->setType('categoryidfield', PARAM_ALPHANUMEXT);
        $mform->addRule('categoryidfield', get_string('required'), 'required');
        $mform->setDefault('categoryidfield', 'course_category');
        $mform->addHelpButton('categoryidfield', 'categoryidfield', 'tool_trigger');

        $mform->addElement('text', 'outputprefix', get_string('outputprefix', 'tool_trigger'));
        $mform->setType('outputprefix', PARAM_ALPHANUMEXT);
        $mform->addRule('outputprefix', get_string('required'), 'required');
        $mform->setDefault('outputprefix', 'category_');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_desc()
     */
    public static function get_step_desc() {
        return get_string('step_lookup_category_desc', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public static function get_step_name() {
        return get_string('step_lookup_category_name', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_privacyfields()
     */
    public static function get_privacyfields() {
        return ['category_lookup_step' => 'step_lookup_course:privacy:categorydata_desc'];
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
