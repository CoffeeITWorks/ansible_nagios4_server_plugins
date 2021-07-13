#!/bin/bash
## Event handler script for executing an NRPE command on a given host when a service# is in CRITICAL and HARD state.

state=$1 # eg. "OK","CRITICAL,"WARNING","UNKNOWN"
statetype=$2 # eg. "SOFT","HARD"
host=$3 # hostaddress of where to execute nrpe command
command=$4 # nrpe command to execute
logfile=/var/log/eventhandler.log # logfile to store executions by this eventhandler

# Date format: "2016-03-29 13:10 CEST"
date=`date +"%Y-%m-%d %H:%M %Z"`
case "$1" in (CRITICAL)
 if [ "$statetype" = "HARD" ] ; then
 /bin/echo -en "$date | ${0##*/} Got state: <$state> and statetype: <$statetype> with command <$command> and argument <$cmdarg> for execution on host <$host>\n" >> $logfile
 /usr/lib/nagios/plugins/check_nrpe -H $host -c $command >> $logfile
 fi
esac
exit 0
