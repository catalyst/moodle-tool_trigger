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
 * This script exports fields to the JSON fixture files.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', 1);

require(__DIR__.'/../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false), array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

// Help information.
if ($options['help']) {
    $help = <<<EOL
Extracts learnt fields for trigger events from the database\n
and makes JSON fixture files containing the extracted fields.

Options:
-h, --help                  Print out this help.

Example:
\$sudo -u www-data /usr/bin/php admin/tool/trigger/cli/export_fields.php\n
EOL;

    echo $help;
    die;
}

print_string('cli_extractfields', 'tool_trigger');
echo "\n";

// Get the events we have stored fields for.
$learnprocess = new \tool_trigger\learn_process();
$eventnames = $learnprocess->get_event_fields_events();
$results = array();
$count = 0;

// Iterrate through each event getting fields.
foreach ($eventnames as $eventname) {
    // Convert fields from JSON and add to array indexed by event name.
    $fieldsjson = $learnprocess->get_event_fields_json($eventname);
    $fields = json_decode($fieldsjson->jsonfields, true);
    $results[$eventname] = $fields;
    $count++;
}

// Convert results into JSON.
$resultsjson = json_encode($results, JSON_PRETTY_PRINT);

print_string('cli_writingfile', 'tool_trigger', $count);
echo "\n";

// Write results to file.
$filename = $CFG->dirroot . '/admin/tool/trigger/db/fixtures/event_fields.json';
$fp = fopen($filename, 'w');

if ($fp) {
    fwrite($fp, $resultsjson);
    fclose($fp);
    print_string('cli_filesummary', 'tool_trigger', $filename);
    echo "\n";
} else {
    print_string('cli_filefail', 'tool_trigger', $filename);
    echo "\n";
}

exit(0);
