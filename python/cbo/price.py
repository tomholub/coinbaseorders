#!/usr/bin/env python
# -*- coding: utf-8 -*-

db = None

from datetime import datetime
import httplib, json
OK = 200

def getBuyPrice(quantity = 1.00):
	connection = httplib.HTTPSConnection("coinbase.com", 443)
	connection.request('GET', '/api/v1/prices/buy?qty=%f' % quantity)
	response = connection.getresponse()
	if response.status == OK:
		try:
			data = json.loads(response.read())
			return float(data['subtotal']['amount'])
		except:
			return None
	return None

def getSellPrice(quantity = 1.00):
	connection = httplib.HTTPSConnection("coinbase.com", 443)
	connection.request('GET', '/api/v1/prices/sell?qty=%f' % quantity)
	response = connection.getresponse()
	if response.status == OK:
		try:
			data = json.loads(response.read())
			return float(data['subtotal']['amount'])
		except:
			return None
	return None

def updateBuyPrice():
	buyPrice = getBuyPrice(1)
	if buyPrice:
		db((db.values.group=='coinbase')&(db.values.name=='buyPrice')).update(value=str(buyPrice),updated=datetime.now())
		db.commit()
	return buyPrice

def updateSellPrice():
	sellPrice = getSellPrice(1)
	if sellPrice:
		db((db.values.group=='coinbase')&(db.values.name=='sellPrice')).update(value=str(sellPrice),updated=datetime.now())
		db.commit()
	return sellPrice
