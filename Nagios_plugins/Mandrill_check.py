#!/usr/bin/python
######################################################
# Name : count_mandrill_tags.py                      #
######################################################
import sys
import json
import pprint
import time
import calendar
import os
import os.path
from mandrill import Mandrill
from datetime import datetime
if len(sys.argv) != 4:
 print "USAGE : ", sys.argv[0], "<sender add> <delay in days> <nag_check_interval>"
 print "E.g.", sys.argv[0], "xyz@pqr.com 3 5"
 print "Check https://mandrillapp.com/login/ for more details"
 sys.exit(3)

m = Mandrill('xxxxxxx') # Put your mandrill key here
s = []
t = []
check_interval = sys.argv[3]
recurrence = 60/int(check_interval) * 24 
# Check Interval if 5 mins and Incident is once in 24 Hrs, then Execution 60/5 = 12, In 24 Hrs 288.
sent_c = 0
reject_c = 0
tag_cv_submit = 0
tag_gateway = 0
tag_job_alerts = 0
tag_userreg_admin_created = 0
count = 0

s.append(sys.argv[1])
search_result = m.messages.search(limit=1000)
GMTcts = calendar.timegm(time.gmtime())
GMTcts_5 = GMTcts - 300 # Execution in 5 mins i.e. 300 Sec
recurrence = recurrence * int(sys.argv[2])
for i in search_result:
 if ( i['sender'] == ''.join(s) and (i['ts'] < GMTcts and i['ts'] > GMTcts_5)):
   if (i['state'] == "sent" ):
     sent_c = sent_c + 1
   if (i['state'] == "rejected" ):
     reject_c = reject_c + 1
FName = "/tmp/check_count/"+ sys.argv[1]
if (not os.path.isfile(FName)):
 print "%s Doen't exist" %(FName)
 fw = open(FName, 'w')
 fw.write("0")
 fw.close()
file_rd_val = open(FName,'r').read()
if ( int(file_rd_val) < recurrence ):
 if reject_c <> 0:
   file_rd_val = 0
   STATUS = "OK"
   ecode = 0
 else:
   file_rd_val = int(file_rd_val) + int(1)
   STATUS = "OK"
   ecode = 0
else:
 if ( int(file_rd_val) >= recurrence ):
  if reject_c <> 0:
    file_rd_val = 0
    STATUS = "OK"
    ecode = 0
  else:
    STATUS = "WARNING : No Reject Count is present"
    ecode = 1

f_write = open(FName,'w')
f_write.write(str(file_rd_val))
f_write.close()

if STATUS == "OK":
   print STATUS, "Sent_Vol-%d Reject_Vol-%d | Sent_Vol=%d;0; Reject_Vol=%d;0;" %(sent_c,reject_c,sent_c,reject_c)
else:
   print STATUS, sys.argv[1]

sys.exit(ecode)
