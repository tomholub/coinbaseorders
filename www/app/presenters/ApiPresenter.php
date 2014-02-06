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
			sleep(3);
		}
	}

	private function checkActiveOrders() {
		$this->context->orders->cancelExpired();

		$currentBuyPrice = $this->context->coinbasePrice->getBuyPrice();
		if (!empty($currentBuyPrice)) {
			$this->checkBuyOrders($currentBuyPrice);
			$this->context->values->update('coinbase', 'buyPrice', (string) $currentBuyPrice);
		}

		$currentSellPrice = $this->context->coinbasePrice->getSellPrice();
		if (!empty($currentSellPrice)) {
			$this->checkSellOrders($currentSellPrice);
			$this->context->values->update('coinbase', 'sellPrice', (string) $currentSellPrice);
		}
	}

	public function checkBuyOrders($currentBuyPrice) {
		$sqlWhere = Array('status' => 'ACTIVE', 'action' => 'BUY', "$currentBuyPrice <= `at_price`");
		foreach ($this->context->orders->findAll()->where($sqlWhere) as $order) { //double check the price for each before buying
			$totalBuyPrice = $this->context->coinbase->user($order->user_id)->order($order)->getBuyPrice($order->amount);
			if ($totalBuyPrice !== NULL && $totalBuyPrice->subtotal->amount <= $order->at_price * $order->amount) {
				$result = $this->context->coinbase->user($order->user_id)->order($order)->buy($order->amount); //Buy the coins
				$this->context->orders->findAll()->get($order->order_id)->update(Array('status' => 'EXECUTED')); //Update order status
				
				$user = $this->context->authenticator->getUser($order->user_id);
							new SendEmail($user->email, 'You just bought Bitcoin using limit order on Coinbase!', 'Hi there!<br/><br/>The system just executed your order to buy Bitcoin. You can check the details at <a href="http://coinbaseorders.com/">http://coinbaseorders.com/</a>.<br/><br/>Coinbase Orders is a free service. Please consider a small donation, others have donated too. The donation address is 1NNcg12tPe2EHhg3Ese7hq6mEQ9W5W1ign, alternatively <a href="http://coinbaseorders.com/homepage/donate">click here to get donation QR code</a>.<br/><br/>I appriciate your help!<br/><br/>Tom');				
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
				
				$user = $this->context->authenticator->getUser($order->user_id);
				new SendEmail($user->email, 'You just sold Bitcoin using limit order on Coinbase!', 'Hi there!<br/><br/>The system just executed your order to buy Bitcoin. You can check the details at <a href="http://coinbaseorders.com/">http://coinbaseorders.com/</a>.<br/><br/>Coinbase Orders is a free service. Please consider a small donation, others have donated too. The donation address is 1NNcg12tPe2EHhg3Ese7hq6mEQ9W5W1ign, alternatively <a href="http://coinbaseorders.com/homepage/donate">click here to get donation QR code</a>.<br/><br/>I appriciate your help!<br/><br/>Tom');
			}
		}
	}

}