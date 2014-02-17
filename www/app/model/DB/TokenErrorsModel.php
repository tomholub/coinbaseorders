<?php

use Nette\Database;

class TokenErrorsModel extends BaseDbModel {

	/** @var Nette\Database\Connection */
	private $database;

	public function __construct(Nette\Database\Connection $database) {
		$this->database = $database;
	}

	/** @return Nette\Database\Table\Selection */
	public function findAll() {
		return $this->database->table('token_errors');
	}

	public function log($orderId, $token, $renew, $expiration){
		return $this->insert(Array(
			'order_id' => $orderId,
			'token' => $token,
			'refresh' => $renew,
			'expiration' => $expiration,
		));
	}

	public function cancelById($orderId) {
		$this->findById($orderId)->update(Array('status' => self::CANCELED, 'date_edited' => new Nette\Database\SqlLiteral('NOW()')));
	}

	/** @return Nette\Database\Table\ActiveRow */
	public function findById($id) {
		return $this->findAll()->get($id);
	}

	public function insert($values) {
		return $this->findAll()->insert($values);
	}

}