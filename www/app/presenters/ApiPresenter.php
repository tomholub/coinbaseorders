<?php

/**
 * Description of ApiPresenter
 *
 * @author tom
 */
class ApiPresenter extends BasePresenter {

	/**
	 * Called every minute
	 */
	public function renderCron() {
		$initialTime = time();

		$this->possiblyUpdateGlobalSummaryStats();

		// STOPSHIP(mattfaus): DEBUG ONLY
		// Check and execute active orders every 3 seconds
		// while(time() - $initialTime < 55){
		// 	$this->checkActiveOrders();
		// 	// TODO(mattfaus): We should sleep less if order execution took a long time
		// 	sleep(3);
		// }
	}

	private function checkActiveOrders() {
		$this->context->orders->cancelExpired();

		$currentBuyPrice = $this->context->coinbasePrice->getBuyPrice();
		if (!empty($currentBuyPrice)) {
			$this->context->values->update('coinbase', 'buyPrice', (string) $currentBuyPrice);
		}

		$currentSellPrice = $this->context->coinbasePrice->getSellPrice();
		if (!empty($currentSellPrice)) {
			$this->context->values->update('coinbase', 'sellPrice', (string) $currentSellPrice);
		}

		if (!empty($currentBuyPrice)) {
			$this->checkBuyOrders($currentBuyPrice);
		}
		if (!empty($currentSellPrice)) {
			$this->checkSellOrders($currentSellPrice);
		}
	}

	public function checkBuyOrders($currentBuyPrice) {
		$sqlWhere = Array('status' => 'ACTIVE', 'action' => 'BUY', "$currentBuyPrice <= `at_price`");
		foreach ($this->context->orders->findAll()->where($sqlWhere) as $order) { //double check the price for each before buying
			$userAssociatedWithOrder = $this->context->authenticator->getUser($order->user_id);
			if(!empty($order->at_price) && !empty($order->amount) && $userAssociatedWithOrder->coinbase_access_token){ //his Coinbase API tokens are set
				$totalBuyPrice = $this->context->coinbase->user($order->user_id)->order($order)->getBuyPrice($order->amount);
				if ($totalBuyPrice !== NULL && $totalBuyPrice->subtotal->amount <= $order->at_price * $order->amount) {
					$result = $this->context->coinbase->user($order->user_id)->order($order)->buy($order->amount); //Buy the coins
					$this->context->orders->findAll()->get($order->order_id)->update(Array('status' => 'EXECUTED')); //Update order status

					new SendEmail($userAssociatedWithOrder->email, 'You just bought Bitcoin using limit order on Coinbase!', 'Hi there!<br/><br/>The system just executed your order to buy Bitcoin. You can check the details at <a href="http://coinbaseorders.com/">http://coinbaseorders.com/</a>.<br/><br/>Coinbase Orders is a free service. Please consider a small donation, others have donated too. The donation address is 13ejFczTyMsdZQHkrfVEfiGY8RLD2rDs9i, alternatively <a href="http://coinbaseorders.com/homepage/donate">click here to get donation QR code</a>.<br/><br/>I appriciate your help!<br/><br/>Tom');
				}
			}
		}
	}

	public function checkSellOrders($currentSellPrice) {
		$sqlWhere = Array('status' => 'ACTIVE', 'action' => 'SELL', "$currentSellPrice >= `at_price`");
		foreach ($this->context->orders->findAll()->where($sqlWhere) as $order) { //double check the price for each before selling
			$userAssociatedWithOrder = $this->context->authenticator->getUser($order->user_id);
			if(!empty($order->at_price) && !empty($order->amount) && $userAssociatedWithOrder->coinbase_access_token){ //his Coinbase API tokens are set
				$totalSellPrice = $this->context->coinbase->user($order->user_id)->order($order)->getSellPrice($order->amount);
				if ($totalSellPrice !== NULL && $totalSellPrice->subtotal->amount >= $order->at_price * $order->amount) {
					$result = $this->context->coinbase->user($order->user_id)->order($order)->sell($order->amount); //Sell the coins
					$this->context->orders->findAll()->get($order->order_id)->update(Array('status' => 'EXECUTED')); //Update order status

					new SendEmail($userAssociatedWithOrder->email, 'You just sold Bitcoin using limit order on Coinbase!', 'Hi there!<br/><br/>The system just executed your order to buy Bitcoin. You can check the details at <a href="http://coinbaseorders.com/">http://coinbaseorders.com/</a>.<br/><br/>Coinbase Orders is a free service. Please consider a small donation, others have donated too. The donation address is 13ejFczTyMsdZQHkrfVEfiGY8RLD2rDs9i, alternatively <a href="http://coinbaseorders.com/homepage/donate">click here to get donation QR code</a>.<br/><br/>I appriciate your help!<br/><br/>Tom');
				}
			}
		}
	}

	/**
	 * Checks to see when the last time global stats were updated, and
	 * if that was longer than 10 minutes ago, re-updates them.
	 */
	private function possiblyUpdateGlobalSummaryStats() {
		$updateInterval = new DateInterval("PT10M");
		$lastUpdateTime = $this->context->values->getDateTime("globalStats", "lastUpdateTime");
		$now = new DateTime();

		if (!$lastUpdateTime) {
			$nextUpdateTime = $now;
		} else {
			$nextUpdateTime = $lastUpdateTime->add($updateInterval);
		}

		$confirmed_users = $this->context->authenticator->getCountConfirmedUsers();
		$stats = $this->context->orders->calculateOrderStats();
		\Nette\Diagnostics\Debugger::dump($stats);

		if ($nextUpdateTime <= $now) {
			// amount of registered users, amount of active orders, amount of executed orders to date
			\Nette\Diagnostics\Debugger::dump("Updating!");
			$this->context->values->updateDateTime("globalStats", "lastUpdateTime", $now);
		}
	}

}
