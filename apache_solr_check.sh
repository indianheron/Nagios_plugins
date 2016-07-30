#!/bin/bash
############################################################################
# This script check the num of records on each machine of mp-solr, one way #
# to check the solr site.						   #
# Developed By : jbhogare                                                  #
############################################################################

# Nagios Constant 
STATE_OK=0
STATE_WARNING=1
STATE_CRITICAL=2
STATE_UNKNOWN=3

if [ $# -lt 2 ]
then
	echo "USAGE: $0 <host_address> <context_path>"
	exit $STATE_UNKNOWN
fi
numOfrecords=$(curl -s -m 3 "http://$1:8080/solr/$2/select/?q=*:*" | awk '/numFound/ {print $8}' | awk -F "=" '{print $2}' | sed 's/"//g')
if [ $numOfrecords -lt "30000" ]
then 
	OUTPUT="Num of records : $numOfrecords"
	STATUS=$STATE_WARNING
elif [ $numOfrecords -le "0" ]
then
	OUTPUT="Num of records : $numOfrecords"
        STATUS=$STATE_CRITICAL
elif [ -z $numOfrecords ]
then
        OUTPUT="Host $1 is not responding"
        STATUS=$STATE_CRITICAL
else
        OUTPUT="Num of records : $numOfrecords"
        STATUS=$STATE_OK
fi

echo $OUTPUT
exit $STATUS
