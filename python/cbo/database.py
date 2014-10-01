#!/usr/bin/env python
# -*- coding: utf-8 -*-

import sys, MySQLdb
sys.path.append("./web2py")
from gluon.dal import DAL, Field
import time
import _mysql_exceptions
from datetime import datetime
import configurator


class ConnectionManager():
	
	def __init__(self, connectionString):
		self.connectionString = connectionString
		
	def getConnection(self, migrate='disable'):

		if migrate not in ['fake','enable','disable']:
			raise ValueError('ConnectionManager.getConnection(): Expected enable|disable|fake in migrate parameter')
			
		# we are trying up to 10 times because there might be several dead connections in a row
		for i in [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]:
			try:
				# last two attempts, without connection pooling
				db = DAL(self.connectionString, folder=configurator.getPath('db'), \
					pool_size = 50 if i < 9 else 0, \
					lazy_tables = True if migrate == 'disable' else False, \
					migrate_enabled = False if migrate == 'disable' else True, \
					fake_migrate_all = True if migrate == 'fake' else False, \
				)
				break
			except _mysql_exceptions.OperationalError, err:
				if err[0] not in [2006]: #other exception than [mysql gone away]
					raise
				time.sleep(0.2)
		
		self.structure(db)
		return db
	
	def structure(self, db):
				
		# users
		db.define_table('users', \
			Field('role', 'string', required=True, notnull=True), \
			Field('email', 'string', required=True, notnull=True), \
			Field('email_confirmation', 'string', required=True, notnull=True), \
			Field('random_hash', 'string', required=True, notnull=True), \
			Field('username', 'string'), \
			Field('password', 'string', required=True, notnull=True), \
			Field('coinbase_access_token', 'text', default=None), \
			Field('coinbase_refresh_token', 'text', default=None), \
			Field('coinbase_expire_time', 'integer', default=None), \
			Field('date_registered', 'datetime', required=True, notnull=True), \
		)

		# orders
		db.define_table('orders', \
			Field('user_id', 'integer', required=True, notnull=True), \
			Field('status', 'string', required=True, notnull=True), \
			Field('action', 'string', required=True, notnull=True), \
			Field('amount', 'float', required=True, notnull=True), \
			Field('amount_currency', 'string', required=True, notnull=True), \
			Field('date_created', 'datetime', required=True, notnull=True), \
			Field('date_cancel', 'datetime', default=None), \
			Field('date_edited', 'datetime', default=None), \
		)

		# values
		db.define_table('values', \
			Field('group', 'string', required=True, notnull=True), \
			Field('name', 'string', required=True, notnull=True), \
			Field('value', 'string', required=True, notnull=True), \
			Field('updated', 'datetime', required=True, notnull=True), \
		)
