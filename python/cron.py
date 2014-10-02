#!/usr/bin/python
# -*- coding: utf-8 -*-

import MySQLdb, time, os, threading, sys, signal, threading, _mysql_exceptions
from datetime import datetime, timedelta

from cbo import configurator, database, logger, worker


def signal_handler(signal, frame):
	print "\n[main] SIGINT, starting  CBO cron shutdown"
	cron.stop()
	print "[main] Waiting for last operation to finish..."
signal.signal(signal.SIGINT, signal_handler)

cron = None
delay = 3.0
print "[main] Starting CBO cron on %s" % str(datetime.now())

try:

	connectionManager = database.ConnectionManager(configurator.mysqlString())
	db = connectionManager.getConnection()
	
	cron = worker.Worker()
	cron.setup(delay, db, logger)
	cron.repeat = True
	cron.start()

except Exception, err:
	logger.log(sys.exc_info(), cron.getDebugInfo() if cron is not None else None)
	sys.exit(1)

while cron.running or cron.repeat:
	time.sleep(1)

print "[main] exiting"
sys.exit(0)
