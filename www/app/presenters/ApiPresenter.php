<?php

/**
 * Description of ApiPresenter
 *
 * @author tom
 */
class ApiPresenter extends BasePresenter {

	public function renderCron() {
		$this->checkActiveOrders();
	}
	
	private function checkActiveOrders(){
		$this->context->orders->cancelExpired();
		
		foreach($this->context->orders->findAll()->where('status', 'ACTIVE') as $order){
			if($order->action == 'BUY'){
				$result = $this->checkPriceAndBuyIfGood($order);
			} elseif($order->action == 'SELL'){
				$result = $this->checkPriceAndSellIfGood($order);
			}
		}
	}
	
	
	public function checkPriceAndBuyIfGood($order){
		$currentPrice = $this->context->coinbase->user($order->user_id)->order($order)->getBuyPrice($order->amount);
		
		if($currentPrice !== NULL && $currentPrice->amount <= $order->at_price*$order->amount){
			//Buy the coins
			$result = $this->context->coinbase->user($order->user_id)->order($order)->buy($order->amount);
			//Update order status to EXECUTED
			$this->context->orders->findAll()->get($order->order_id)->update(Array('status' => 'EXECUTED'));
		}
	}
	
	public function checkPriceAndSellIfGood($order){
		$currentPrice = $this->context->coinbase->user($order->user_id)->order($order)->getSellPrice($order->amount);
		
		if($currentPrice !== NULL && $currentPrice->amount >= $order->at_price*$order->amount){
			//Sell the coins
			$result = $this->context->coinbase->user($order->user_id)->order($order)->sell($order->amount);
			//Update order status to EXECUTED
			$this->context->orders->findAll()->get($order->order_id)->update(Array('status' => 'EXECUTED'));
		}
	}

}