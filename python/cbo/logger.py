#!/usr/bin/env python
# -*- coding: utf-8 -*-

#
# Exception Logger
#
# Author: Tom James Holub
#
#
# USAGE:
# import rpc_logger, sys
# ...
# logFileName = rpc_logger.log(sys.exc_info(), dictionaryToDump)
#

import sys, pprint, traceback, string, os, datetime, inspect, json
import configurator, mailer
import thread

ERRORDIR = configurator.getPath('log') + 'error/'

def formatDirectoryName(unformatted):
	valid_chars = "-_.(): %s%s" % (string.ascii_letters, string.digits)
	sanitized = ''.join(c for c in unformatted if c in valid_chars)
	return 'E_' + sanitized.replace(' ','_')[:100] + '/'

def log(exc_info, dump = [], stdout = ''):
	
	content = [
		'############################################',
		'# Stack Trace ##############################',
		'############################################',
		''
	];
	exc_type, exc_value, exc_traceback = exc_info
	content += traceback.format_exception(exc_type, exc_value, exc_traceback)
	content += [
		'',
		'#############',
		'# Data ######',
		'#############',
		''
	]
	content += [pprint.pformat(dump)]
	content += [
		'',
		'',
		'#############',
		'# Stdout ####',
		'#############',
		''
	]
	content += [str(stdout)]
	
	exceptionLogDir = formatDirectoryName(str(exc_type.__name__) +':'+ str(exc_value))
	
	
	if not os.path.exists(ERRORDIR + exceptionLogDir):
		os.makedirs(ERRORDIR + exceptionLogDir)
		body = "<br/>".join(content).replace(' ', '&nbsp;').replace('\n','<br/>')
		sendEmailNotification('New unhandled exception ('+str(exc_type.__name__) +': '+ str(exc_value)[:100]+')', body)
	
	now = datetime.datetime.now()
	fileName = now.strftime("exception_%Y-%m-%d_%H:%M:%S:") + str(now.microsecond).zfill(6) + ".log"
	sys.stderr.write("!!! Exception -> " + exceptionLogDir + fileName + "\n")
		
	with open(ERRORDIR + exceptionLogDir + fileName, "w") as myfile:
		myfile.write("\n".join(content))
		
	return exceptionLogDir + fileName

def sendEmailNotification(subject, body):
	mailer.send(configurator.getDebugEmail(), subject, body)

class ThreadStdout:
	
	def __init__(self):
		self.thread_specific_outputs = {}
		self.MAIN_THREAD = thread.get_ident()

	def write(self, value):
		if thread.get_ident() != self.MAIN_THREAD: # put all children threads stdouts into a separate storage
			if thread.get_ident() not  in self.thread_specific_outputs:
				self.thread_specific_outputs[thread.get_ident()] = value
			else:
				self.thread_specific_outputs[thread.get_ident()] += value
		else: # print all main thread stdouts the normal way
			sys.__stdout__.write(value)

	def flush(self):
		sys.__stdout__.flush()

	def clean(self):
		if thread.get_ident() in self.thread_specific_outputs:
			del self.thread_specific_outputs[thread.get_ident()]
		
	def get(self):
		return self.thread_specific_outputs[thread.get_ident()]
