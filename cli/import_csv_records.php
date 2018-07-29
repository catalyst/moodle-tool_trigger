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
 * This script takes Postgres log files and inserts them
 * into the learnt events table.
 *
 * @package    tool_trigger
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', 1);

require(__DIR__.'/../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

global $DB;

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'source' => '',
        'help' => false
    ),
    array(
        'h' => 'help'
    )
    );

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

// Help information.
if ($options['help'] || !($options['source'])) {
    $help = <<<EOL
Extracts learnt fields for trigger events from the database\n
and makes JSON fixture files containing the extracted fields.

Options:
--source=STRING             Path where the source csv file to import
                            is located.
-h, --help                  Print out this help.

Example:
\$sudo -u www-data /usr/bin/php admin/tool/trigger/cli/export_fields.php\n
EOL;

    echo $help;
    die;
}

// Check source file exists.
$filename = trim($options['source']);
$fp = fopen($filename, 'r');
$count = 0;

if ($fp) {
    // Go through CSV file line by line extracting data and inserting into database.
    while (($data = fgetcsv($fp)) !== false) {
        $parametermatches = array();
        $valuesmatches = array();

        // Only get the 2 fields from the CSV that contain the data we need.
        $gotparameters = preg_match ('/\((.*?)\)/', $data[13], $parametermatches);
        $gotvalues = preg_match ('/parameters\:\s(.*)/', $data[14], $valuesmatches);

        if ($gotparameters && $gotvalues) {
            // Extract the required data from the fetched fields.
            $parameterarray = explode(',', $parametermatches[1]);
            $valuesarray = explode(', $', $valuesmatches[1]);

            $record = new \stdClass();
            for ($i = 0; $i < count($parameterarray); $i++) {

                // Do some final formating and converison on values before insert.
                $value = preg_replace('/\d*\s\=\s/', '', $valuesarray[$i]);
                $value = str_replace('NULL', '', $value);
                $value = str_replace('\'', '', $value);

                // Add field value pair to record object.
                $record->{$parameterarray[$i]} = $value;
            }

            // Insert reocrd into database.
            $DB->insert_record('tool_trigger_learn_events', $record);
            $count++;
        }

    }

    echo 'Processed: ' . $count . ' records' . "\n";
    fclose($fp);
} else {
    echo 'Unable to open file at location: ' . $filename;
    echo "\n";
    exit(1);
}

exit(0);
