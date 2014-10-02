#!/usr/bin/env python
# -*- coding: utf-8 -*-

db = None
import price, mailer, sys

EXECUTING = 'EXECUTING'
EXECUTED = 'EXECUTED'
FAILED = 'FAILED'
NOTIFIED = 'NOTIFIED'

def note(order):
	return "%s %.3f BTC at $%.2f" % (order['action'], order['amount'], order['at_price'])

def getEmailTitle(order, result):
	if result == EXECUTED:
		return "You just %s Bitcoin on Coinbase! (%s)" % ('bought' if order['action']=='BUY' else 'sold', note(order))
	elif result == FAILED:
		return "Could not automatically %s Bitcoin on your Coinbase account (%s)" % ('buy' if order['action']=='BUY' else 'sell', note(order))
	elif result == NOTIFIED:
		return "Your %s order on Coinbase skipped (%s)" % ('buy' if order['action']=='BUY' else 'sell', note(order))
	
def getEmailText(order, result):
	text = 'Hi there!\n\n'
	if result == EXECUTED:
		text += 'The system just executed your order to %s Bitcoin.\n\n' % ('buy' if order['action']=='BUY' else 'sell')
	elif result == FAILED:
		text += "It seems that the attempt to process this order was not successful. Remember - what you see on your Coinbase account is what counts: if you don't see your order there, it indeed wasn't (and won't be) processed and you should do it manually. If you happen to see this order on Coinbase, you can ignore this email.\n\n"
	elif result == NOTIFIED:
		text += "This is a notice that this order <b>would</b> be normally processed, but was skipped because the automatic ordering engine is temporarily off. I will not attempt to process this order again.\n\n<b>Automatic ordering of your future orders will be working again soon.</b>\n\n"
	text += 'You can check the details at <a href="http://coinbaseorders.com/">http://coinbaseorders.com/</a>.\n\n'
	text += 'Coinbase Orders is a free service. Please consider a small donation on 13ejFczTyMsdZQHkrfVEfiGY8RLD2rDs9i, alternatively <a href="http://coinbaseorders.com/homepage/donate">click here to get donation QR code</a>.\n\n'
	text += 'I appriciate your help!\n\nTom'
	return text.replace("\n","\n<br>")

def processOrder(order, user):
	
	print "[processing %s," % note(order),
	sys.stdout.flush()
	
	if "nvimp" not in user.email:
		print "debug-skip]",
		sys.stdout.flush()
		return #test on my own accounts first
	
	db.orders[order.id] = {'status': EXECUTING}
	db.commit()
	
	try:
		# from coinbase import CoinbaseAccount
		# user['coinbase_access_token'], user['coinbase_refresh_token'], user['coinbase_expire_time']
		# coinbaseAccount = CoinbaseAccount(...)
		# result = coinbaseAccount.buy/sell(order['amount'])
		# if result is OK:
		# 	print "success]",
		# 	sys.stdout.flush()
		# 	db.orders[order.id] = {'status': EXECUTED}
		# 	db.commit()
		# 	mailer.send(user.email, getEmailTitle(order, EXECUTED), getEmailText(order, EXECUTED))
		# else:
		# 	raise CoinbaseTradeException(order, result)
		
		print "notice]",
		sys.stdout.flush()
		db.orders[order.id] = {'status': NOTIFIED}
		db.commit()
		mailer.send(user.email, getEmailTitle(order, NOTIFIED), getEmailText(order, NOTIFIED))
	except:
		#todo: log the problem
		print "fail]",
		sys.stdout.flush()
		db.orders[order.id] = {'status': FAILED}
		db.commit()
		mailer.send(user.email, getEmailTitle(order, FAILED), getEmailText(order, FAILED))
	

def processBuyAt(currentBuyPrice):
	triggeredOrders = db(\
		(db.orders.status=='ACTIVE') & \
		(db.orders.action=='BUY') & \
		(currentBuyPrice<=db.orders.at_price) & \
		(db.orders.at_price!=None) & \
		(db.orders.amount!=None) \
	)
	for order in triggeredOrders.select():
		user = db.users[order.user_id]
		if "nvimp" not in user.email:
			print "#",
			sys.stdout.flush()
			continue
		if user.coinbase_access_token is not None:
			totalBuyPrice = price.getBuyPrice(order.amount) #todo - ask for price with users tokens
			if totalBuyPrice is not None and totalBuyPrice <= order.at_price * order.amount:
				processOrder(order, user)

def processSellAt(currentSellPrice):
	triggeredOrders = db(\
		(db.orders.status=='ACTIVE') & \
		(db.orders.action=='SELL') & \
		(currentSellPrice>=db.orders.at_price) & \
		(db.orders.at_price!=None) & \
		(db.orders.amount!=None) \
	)
	for order in triggeredOrders.select():
		user = db.users[order.user_id]
		if "nvimp" not in user.email:
			print "#",
			sys.stdout.flush()
			continue
		if user.coinbase_access_token is not None:
			totalSellPrice = price.getSellPrice(order.amount) #todo - ask for price with users tokens
			if totalSellPrice is not None and totalSellPrice >= order.at_price * order.amount:
				processOrder(order, user)

