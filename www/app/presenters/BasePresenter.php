
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

		if ($this->getParam('code') != NULL && $this->user->isLoggedIn()) {
			$this->context->coinbase->user($this->user->id)->getAndSaveTokens($this->getParam('code'));
			$this->redirect($this->home);
		}
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
