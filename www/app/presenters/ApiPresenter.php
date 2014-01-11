<?php

/**
 * Description of ApiPresenter
 *
 * @author tom
 */
class ApiPresenter extends BasePresenter {

	public function renderCron() {
		$initialTime = time();		
		//runs repeatedly for 55 seconds
		while(time() - $initialTime < 55){
			$this->checkActiveOrders();
		}
	}

	private function checkActiveOrders() {
		$this->context->orders->cancelExpired();

		$currentBuyPrice = $this->context->coinbase->getBuyPrice();
		if (!empty($currentBuyPrice->subtotal->amount)) {
			$this->checkBuyOrders($currentBuyPrice->subtotal->amount);
			$this->context->values->update('coinbase', 'buyPrice', (string) $currentBuyPrice->subtotal->amount);
		}

		$currentSellPrice = $this->context->coinbase->getSellPrice();
		if (!empty($currentSellPrice->subtotal->amount)) {
			$this->checkSellOrders($currentSellPrice->subtotal->amount);
			$this->context->values->update('coinbase', 'sellPrice', (string) $currentSellPrice->subtotal->amount);
		}
	}

	public function checkBuyOrders($currentBuyPrice) {
		$sqlWhere = Array('status' => 'ACTIVE', 'action' => 'BUY', "$currentBuyPrice <= `at_price`");
		foreach ($this->context->orders->findAll()->where($sqlWhere) as $order) { //double check the price for each before buying
			$totalBuyPrice = $this->context->coinbase->user($order->user_id)->order($order)->getBuyPrice($order->amount);
			if ($totalBuyPrice !== NULL && $totalBuyPrice->subtotal->amount <= $order->at_price * $order->amount) {
				$result = $this->context->coinbase->user($order->user_id)->order($order)->buy($order->amount); //Buy the coins
				$this->context->orders->findAll()->get($order->order_id)->update(Array('status' => 'EXECUTED')); //Update order status
			}
		}
	}

	public function checkSellOrders($currentSellPrice) {
		$sqlWhere = Array('status' => 'ACTIVE', 'action' => 'SELL', "$currentSellPrice >= `at_price`");
		foreach ($this->context->orders->findAll()->where($sqlWhere) as $order) { //double check the price for each before selling
			$totalSellPrice = $this->context->coinbase->user($order->user_id)->order($order)->getSellPrice($order->amount);
			if ($totalSellPrice !== NULL && $totalSellPrice->subtotal->amount >= $order->at_price * $order->amount) {
				$result = $this->context->coinbase->user($order->user_id)->order($order)->sell($order->amount); //Sell the coins
				$this->context->orders->findAll()->get($order->order_id)->update(Array('status' => 'EXECUTED')); //Update order status
			}
		}
	}

}