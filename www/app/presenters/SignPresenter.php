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
			$form->addError($e->getMessage());
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

		$form->addPassword('password', 'New Password:')
				->setRequired('Please enter your new password.')
				->addRule(\Nette\Forms\Form::LENGTH, 'Password should be 8 to 500 characters long.', Array(6, 500));

		$form->addPassword('password2', 'New Password:')
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
					->setSubject('Coinbase Limit Orders: Verify your email address')
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

}
