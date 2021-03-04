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
 * Plugin strings are defined here.
 *
 * @package     tool_trigger
 * @category    string
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Event Trigger';
$string['pluginname_help'] = 'Event Triggering for Moodle';

$string['action'] = 'Action';
$string['actionscurrent'] = 'Current configuration';
$string['actionshistoric'] = 'Historic configuration';
$string['active'] = 'Active';
$string['addworkflow'] = 'Add new trigger workflow';
$string['areatomonitor'] = 'Area to monitor';
$string['areatomonitor_help'] = 'The Moodle area that contains the event to trigger workflow on.';
$string['assigncohortactionstepname'] = 'Assign cohort';
$string['assigncohortactionstepdesc'] = 'A step that allows for assigning a user to a cohort';
$string['availablefields'] = 'Available fields';
$string['badsteptype'] = 'Incorrect step type';
$string['badstepclass'] = 'Incorrect step class name';
$string['cachedef_eventsubscriptions'] = 'Tool Trigger event subscription cache';
$string['cleanupsettings'] = 'Clean up settings';
$string['cleanupsettingsdesc'] = 'The following settings control the cleanup tasks for this plugin.';
$string['cli_extractfields'] = 'Extracting fields for learnt events from database...';
$string['cli_filefail'] = 'Failed to write file: {$a}';
$string['cli_filesummary'] = 'File written to: {$a}';
$string['cli_writingfile'] = 'Writing {$a} event field definitions to file...';
$string['cohortidfield'] = 'Cohort ID';
$string['core'] = 'Core';
$string['debounce'] = 'Debounce';
$string['debounce_desc'] = 'The debounce step is a special step that queues up the workflow to be run after a certain period of time, using only the latest instance of the workflow to occur in the period, with a period reset occuring at each new workflow instance trigger.';
$string['debouncecontext'] = 'Debounce match fields';
$string['debouncecontext_help'] = 'The fields to match in an event to start a debounce. All selected fields must match in order to be debounced.';
$string['debounceduration'] = 'Duration to debounce events';
$string['deferred'] = 'Deferred';
$string['deleterule'] = 'Delete rule';
$string['deletestep'] = 'Delete step';
$string['description'] = 'Description';
$string['downloadrule'] = 'Download rule';
$string['draft'] = 'Draft';
$string['draftmode'] = 'Draft mode';
$string['draftmode_help'] = 'Use draft mode to test workflow with firing triggers.';
$string['duplicaterule'] = 'Duplicate rule';
$string['duplicatedworkflowname'] = '{$a} (copy)';
$string['editrule'] = 'Edit rule';
$string['editsettings'] = 'Workflow settings';
$string['editstep'] = 'Edit step';
$string['editworkflow'] = 'Edit trigger workflow';
$string['emailsubject'] = 'Subject';
$string['emailsubject_help'] = 'The text to use in the subject of the e-mail';
$string['emailto'] = 'To';
$string['emailto_help'] = 'Who to send the email to';
$string['emailcontent'] = 'Content';
$string['emailcontent_help'] = 'The content to use in the email';
$string['emailactionstepname'] = 'Email';
$string['emailactionstepdesc'] = 'A step to allow an e-mail to be sent';
$string['event'] = 'Event';
$string['eventdescription'] = 'Event description';
$string['eventfields'] = 'Event fields';
$string['eventid'] = 'Event ID';
$string['eventtomonitor'] = 'Event to monitor';
$string['eventtomonitor_help'] = 'The Moodle event to trigger workflow on.';
$string['erroreditstep'] = 'Something went wrong while attempting to save the workflow step. Please try again.';
$string['errorimportworkflow'] = 'Something went wrong while importing the workflow. Please try again.';
$string['errorsavingworkflow'] = 'Something went wrong while attempting to save the workflow. Please try again.';
$string['errorstep'] = 'Errored step {$a}';
$string['executenext'] = 'Execute following step';
$string['executenextconfirm'] = 'Are you sure you wish to execute following step? It will execute based on the data from this step.';
$string['expectedresponse'] = 'Expected response code';
$string['expectedresponse_help'] = 'Enter the HTTP Response code you expect to receive from the step. Any other response header will cause the step to error.';
$string['failedstep'] = 'Failed step {$a}';
$string['filter'] = 'Filter';
$string['historyduration'] = 'Duration to store trigger instances';
$string['historydurationdesc'] = 'This is the duration to store detailed trigger instances. After this period, it is no longer possible to rerun steps of the instance, or view step data.';
$string['historysettings'] = 'Workflow history settings';
$string['historysettingsdesc'] = 'These settings provide control over how the history of a workflow is stored.';
$string['httpostactionurl'] = 'URL';
$string['httpostactionurl_help'] = 'The URL to post the data to.';
$string['httpostactionheaders'] = 'Headers';
$string['httpostactionheaders_help'] = 'The requests headers to send.';
$string['httpostactionparams'] = 'Parameters';
$string['httpostactionparams_help'] = 'The parameters to send with the request.';
$string['httppostactionstepname'] = 'HTTP Post';
$string['httppostactionstepdesc'] = 'A step to allow Moodle workflows to send data to a HTTP/S endpoint.';
$string['importmodaltitle'] = 'Import workflow from file';
$string['importworkflow'] = 'Import a workflow';
$string['inputprefixuser'] = 'Prefix used for user lookup';
$string['inputprefixrole'] = 'Prefix used for role lookup';
$string['invalidjson'] = 'The workflow import file contains invalid JSON and could not be imported';
$string['invalidversion'] = 'The workflow import file is not valid with this version of the plugin';
$string['jsonencode'] = 'JSON encode parameters';
$string['jsonencode_help'] = 'If enabled values in the Parameter field will be JSON encoded.';
$string['lasttriggered'] = 'Last triggered';
$string['learning'] = 'Enable learning mode';
$string['learning_help'] = 'Learning mode will collect data about available fields for fired events';
$string['learningsettings'] = 'Learning mode settings';
$string['learningsettingsdesc'] = 'Every Moodle event provides a set of fields that can be used as placeholders in the workflow that is triggered for that event.<br/>
This plugin comes with a predefined set of placeholders for some Moodle core events. Enabling learning mode dynamically updates the list of placeholders availble for Moodle events.<br/><br/>
This setting can cause Moodle performance issues and should only be enabled when there are events such as those in plugins that do not have existing
placeholder definitions available. <br/>
Please refer to the plugin documentation for more information.';
$string['lookup'] = 'Lookup';
$string['manage'] = 'Manage';
$string['manageworkflow'] = 'Manage workflow';
$string['messageprovider:tool_trigger'] = 'Event trigger notifications';
$string['modaltitle'] = 'Add workflow step.';
$string['movestepup'] = 'Move step towards start';
$string['movestepdown'] = 'Move step towards end';
$string['name'] = 'Name';
$string['newrunfailed'] = 'Run {$a->prev} was rerun as run {$a->new} and errored.';
$string['noavailablefields'] = 'No fields available, consider turning on learning mode.';
$string['noworkflowfile'] = 'No workflow file found';
$string['numsteps'] = 'Steps';
$string['outputprefix'] = 'Prefix for added fields';
$string['pluginsettings'] = 'Plugin Settings';
$string['prevstep'] = 'Previous step ID';
$string['privacy:path:events'] = '';
$string['privacy:metadata:events'] = 'Data from monitored Moodle events';
$string['privacy:metadata:events:anonymous'] = 'Whether the event was flagged as anonymous';
$string['privacy:metadata:events:eventname'] = 'The event name';
$string['privacy:metadata:events:ip'] = 'The IP address used at the time of the event';
$string['privacy:metadata:events:origin'] = 'The origin of the event';
$string['privacy:metadata:events:other'] = 'Additional information about the event';
$string['privacy:metadata:events:realuserid'] = 'The ID of the real user behind the event, when masquerading a user.';
$string['privacy:metadata:events:relateduserid'] = 'The ID of a user related to this event';
$string['privacy:metadata:events:timecreated'] = 'The time at which the event occurred';
$string['privacy:metadata:events:userid'] = 'The ID of the user who triggered this event';
$string['privacy:path:learnevents'] = '';
$string['privacy:metadata:learnevents'] = 'Data from monitored Moodle learn events';
$string['privacy:metadata:learnevents:anonymous'] = 'Whether the learn event was flagged as anonymous';
$string['privacy:metadata:learnevents:eventname'] = 'The learn event name';
$string['privacy:metadata:learnevents:ip'] = 'The IP address used at the time of the learn event';
$string['privacy:metadata:learnevents:origin'] = 'The origin of the learn event';
$string['privacy:metadata:learnevents:other'] = 'Additional information about the learn event';
$string['privacy:metadata:learnevents:realuserid'] = 'The ID of the real user behind the learn event, when masquerading a user.';
$string['privacy:metadata:learnevents:relateduserid'] = 'The ID of a user related to this learn event';
$string['privacy:metadata:learnevents:timecreated'] = 'The time at which the learn event occurred';
$string['privacy:metadata:learnevents:userid'] = 'The ID of the user who triggered this learn event';
$string['privacy:metadata:runhistory'] = 'This table stores historical data of trigger runs steps, in order to allow for replaying trigger runs.';
$string['privacy:metadata:runhistory:executed'] = 'The time that this step was executed.';
$string['privacy:metadata:runhistory:results'] = 'An encoded set of results from the step execution.';
$string['privacy:metadata:workflowhistory'] = 'This table stores historical data of trigger runs, in order to allow for replaying trigger runs.';
$string['privacy:metadata:workflowhistory:event'] = 'An encoded event entry that triggered the trigger run.';
$string['privacy:metadata:workflowhistory:timecreated'] = 'The time that the trigger run was executed.';
$string['realtime'] = 'Real time';
$string['rerunallcurr'] = 'Rerun all errored runs with current configuration';
$string['rerunallcurrconfirm'] = 'Are you sure you wish to re-run all errored runs using the current workflow configuration?';
$string['rerunallhist'] = 'Rerun all errored runs with historic configuration';
$string['rerunallhistconfirm'] = 'Are you sure you wish to re-run all errored runs using a historic workflow configuration?';
$string['rerunstep'] = 'Re-run step';
$string['rerunstepandfinish'] = 'Re-run step and finish run';
$string['rerunstepandfinishconfirm'] = 'Are you sure you wish to re-run this step, and perform all steps until completing the run?';
$string['rerunstepandnext'] = 'Re-run step and next';
$string['rerunstepandnextconfirm'] = 'Are you sure you wish to re-run this step, and perform the next step in the workflow?';
$string['rerunstepconfirm'] = 'Are you sure you wish to re-run step? This will perform all actions from the step.';
$string['rerunworkflow'] = 'Re-run workflow';
$string['rerunworkflowconfirm'] = 'Are you sure you wish to re-run workflow with current workflow configuration? This will execute all steps based on the original event.';
$string['roleassignactionstepname'] = 'Role assignment';
$string['roleassignactionstepdesc'] = 'Assign a role to a user in the given context';
$string['stepclass'] = 'Step';
$string['stepclass_help'] = 'Choose the step to apply.';
$string['stepdescription'] = 'Step description';
$string['stepdescription_help'] = 'A meaningful description for this step.';
$string['stepid'] = 'Step ID';
$string['sttepidembed'] = 'Step ID: {$a}';
$string['stepmodalbutton'] = 'Add workflow step';
$string['stepname'] = 'Step name';
$string['stepname_help'] = 'The name of this step.';
$string['steprequired'] = 'The workflow must have at least one step.';
$string['stepresults'] = 'Step results:';
$string['steptype'] = 'Step type';
$string['steptype_help'] = 'The type of step to apply.';
$string['step_filter_fail_desc'] = 'A step that always fails. (Mostly useful for testing.)';
$string['step_filter_fail_name'] = 'Fail';
$string['step_lookup_cohort_desc'] = 'This step looks up cohort data for a user.';
$string['step_lookup_cohort_name'] = 'Cohort lookup';
$string['step_lookup_course:privacy:coursedata_desc'] = 'Data about courses, including id, course name, start and end dates, etc.';
$string['step_lookup_course:privacy:categorydata_desc'] = 'Data about course categories, including name, description etc.';
$string['step_lookup_course_desc'] = 'This step looks up data about a course.';
$string['step_lookup_course_name'] = 'Course lookup';
$string['step_lookup_course_courseidfield'] = 'Course id data field';
$string['courseidfield'] = 'Course id data field';
$string['courseidfield_help'] = 'You can use course id as a number or as a filed name from the workflow data';
$string['step_lookup_category_desc'] = 'This step looks up data about a category.';
$string['step_lookup_category_name'] = 'Category lookup';
$string['step_lookup_category_categoryidfield'] = 'Category id data field';
$string['categoryidfield'] = 'Category id data field';
$string['categoryidfield_help'] = 'You can use category id as a number or as a filed name from the workflow data';
$string['step_lookup_user:privacy:userdata_desc'] = 'Personal data about users, such as usernames, names, email addresses, etc.';
$string['step_lookup_user_desc'] = 'This step looks up data about a user.';
$string['step_lookup_user_nodeleted'] = 'Exit if user has been deleted?';
$string['step_lookup_user_name'] = 'User lookup';
$string['step_lookup_user_useridfield'] = 'User id data field';

