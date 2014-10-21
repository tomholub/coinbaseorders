<?php

/**
 * Description of ApiPresenter
 *
 * @author tom
 */
class ApiPresenter extends BasePresenter {
	
	private function prepareTrade($pw, $orderId, $userId, $access){
		\Nette\Diagnostics\Debugger::$productionMode = True;
		if($pw !== $this->context->salted->interfacePassword){
			die('ERROR:PW');
		}
		$order = $this->context->orders->findAll()->where(['id'=> $orderId, 'user_id' => $userId])->fetch();
		$user = $this->context->authenticator->getUser($userId);
		if(!$order){
			die('ERROR:ORDER_ID');
		}
		if(!$user){
			die('ERROR:USER');
		}
		if($user['coinbase_access_token'] != $access){
			die('ERROR:ACCESS');
		}
		return $order;
	}
	
	public function renderBuy($pw, $orderId, $userId, $access){
		$order = $this->prepareTrade($pw, $orderId, $userId, $access);
		try{
			$this->context->coinbase->user($order->user_id)->order($order)->buy($order->amount);
		}
		catch(Exception $exception){
			die('ERROR|'.$exception);
		}
		die('SUCCESS');
	}
	
	public function renderSell($pw, $orderId, $userId, $access){
		$order = $this->prepareTrade($pw, $orderId, $userId, $access);
		try{
			$this->context->coinbase->user($order->user_id)->order($order)->sell($order->amount);
		}
		catch(Exception $exception){
			die('ERROR|'.$exception);
		}
		die('SUCCESS');
	}
	
	/**
	 * Called every minute
	 */
	public function renderCron() {
		$initialTime = time();
		$this->context->orders->cancelExpired();
		$this->possiblyUpdateGlobalSummaryStats();
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

		if ($nextUpdateTime <= $now) {
			$this->context->values->updateDateTime("globalStats", "lastUpdateTime", $now);

			$confirmedUsers = $this->context->authenticator->getCountConfirmedUsers();
			$this->context->values->update("globalStats", "confirmedUsers", $confirmedUsers);

			$stats = $this->context->orders->calculateOrderStats();
			foreach ($stats as $key => $value) {
				$this->context->values->update("globalStats", $key, $value);
			}
		}
	}

}
