<?php

class SendEmail extends \Nette\Object
{
	public function __construct($to, $subject, $text) {
		$email = new Nette\Mail\Message();
		$email->setFrom('coinbaseorders@gmail.com')
				->addTo($to)
				->setSubject($subject)
				->setHtmlBody($text)->send();
	}
}