<?php

class CoinbaseWrapper extends Nette\Object {

	/** @var Coinbase_OAuth */
	protected $coinbaseOauth;

	/** @var array */
	private $userCoinbases = Array();

	const ANONYMOUS = -1;

	private $presenter;
	private $context;
	private $currentUserId = self::ANONYMOUS;
	private $currentOrder = NULL;

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
		$this->currentUserId = self::ANONYMOUS; //reset USER ID for next call
		return ($result instanceof Exception) ? NULL : $result;
	}

	/**
	 * log info about each call into our DB
	 * 
	 * @param string $method
	 * @param array $args
	 * @param Exception $result
	 */
	public function logCall($method, $args, $result) {

		//if Exception occured - need to log exception in DB
		if ($result instanceof Exception) {
			$orderId = isset($this->currentOrder->order_id) ? $this->currentOrder->order_id : NULL;
			$this->context->logs->logException($result, Array('order_id' => $orderId, 'loggedUser' => $this->presenter->user->id));
			//email notification to admin
			$message = new \Nette\Mail\Message();
			$message->setFrom('tom@coinbaseorders.com')->addTo('tom@coinbaseorders.com')
					->setSubject('new exception' . get_class($result))
					->setBody($result->getMessage())
					->send();
		}
	}

	/**
	 * 
	 * @param mixed $userId
	 * @return \CoinbaseWrapper
	 */
	public function user($userId) {
		$this->currentUserId = $userId;
		return $this;
	}

	/**
	 * save related order info
	 * 
	 * @param mixed $order
	 * @return \CoinbaseWrapper
	 */
	public function order($order) {
		$this->currentOrder = $order;
		return $this;
	}

	/**
	 * $code is returned by Coinbase.com. It is what we use to get user auth Tokens
	 * 
	 * @param string $code
	 */
	public function getAndSaveTokens($code) {
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
	private function callAndHandleExceptions($callbackFunction, array $parameters, $userId, $tryAgain = True) {

		//user-specific api call. Create coinbase instance based on user.
		if (!isset($this->userCoinbases[$userId])) {
			if ($userId != self::ANONYMOUS) {
				$tokens = $this->decryptTokens($this->context->authenticator->getUser($userId));
			} else {
				$tokens = NULL;
			}
			$this->userCoinbases[$userId] = new Coinbase($this->coinbaseOauth, $tokens);
		}
		$coinbaseCallback = callback($this->userCoinbases[$userId], $callbackFunction);

		try {
			$result = $coinbaseCallback->invokeArgs($parameters);
		} catch (Coinbase_ConnectionException $connectionException) {
			//recursive callback. Only if this was the first try
			if ($tryAgain) {
				//make sure next time it will not go recursive if it fails again
				$result = $this->callAndHandleExceptions($callbackFunction, $parameters, $userId, False);
			} else {
				return $connectionException;
			}
		} catch (Coinbase_TokensExpiredException $tokenExpiredException) {
			$oldTokens = $this->decryptTokens($this->context->authenticator->getUser($userId));
			$newTokens = $this->coinbaseOauth->refreshTokens($oldTokens);
			$this->context->authenticator->update($userId, $newTokens);
			$this->userCoinbases[$userId] = new Coinbase($this->coinbaseOauth, $newTokens);

			//recursive callback. Only if this was the first try
			if ($tryAgain) {
				//make sure next time it will not go recursive if it fails again
				$result = $this->callAndHandleExceptions($callbackFunction, $parameters, $userId, False);
			} else {
				$exception = new LogMeException($this->context->texts->get('LogMeException', 10), 10, $tokenExpiredException);
				$exception->data = Array('userId' => $userId);
				return $exception;
			}
		} catch (Coinbase_ApiException $apiException) {
			if (strpos($apiException->getMessage(), $this->context->texts->get('CoinbaseErrors', 'first_purchase')) !== false) {
				//known exception
				$this->presenter->flashMessage($apiException->getMessage());
				return NULL;
			} else {
				//unknown exception
				$exception = new LogMeException($this->context->texts->get('LogMeException', 12), 12, $apiException);
				$exception->data = Array('userId' => $userId);
				return $exception;
			}
		}

		return $result;
	}

	/**
	 * 
	 * @return string
	 */
	public function getConnectUrl() {
		return $this->coinbaseOauth->createAuthorizeUrl("balance", "buy", "sell", "transfers");
	}

	/**
	 * 
	 * @param array $tokens
	 * @return array
	 */
	private function decryptTokens($tokens) {
		return Array(
			'coinbase_refresh_token' => $this->context->salted->decrypt($tokens['coinbase_refresh_token']),
			'coinbase_access_token' => $this->context->salted->decrypt($tokens['coinbase_access_token']),
			'coinbase_expire_time' => $tokens['coinbase_expire_time'],
		);
	}

}