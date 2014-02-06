<?php

class CoinbasePrice extends \Nette\Object{
	
	private $curl = NULL;
	private $proxyUrl = 'http://shopito.com/download.php?url=';
	
	public function __construct() {
		$this->curl = new CurlDownloader();
	}
	
	public function getBuyPrice($quantity = 1){
		$result = $this->curl->download($this->proxyUrl.'https://coinbase.com/api/v1/prices/buy?qty='.$quantity, NULL, 5);
		$decoded = json_decode($result);
		if(isset($decoded->subtotal->amount) && $decoded->subtotal->amount > 0){
			return $decoded->subtotal->amount;
		}
		throw new Exception('Not able to fetch Buy price');
	}
	
	public function getSellPrice($quantity = 1){
		$result = $this->curl->download($this->proxyUrl.'https://coinbase.com/api/v1/prices/sell?qty='.$quantity, NULL, 5);
		$decoded = json_decode($result);
		if(isset($decoded->subtotal->amount) && $decoded->subtotal->amount > 0){
			return $decoded->subtotal->amount;
		}
		throw new Exception('Not able to fetch Sell price');
	}	
}