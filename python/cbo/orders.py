#!/usr/bin/env python
# -*- coding: utf-8 -*-

db = None

import sys, json
from datetime import datetime

import price, mailer, configurator
from coinbase import CoinbaseAccount, CoinbaseTransfer, CoinbaseError
from crypt import encrypt, decrypt

KEY = configurator.getEncryptionKey()

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

def makeCoinbaseAuthString(user):
	expiration = datetime.fromtimestamp(user['coinbase_expire_time']).isoformat()+'Z'
	access = decrypt(KEY, user['coinbase_access_token'])
	refresh = decrypt(KEY, user['coinbase_refresh_token'])
	#print "[", access, refresh, "]"
	
	credentials = {
		"_module": "oauth2client.client",
		"access_token": access, 
		"refresh_token": refresh,
		"token_expiry": expiration,
#		"token_response": {
#			"access_token": access, 
#			"token_type": "bearer",
#			"expires_in": 7200,
#			"refresh_token": refresh,
#			"scope": "all"
#		},
		"invalid": False,
		"token_uri": "https://www.coinbase.com/oauth/token", 
		"client_id": "73980e1a5f0e2b17a7780129b505e95a6504387962a59364c04649791452cd72", 
		"client_secret": "2abe2652bd5ccb725c2574592f0cb1b60c6ab2fdc8c0631525b733f21c64dba1", 
#		"revoke_uri": "https://accounts.google.com/o/oauth2/revoke",
		"user_agent": None,
	}
	return json.dumps(credentials)

def processOrder(order, user):
	
	print "[processing %s," % note(order),
	sys.stdout.flush()
	db.orders[order.id] = {'status': EXECUTING}
	db.commit()
	
	try:
		if "nvimp" in user.email: #test it first
			
			coinbaseAccount = CoinbaseAccount(oauth2_credentials=makeCoinbaseAuthString(user))
			if coinbaseAccount.token_expired:
				newCredentials = coinbaseAccount.refresh_oauth()
				db.users[user.id] = {
					"coinbase_expire_time": encrypt(KEY, newCredentials.token_expiry),
					"coinbase_access_token": encrypt(KEY, newCredentials.access_token),
					"coinbase_refresh_token": encrypt(KEY, newCredentials.refresh_token),
				}
			
			if order['action'] == 'BUY':
				result = coinbaseAccount.buy_btc(order['amount'])
			elif order['action'] == 'SELL':
				result = coinbaseAccount.sell_btc(order['amount'])
			else:
				raise ValueError
			
			if isinstance(result, CoinbaseTransfer):
				print "success]",
				sys.stdout.flush()
				db.orders[order.id] = {'status': EXECUTED}
				db.commit()
				mailer.send(user.email, getEmailTitle(order, EXECUTED), getEmailText(order, EXECUTED))
			else:
				raise result
		else:
			print "notice]",
			sys.stdout.flush()
			db.orders[order.id] = {'status': NOTIFIED}
			db.commit()
			mailer.send(user.email, getEmailTitle(order, NOTIFIED), getEmailText(order, NOTIFIED))
	except Exception, err:
		#todo: log the problem
		print "fail]",
		sys.stdout.flush()
		db.orders[order.id] = {'status': FAILED}
		db.commit()
		mailer.send(user.email, getEmailTitle(order, FAILED), getEmailText(order, FAILED))
		raise

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
		if user.coinbase_access_token is not None:
			totalSellPrice = price.getSellPrice(order.amount) #todo - ask for price with users tokens
			if totalSellPrice is not None and totalSellPrice >= order.at_price * order.amount:
				processOrder(order, user)

