#!/usr/bin/env python
# -*- coding: utf-8 -*-

import threading, time, sys
import price, orders

class Worker(threading.Thread):

	def setup(self, delay, db, logger):
		self.db = db
		self.delay = delay
		self.logger = logger
		
		self.orders = orders
		self.orders.db = db
		self.price = price
		self.price.db = db
		
		self.repeat = False
		self.running = False

	def run(self):
		if self.running:
			return
		self.repeat = True
		self.running = True
		while self.repeat:
			
			try:
				
				buyPrice = price.updateBuyPrice()
				sellPrice = price.updateSellPrice()
				print ".",
				sys.stdout.flush()
				orders.processBuyAt(buyPrice)
				orders.processSellAt(sellPrice)

			except Exception, err:
				if err.__class__.__name__ == 'OperationalError' and err[0] == 1213: # deadlock
					print "(DEADLOCK, rollback)",
					self.db.rollback()
					time.sleep(5)
				else:
					self.logger.log(sys.exc_info(), self.getDebugInfo())
			
			time.sleep(self.delay)
		
		self.running = False
	
	def stop(self):
		self.repeat = False

	def getDebugInfo(self):
		return {
			'sys.argv': str(sys.argv),
			'cron.repeat': self.repeat,
			'cron.running': self.running,
			'cron.delay': self.delay,
		}
