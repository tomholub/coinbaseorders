<?php

use Nette\Utils\Html;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {

	/** @var Nette\Database\Connection */
	protected $db;
	public $home = '//Homepage:default';
	protected $coinbaseData = Array();

	protected function startup() {
		parent::startup();

		try { //init DB
			$this->db = $this->context->database;
		} catch (PDOException $e) {
			die('Ooops! We are having some difficulties. It says "' . $e->getMessage() . '"<br/><br/><b>Please press F-5 button to refresh the page</b>. If this doesn\'t go away, please write me at tom@coinbaseorders.com');
		}

		$this->template->latestPrice = Array(
			'buy' => $this->context->values->get('coinbase', 'buyPrice'),
			'sell' => $this->context->values->get('coinbase', 'sellPrice'),
		);

		// Handle OAuth2 redirect from Coinbase
		if ($this->getParam('code') != NULL && $this->user->isLoggedIn()) {
			$this->context->coinbase->user($this->user->id)->getAndSaveTokens($this->getParam('code'));
			$this->redirect($this->home);
		}
	}

	public function handleUpdateCurrentPrice(){
		$updatedSecondsAgo = time() - $this->context->values->get('coinbase', 'sellPrice')->updated->getTimestamp();

		if($updatedSecondsAgo > 600 && Nette\Environment::isProduction()){ //price not updated for more than 10 minutes
			throw new Exception('Price not updated for more than 10 minutes');
		}

		$this->payload->currentBuyPriceValue = $this->context->values->get('coinbase', 'buyPrice')->value;
		$this->payload->currentSellPriceValue = $this->context->values->get('coinbase', 'sellPrice')->value;

		$this->payload->currentBuyPrice = 'Buy $'.number_format($this->payload->currentBuyPriceValue, 2);
		$this->payload->currentSellPrice = 'Sell $'.number_format($this->payload->currentSellPriceValue, 2);
		$this->payload->lastPriceCheck = "Price updated $updatedSecondsAgo seconds ago";

		$this->sendPayload();
	}

	protected function beforeRender() {
		parent::beforeRender();
		$this->template->user = $this->user;
		if ($this->user->isLoggedIn() && $this->user->identity->coinbase_access_token == NULL) {
			if ($this->user->identity->email_confirmation == 'confirmed') {
				$this->template->headerMessage = Html::el('a')->href($this->context->coinbase->getConnectUrl())->setText('Connect to Coinbase');
			} else {
				$this->template->headerMessage = Html::el('span')->setText('You got mail. Please verify your email address');
			}
		}
	}

	public function actionLogout() {
		if ($this->getUser()->isLoggedIn()) {
			$this->getUser()->logout(TRUE);
			$this->redirect($this->home);
		}
	}

	public function createComponent($name) {
		if (preg_match('/^[A-Z][A-Za-z]+Form$/', $name)) {
			$component = new $name($this, $name);
			$component->create($this);
			if (!($component instanceof IComponent) && !isset($this->components[$name])) {
				throw new UnexpectedValueException("Method $name::create() did not return or create the desired component.");
			}
			return $component;
		}

		if (preg_match('/^[A-Z][A-Za-z]+Grid$/', $name)) {
			$component = new $name($this, $this->context->database);

//			if (!($component instanceof IComponent) && !isset($this->components[$name])) {
//				throw new UnexpectedValueException("Method $name::create() did not return or create the desired component.");
//			}
			return $component;
		}

		return parent::createComponent($name);
	}

}
