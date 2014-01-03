<?php


class CoinbaseWrapper extends Nette\Object
{
	/** @var Coinbase_OAuth */
	protected $coinbaseOauth;
	
	/** @var array */
	private $userCoinbases = Array();
	
	private $presenter;
	private $context;
	
	private $currentUserId;
	private $currentOrder;
	
	
	/**
	 * Is called automatically in app configurator
	 * 
	 * @param string $OauthClientId
	 * @param string $OauthClientSecret
	 */
	function __construct($OauthClientId, $OauthClientSecret) {
		$this->presenter = Nette\Environment::getApplication()->presenter;
		$this->context = $this->presenter->context;
		$this->coinbaseOauth = new Coinbase_OAuth($OauthClientId, $OauthClientSecret, $this->presenter->link('//default'));
	}
	
	/**
	 * function overload to call Coinbase class functions
	 * used for easy logging and exception handling
	 * 
	 * @param string $method
	 * @param array $args
	 */
    public function __call($method, $args) {
        $result = $this->callAndHandleExceptions($method, $args, $this->currentUserId);
		$this->logCall($method, $args, $result);
		return ($result instanceof Exception) ? NULL : $result;
    }
	
	
	/**
	 * log info about each call into our DB
	 * 
	 * @param string $method
	 * @param array $args
	 * @param Exception $result
	 */
	public function logCall($method, $args, $result){
		if(in_array($method, Array('getBuyPrice', 'getSellPrice'))){
			
			if($result instanceof Exception){
				\Nette\Diagnostics\Debugger::barDump($this->currentOrder);
				$orderId = $this->currentOrder->order_id;
				$this->context->logs->logFailedCoinbaseConnection($this->currentUserId, $method, 'order_id', $orderId, $result->getMessage());
			}
			elseif($result === NULL){
				//todo - something went wrong. Let's just do proper Exception handling/logging
			}
			else{
				$order = $this->currentOrder;
				$pricePerCoin = number_format($result->amount/$order->amount, 2);
				$atPrice = number_format($order->at_price, 2);
				$text = "Want to $order->action $order->amount $order->amount_currency for $$atPrice/฿. Current price is $pricePerCoin/฿ incl. fees.";
				$this->context->logs->logActiveCoinbaseOrder($order->order_id, $method, $this->currentUserId, $text);				
			}	
		}
		elseif(in_array($method, Array('buy', 'sell'))){
			//todo - log this
		}
	}
	
	/**
	 * 
	 * @param mixed $userId
	 * @return \CoinbaseWrapper
	 */
	public function user($userId){
		$this->currentUserId = $userId;
		return $this;
	}
	
	/**
	 * save related order info
	 * 
	 * @param mixed $order
	 * @return \CoinbaseWrapper
	 */
	public function order($order){
		$this->currentOrder = $order;
		return $this;
	}

	/**
	 * $code is returned by Coinbase.com. It is what we use to get user auth Tokens
	 * 
	 * @param string $code
	 */
	public function getAndSaveTokens($code){
		$tokens = $this->coinbaseOauth->getTokens($code);
		$this->context->authenticator->update($this->currentUserId, $tokens);
	}

	
	/**
	 * 
	 * Handles API call exceptions. 
	 *  - connection exception - try one more time, return (not throw) exception if unsuccessful second time
	 *  - tokens expired exception - renew tokens and try again one more time
	 *  - coinbase API exception - probably error in code. LOG
	 * 
	 * @param string $callbackFunction
	 * @param array $parameters
	 * @param mixed $userId
	 * @param bool $tryAgain
	 * 
	 */
	private function callAndHandleExceptions( $callbackFunction, array $parameters, $userId, $tryAgain = True){
		//create coinbase instance based on user
		if(!isset($this->userCoinbases[$userId])){
			$tokens = $this->decryptTokens($this->context->authenticator->getUser($userId));
			$this->userCoinbases[$userId] = new Coinbase($this->coinbaseOauth, $tokens);
		}
		
		$coinbaseCallback = callback($this->userCoinbases[$userId], $callbackFunction);
		
		try{
			$result = $coinbaseCallback->invokeArgs($parameters);
		}
		catch(Coinbase_ConnectionException $connectionException){
			//recursive callback. Only if this was the first try
			if($tryAgain){
				//make sure next time it will not go recursive if it fails again
				$result = $this->callAndHandleExceptions($callbackFunction, $parameters, $userId, False);
			}
			else{
				return $connectionException;
			}
		}
		catch (Coinbase_TokensExpiredException $tokenExpiredException){
			$oldTokens = $this->decryptTokens($this->context->authenticator->getUser($userId));
			$newTokens = $this->coinbaseOauth->refreshTokens($oldTokens);
			$this->context->authenticator->update($userId, $newTokens);
			$this->userCoinbases[$userId] = new Coinbase($this->coinbaseOauth, $newTokens);
			
			//recursive callback. Only if this was the first try
			if($tryAgain){
				//make sure next time it will not go recursive if it fails again
				$result = $this->callAndHandleExceptions($callbackFunction, $parameters, $userId, False);
			}
			else{
				$result = NULL;
				//todo - log error				
			}
		}
		catch(Coinbase_ApiException $apiException){
			$result = NULL;
			//todo - log error
			//Please wait until your first bitcoin purchase completes before making additional purchases., (Once your first purchase completes you can make multiple purchases at the same time.)
		}
		
		return $result;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getConnectUrl(){
		return $this->coinbaseOauth->createAuthorizeUrl("balance", "buy", "sell", "transfers");
	}
	
	/**
	 * 
	 * @param array $tokens
	 * @return array
	 */
	private function decryptTokens($tokens){
		return Array(
			'coinbase_refresh_token' => $this->context->salted->decrypt($tokens['coinbase_refresh_token']),
			'coinbase_access_token' => $this->context->salted->decrypt($tokens['coinbase_access_token']),
			'coinbase_expire_time' => $tokens['coinbase_expire_time'],
		);
	}
	
}