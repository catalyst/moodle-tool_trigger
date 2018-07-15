# JSON Event Field Fixture Files
TODO: this

## Setup Query Logging in PostgreSQL
`sudo vim /etc/postgresql/9.5/main/postgresql.conf`

log_destination = 'syslog, csvlog'
logging_collector = on # Turnn off to stop
log_statement = mod # comment out to stop
log_directory = '/var/log/pg_log' 

mattp@moodle:~$ sudo mkdir /var/log/pg_log 
mattp@moodle:~$ sudo chmod 777 /var/log/pg_log/
mattp@moodle:~$ sudo service postgresql restart 


sudo grep -rhn 'INSERT INTO bht_tool_trigger_learn_events' /var/log/pg_log/*.csv > ~/events.csv



## Setup Behat Tests
https://docs.moodle.org/dev/Running_acceptance_test

force plugin setting
