#!/usr/bin/python
# -*- coding: utf-8 -*-

from cbo import configurator, database

connectionManager = database.ConnectionManager(configurator.mysqlString())

print "Creating database structure temp files for web2py DAL...",
db = connectionManager.getConnection(migrate='fake')
db.commit()
db.close()
print "done"
