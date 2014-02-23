<?php

use Nette\Application\UI;

/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter {

	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm() {
		$form = new UI\Form;
		$form->addText('email', 'E-mail:')
				->setRequired('Please enter your username.');

		$form->addPassword('password', 'Password:')
				->setRequired('Please enter your password.');

		$form->addCheckbox('remember', 'Keep me signed in');

		$form->addSubmit('send', 'Sign in');

		// call method signInFormSucceeded() on success
		$form->onSuccess[] = $this->signInFormSucceeded;
		return $form;
	}

	public function signInFormSucceeded($form) {
		$values = $form->getValues();

		if ($values->remember) {
			$this->getUser()->setExpiration('14 days', FALSE);
		} else {
			$this->getUser()->setExpiration('20 minutes', TRUE);
		}

		try {
			$this->getUser()->login($values->email, $values->password);
			$this->redirect('Homepage:default');
		} catch (Nette\Security\AuthenticationException $e) {
			$resetLink = $this->link('sendResetEmail!', Array('email' => $values->email));
			$this->flashMessage($e->getMessage().($e->getCode() == Authenticator::INVALID_CREDENTIAL ? " You can <a href=\"$resetLink\">reset your password</a>" : ''));
		}
	}

	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignUpForm() {
		$form = new UI\Form;

		$form->addText('email', 'E-mail:')
				->setRequired('Please enter your e-mail.');

		$form->addPassword('password', 'Set password:')
				->setRequired('Please enter your new password.')
				->addRule(\Nette\Forms\Form::LENGTH, 'Password should be 6 to 500 characters long.', Array(6, 500));

		$form->addPassword('password2', 'Re-type password:')
				->setRequired('Please enter your new password.')
				->addRule(\Nette\Forms\Form::EQUAL, 'Passwords don\'t match', $form['password']);

		$form->addCheckbox('remember', 'Keep me signed in');

		$form->addSubmit('send', 'Sign up');

		$form->onSuccess[] = $this->signUpFormSucceeded;
		return $form;
	}

	public function signUpFormSucceeded($form) {
		$values = $form->getValues();

		if ($values->remember) {
			$this->getUser()->setExpiration('14 days', FALSE);
		} else {
			$this->getUser()->setExpiration('20 minutes', TRUE);
		}

		if (!$this->context->authenticator->register($values->email, $values->password)) {
			$form->addError('You are already registered with this email. Contact me tom@coinbaseorders.com if you need password reset.');
		} else {
			$this->getUser()->login($values->email, $values->password);

			$verificationLink = $this->link("//Sign:verifyEmail", Array('emailCode' => $this->user->identity->email_confirmation));
			$email = new Nette\Mail\Message();
			$email->setFrom('tom@coinbaseorders.com')->addTo($values['email'])
					->setSubject('Coinbase Orders: Verify your email address')
					->setHtmlBody('Thanks for registering! ' . \Nette\Utils\Html::el('a')->href($verificationLink)->setText('Click here to verify email') . " alternatively copy & paste this link into your browser: $verificationLink")->send();

			$this->redirect('Homepage:default');
		}
	}

	public function actionOut() {
		$this->getUser()->logout();
		$this->flashMessage('You have been signed out.');
		$this->redirect('in');
	}

	public function actionVerifyEmail($emailCode) {
		if ($this->context->authenticator->verifyEmail($this->user->id, $emailCode)) {
			$this->flashMessage('Your email is verified. Now you can connect this app with Coinbase.');
		} else {
			$this->flashMessage('I wasn\'t able to verify your email, please contact me at tom@coinbaseorders.com so I can fix it.');
		}
		$this->redirect($this->home);
	}
	
	public function renderProfile(){
		if (!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in');
		}
	}
	
	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentProfileForm() {
		$form = new UI\Form;

		$form->addText('username', 'How should I call you?')
			->setDefaultValue($this->user->identity->username);

		$form->addPassword('password', 'Set New Password:')
			->addCondition(\Nette\Forms\Form::FILLED)
				->addRule(\Nette\Forms\Form::LENGTH, 'Password should be 6 to 500 characters long.', Array(6, 500));

		$form->addPassword('password2', 'Verify Password:')
			->addConditionOn($form['password'], Nette\Forms\Form::FILLED)
				->addRule(Nette\Forms\Form::FILLED, 'Please verify your new password.')
				->addRule(\Nette\Forms\Form::EQUAL, 'Passwords don\'t match', $form['password']);
		
		$form->addSubmit('send', 'Update');

		$form->onSuccess[] = $this->profileFormSucceeded;
		return $form;
	}

	public function profileFormSucceeded($form) {
		$values = $form->getValues();

		$updateValues = Array('username' => $values['username']);
		if (!empty($values['password'])) {
			$updateValues['password'] = $this->context->salted->hash($values['password']);
		}
		$this->context->authenticator->update($this->user->id, $updateValues);
		$this->flashMessage('Profile updated').
		$this->redirect('Sign:profile');
	}	
	
	public function handleSendResetEmail($email){
		if($userId = $this->context->authenticator->getUserIdByEmail($email)){
			$randomHash = $this->context->authenticator->setRandomHash($userId);
			$link = $this->link("//Sign:resetPassword", Array('id' => $userId, 'randomHash' => $randomHash));
			new SendEmail($email, "Reset your password", "Please follow this link to reset your password: <a href=\"$link\">$link</a>");
			$this->flashMessage("Reset link was sent to $email", "success");
			$this->redirect($this->home);
		}
		else{
			$this->flashMessage("This email is not registered", "error");
			$this->redirect('Sign:in');
		}
	}
	
	public function actionResetPassword($id, $randomHash){
		$this->template->verificationSuccess = $this->context->authenticator->verifyRandomHash($id, $randomHash);
	}
	
	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentResetPasswordForm() {
		$form = new UI\Form;

		$form->addPassword('password', 'New Password:')
				->setRequired('Please enter your new password.')
				->addRule(\Nette\Forms\Form::LENGTH, 'Password should be 6 to 500 characters long.', Array(6, 500));

		$form->addPassword('password2', 'New Password Again:')
				->setRequired('Please enter your new password.')
				->addRule(\Nette\Forms\Form::EQUAL, 'Passwords don\'t match', $form['password']);;

		$form->addHidden('randomHash', $this->presenter->getParam('randomHash'));
		$form->addHidden('userId', $this->presenter->getParam('id'));
				
		$form->addSubmit('submit', 'Reset password');

		$form->onSuccess[] = $this->resetPasswordFormSucceeded;
		return $form;
	}

	public function resetPasswordFormSucceeded($form) {
		$values = $form->getValues();

		if($user = $this->context->authenticator->resetPassword($values->userId, $values->randomHash, $values->password)){
			$this->getUser()->login($user->email, $values->password);
			new SendEmail($user->email, 'Coinbase Orders: Password was reset', 'Your password was succesfuly reset');
			$this->flashMessage("Password succesfuly reset.", 'success');
			$this->redirect($this->home);
		}
		else{
			$this->flashMessage("Could not verify password reset.", 'error');
			$this->redirect('Sign:in');
		}
	}	
}
