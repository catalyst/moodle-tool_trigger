[![Build Status](https://travis-ci.org/catalyst/moodle-tool_trigger.svg?branch=master)](https://travis-ci.org/catalyst/moodle-tool_trigger)

# Event Trigger

The Event Trigger allows [Moodle Events](https://docs.moodle.org/dev/Event_2) to be monitored and a *workflow* to be triggered when that event occurs.<br/>
One of the main use cases of this plugin is to allow Moodle events to trigger actions in external systems.

Each workflow is made up of a series of *steps*. Steps can be things like:
* Using event data to *lookup* user and course information
* *Filtering* data based on a set of conditions
* Performing an *action* like sending an email or Posting data to a HTTP endpoint or external API.

The plugin is designed to be extensible and contributions are welcome to extend the available actions.

More configuration documentation can be found at the following link: 

* https://github.com/catalyst/moodle-tool_trigger/wiki

More Information on Moodle events can be found in the Moodle documentation at the following link:

* https://docs.moodle.org/dev/Event_2

## Supported Moodle Versions
This plugin currently supports Moodle:

* 3.4
* 3.5

## Moodle Plugin Installation
The following sections outline how to install the Moodle plugin.

### Command Line Installation
To install the plugin in Moodle via the command line: (assumes a Linux based system)

1. Get the code from GitHub or the Moodle Plugin Directory.
2. Copy or clone code into: `<moodledir>/admin/tool/trigger`
3. Run the upgrade: `sudo -u www-data php admin/cli/upgrade` **Note:** the user may be different to www-data on your system.

### User Interface Installation
To install the plugin in Moodle via the Moodle User Interface:

1. Log into your Moodle as an Administrator.
2. Navigate to: *Site administration > Plugins > Install Plugins*
3. Install plugin from Moodle Plugin directory or via zip upload.

## Plugin Setup
Plugin setup and configuration documentation can be found at the following link: 

* https://github.com/catalyst/moodle-tool_trigger/wiki

## Roadmap

Please see the current GitHub issues for the project roadmap: https://github.com/catalyst/moodle-tool_trigger/issues

# Crafted by Catalyst IT

This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

![Catalyst IT](/pix/catalyst-logo.png?raw=true)


# Contributing and Support

Issues, and pull requests using github are welcome and encouraged! 

https://github.com/catalyst/moodle-webservice_restful/issues

If you would like commercial support or would like to sponsor additional improvements
to this plugin please contact us:

https://www.catalyst-au.net/contact-us