$string['step_lookup_roles:privacy:userdata_desc'] = 'Data about user roles in various contexts.';
$string['step_lookup_roles_desc'] = 'This step looks up user roles.';
$string['step_lookup_roles_name'] = 'User roles lookup';

$string['step_action_email:privacy:desc'] = 'This plugin may be configured to send emails containing data from Moodle.';
$string['step_action_httppost:privacy:desc'] = 'This plugin may be configured to send HTTP requests to external addresses, containing data from Moodle.';
$string['step_action_logdump_desc'] = 'This step prints the event and workflow steps data to the cron log. (Mostly useful for testing.)';
$string['step_action_logdump_name'] = 'Cron log';
$string['step_action_role_assign_useridfield'] = 'User id data field';
$string['useridfield'] = 'User id data field';
$string['useridfield_help'] = 'You can use user id as a number or as a filed name from the workflow data';
$string['step_action_role_assign_roleidfield'] = 'Role id data field';
$string['rerunerrors'] = 'All error runs have been rerun.';
$string['roleidfield'] = 'Role id data field';
$string['roleidfield_help'] = 'You can use role id as a number or as a filed name from the workflow data';
$string['runid'] = 'Run ID';
$string['runpassed'] = 'Passed step {$a}';
$string['runpassednonum'] = 'Passed';
$string['runstatus'] = 'Run status';
$string['step_action_role_assign_contextidfield'] = 'Context id data field';
$string['stepnotfound'] = 'Step not found';
$string['stepnumber'] = 'Step Number';
$string['contextidfield'] = 'Context id data field';
$string['contextidfield_help'] = 'You can use context id as a number or as a filed name from the workflow data';
$string['taskcleanup'] = 'Delete old processed events';
$string['taskcleanuphistory'] = 'Delete historical trigger data';
$string['taskemptyhistoryconfig'] = 'Error: missing configuration for history duration. Exiting...';
$string['tasklearn'] = 'Learn about the fields in stored events.';
$string['tasklearnstart'] = 'Starting event field extraction processing...';
$string['taskprocessworkflows'] = 'Process workflows scheduled task.';
$string['triggerhistory'] = 'History';
$string['triggernumber'] = 'Trigger Number';
$string['triggernumberembed'] = 'Trigger Number: {$a}';
$string['trigger:manageworkflows'] = 'Create and configure automatic event-triggered workflows';
$string['timeexecuted'] = 'Time executed';
$string['timetocleanup'] = 'Time to cleanup old events';
$string['timetocleanup_help'] = 'This setting sets the time sucessfully executed workflows remain in the Moodle database prior to being removed.';
$string['workflowactive'] = 'Workflow active';
$string['workflowactive_help'] = 'Only active workflows will be processed when an event is triggered.';
$string['workflowrealtime'] = 'Real time processing';
$string['workflowrealtime_help'] = 'When enabled this workflow will be triggered synchronously as part of the event. Use caution when enabling as long running workflows will block the user interface.';
$string['workflowcopysuccess'] = 'Workflow successfully duplicated';
$string['workflowdebug'] = 'Debug mode';
$string['workflowdebug_help'] = 'Debug mode stores historical data about this workflow, for use in refining the workflow.';
$string['workflowdeleteareyousure'] = 'Are you sure you want to delete the workflow "{$a}"?';
$string['workflowdeletesuccess'] = 'Workflow successfully deleted';
$string['workflowdescription'] = 'Description';
$string['workflowdescription_help'] = 'A short description of this workflows purpose.';
$string['workflowid'] = 'Workflow ID: {$a}';
$string['workflowimported'] = 'Workflow successfully imported';
$string['workflowfile'] = 'Workflow file';
$string['workflowname'] = 'Name';
$string['workflowname_help'] = 'The human readable name for this workflow.';
$string['workflowoverview'] = 'Workflow overview';
$string['workflowviewhistory'] = 'View workflow history';
$string['viewtriggerrun'] = 'View run';
$string['viewdetailedrun'] = 'View run details';
$string['viewstepinfo'] = 'View step information';

$string['rolesunassignactionstepname'] = 'Roles unassignment';
$string['rolesunassignactionstepdesc'] = 'A step to unassign all user role assignments.';
