<?php

/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter {

	public function renderDefault($code = NULL) {
		
	}

	public function actionOrders() {
		if (!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in');
		} else {
			if ($this->user->identity->email_confirmation != 'confirmed' || $this->user->identity->coinbase_access_token == NULL) {
				$this->flashMessage('Verify your e-mail and connect with Coinbase before placing orders.');
				$this->redirect($this->home);
			}
		}
	}

	public function actionNewOrder() {
		if (!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in');
		} else {
			if ($this->user->identity->email_confirmation != 'confirmed' || $this->user->identity->coinbase_access_token == NULL) {
				$this->flashMessage('Verify your e-mail and connect with Coinbase before placing orders.');
				$this->redirect($this->home);
			}
		}
	}
	
	public function renderAdmin(){
		if(!$this->user->isInRole('ADMIN')){
			$this->flashMessage('Not authorized', 'error');
			$this->redirect($this->home);
		}
	}

}
