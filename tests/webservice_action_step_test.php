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
 * Test of the webservice action
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

class tool_trigger_webservice_action_step_testcase extends advanced_testcase {


    /**
     * Test getting webservice form.
     * This is a special case that instead of an array of external objects we get one external object.
     */
    public function test_get_webservice_form_single_object() {
        global $DB;

        // Function to get form for, we use a name because the id's can change.
        $functionid = $DB->get_field('external_functions', 'id', array('name' => 'mod_resource_get_resources_by_courses'));


        // Get the form to use in the test.
        $class = new ReflectionClass('tool_trigger\steps\base\base_form');
        $property = $class->getProperty('_form');
        $property->setAccessible(true);

        $baseform = new tool_trigger\steps\base\base_form();
        $mform = $property->getValue($baseform);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\steps\actions\webservice_action_step', 'get_webservice_form');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(
            new tool_trigger\steps\actions\webservice_action_step,
            $functionid,
            $mform);  // Get result of invoked method.

        ob_start();
        $mform->display();
        $html = ob_get_contents();
        ob_end_clean();

        $this->assertContains('name="_courseids"', $html);
    }

    //  TODO:  Add webservice form test for manual enrol users testcase.
    /**
     * Test getting webservice form with array of external objects.
     */
    public function test_get_webservice_form_mixed_object() {
        global $DB;

        // Function to get form for, we use a name because the id's can change.
        $functionid = $DB->get_field('external_functions', 'id', array('name' => 'enrol_manual_enrol_users'));


        // Get the form to use in the test.
        $class = new ReflectionClass('tool_trigger\steps\base\base_form');
        $property = $class->getProperty('_form');
        $property->setAccessible(true);

        $baseform = new tool_trigger\steps\base\base_form();
        $mform = $property->getValue($baseform);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\steps\actions\webservice_action_step', 'get_webservice_form');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(
                new tool_trigger\steps\actions\webservice_action_step,
                $functionid,
                $mform);  // Get result of invoked method.

                ob_start();
                $mform->display();
                $html = ob_get_contents();
                ob_end_clean();

                $this->assertContains('name="_roleid"', $html);
                $this->assertContains('name="_userid"', $html);
                $this->assertContains('name="_courseid"', $html);
                $this->assertContains('name="_timestart"', $html);
    }

    /**
     * Test getting the webservice form elements for the enrol_manual_enrol_users webservice.
     *
     * We test the same method for a few different webservices to make sure our logic
     * works in all cases.
     */
    public function test_get_webservice_form_elements_enrol_manual_enrol_users() {

        // Function to get form elements for.
        $function = new \stdClass();
        $function->id = 391;
        $function->name = 'enrol_manual_enrol_users';
        $function->classname = 'enrol_manual_external';
        $function->methodname = 'enrol_users';
        $function->classpath = 'enrol/manual/externallib.php';
        $function->component = 'enrol_manual';
        $function->capabilities = 'enrol/manual:enrol';
        $function->services = '';

        $functioninfo = \external_api::external_function_info($function);
        $paramdesc = $functioninfo->parameters_desc->keys['enrolments'];

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\steps\actions\webservice_action_step', 'get_webservice_form_elements');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(
                new tool_trigger\steps\actions\webservice_action_step,
                $paramdesc);  // Get result of invoked method.

        // Assert that the structure returned is what we need.
        $this->assertEquals('int', $proxy['roleid']->type);
        $this->assertEquals('int', $proxy['userid']->type);
        $this->assertEquals('int', $proxy['courseid']->type);
        $this->assertEquals('int', $proxy['timestart']->type);
        $this->assertEquals('int', $proxy['timeend']->type);

    }

    /**
     * Test getting the webservice form elements for the core_user_create_users webservice.
     *
     * We test the same method for a few different webservices to make sure our logic
     * works in all cases.
     */
    public function test_get_webservice_form_elements_core_user_create_users() {

        // Function to get form elements for.
        $function = new \stdClass();
        $function->id = 391;
        $function->name = 'core_user_create_users';
        $function->classname = 'core_user_external';
        $function->methodname = 'create_users';
        $function->classpath = 'user/externallib.php';
        $function->component = 'moodle';
        $function->capabilities = 'moodle/user:create';
        $function->services = '';

        $functioninfo = \external_api::external_function_info($function);
        $paramdesc = $functioninfo->parameters_desc->keys['users'];

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\steps\actions\webservice_action_step', 'get_webservice_form_elements');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(
                new tool_trigger\steps\actions\webservice_action_step,
                $paramdesc);  // Get result of invoked method.

        // Assert that the structure returned is what we need.
        $this->assertEquals('bool', $proxy['createpassword']->type);
        $this->assertEquals('username', $proxy['username']->type);
        $this->assertEquals('auth', $proxy['auth']->type);
        $this->assertEquals('alphanumext', $proxy['customfields']['type']->type);
        $this->assertEquals('raw', $proxy['preferences']['value']->type);
    }

    /**
     * Test getting the webservice form elements for the core_user_create_users webservice.
     *
     * We test the same method for a few different webservices to make sure our logic
     * works in all cases.
     */
    public function test_create_webservice_form() {

        // Set up the elements to generate the form for.
        $element1 = new \external_value('text', 'The field desc for help text for type text',2, '', 1);
        $element2 = new \external_value('bool', 'The field desc for help text for type bool', 1, '', 1);
        $element3 = new \external_value('username', 'The field desc for help text for type username', 1, '', 1);
        $element4 = new \external_value('auth', 'The field desc for help text for type auth', 0, 'manual', 1);
        $element5 = new \external_value('raw', 'The field desc for help text for type raw', 2, '', 1);
        $element6 = new \external_value('notags', 'The field desc for help text for type notags', 1, '', 1);
        $element7 = new \external_value('raw_trimmed', 'The field desc for help text for type trimmed', 1, '', 1);
        $element8 = new \external_value('int', 'The field desc for help text for type int', 2, '', 1);
        $element9 = new \external_value('alpha', 'The field desc for help text for type alpha', 2, '', 1);
        $element10 = new \external_value('timezone', 'The field desc for help text for type timezone', 2, '', 1);
        $element11 = new \external_value('plugin', 'The field desc for help text for type plugin', 0, 'gregorian', 2);
        $element12 = new \external_value('theme', 'The field desc for help text for type theme', 2, '', 1);

        $element13a = new \external_value('alphanumext', 'The field desc for help text for type type', 1, '', 1);
        $element13b = new \external_value('raw', 'The field desc for help text for type value', 1, '', 1);
        $element13 = array (
                'type' => $element13a,
                'value' => $element13b
        );

        $elements = array(
                'element1name' => $element1,
                'element2name' => $element2,
                'element3name' => $element3,
                'element4name' => $element4,
                'element5name' => $element5,
                'element6name' => $element6,
                'element7name' => $element7,
                'element8name' => $element8,
                'element9name' => $element9,
                'element10name' => $element10,
                'element11name' => $element11,
                'element12name' => $element12,
                'element13name' => $element13,
        );

        // Get the form to use in the test.
        $class = new ReflectionClass('tool_trigger\steps\base\base_form');
        $property = $class->getProperty('_form');
        $property->setAccessible(true);

        $baseform = new tool_trigger\steps\base\base_form();
        $mform = $property->getValue($baseform);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('tool_trigger\steps\actions\webservice_action_step', 'create_webservice_form');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(
                new tool_trigger\steps\actions\webservice_action_step,
                $elements,
                $mform);  // Get result of invoked method.

        ob_start();
        $baseform->display();
        $o = ob_get_contents();
        ob_end_clean();

        error_log($o);

    }

}