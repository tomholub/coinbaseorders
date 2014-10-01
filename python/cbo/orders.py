#!/usr/bin/env python
# -*- coding: utf-8 -*-

db = None
import price, mailer

def getEmailTitle(action):
	return "You just %s Bitcoin using limit order on Coinbase!" % ('bought' if action=='BUY' else 'sold')
	
def getEmailText(action):
	text = 'Hi there!\n\n'
	text += 'The system just executed your order to %s Bitcoin.\n\n' % ('buy' if action=='BUY' else 'sell')
	text += 'You can check the details at <a href="http://coinbaseorders.com/">http://coinbaseorders.com/</a>.\n\n'
	text += 'Coinbase Orders is a free service. Please consider a small donation on 13ejFczTyMsdZQHkrfVEfiGY8RLD2rDs9i, alternatively <a href="http://coinbaseorders.com/homepage/donate">click here to get donation QR code</a>.\n\n'
	text += 'I appriciate your help!\n\nTom'

def checkBuy(currentBuyPrice):
	print "[/] checking buy orders"
	activeOrders = db.orders((db.orders.status=='ACTIVE')&(db.orders.action=='BUY')&(currentBuyPrice<=db.orders.at_price)&(db.orders.at_price!=None)&(db.orders.amount!=None))
	for order in orders:
		user = db.users[order.user_id]
		if user.coinbase_access_token is not None:
			totalBuyPrice = price.getBuyPrice(order.amount) #todo - ask for price with users tokens
			if totalBuyPrice is not None and totalBuyPrice <= order.at_price * order.amount:
				#$this->context->orders->findAll()->get($order->order_id)->update(Array('status' => 'EXECUTING')); //Update order status
				#$result = $this->context->coinbase->user($order->user_id)->order($order)->buy($order->amount); //Buy the coins
				#$this->context->orders->findAll()->get($order->order_id)->update(Array('status' => 'EXECUTED')); //Update order status
				mailer.send(user.email, getEmailTitle('BUY'), getEmailText('BUY'))

def checkSell():
	print "[/] updating sell orders"
