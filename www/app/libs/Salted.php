<?php

/*
 * Gets its salt from appropriate neon file in app/config
 */

class Salted extends \Nette\Object {

	private $salt = NULL;
	public $interfacePassword = NULL;

	function __construct($salt, $interfacePassword) {
		$this->salt = $salt;
		$this->interfacePassword = $interfacePassword;
	}

	public function hash($string) {
		return sha1($string . $this->salt);
	}

	public function encrypt($plainText) {
		return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->salt, $plainText, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
	}

	public function decrypt($encryptedText) {
		return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->salt, base64_decode($encryptedText), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
	}

}