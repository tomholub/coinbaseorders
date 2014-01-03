<?php

use Nette\Database;

class OrdersModel extends BaseDbModel
{
	/** @var Nette\Database\Connection */
	private $database;
	
	const	ACTIVE = 'ACTIVE',
			EXPIRED = 'EXPIRED',
			EXECUTED = 'EXECUTED',
			CANCELED = 'CANCELED';


	public function __construct(Nette\Database\Connection $database)
	{
		$this->database = $database;
	}


	/** @return Nette\Database\Table\Selection */
	public function findAll()
	{
		return $this->database->table('orders');
	}
	
	public function cancelExpired()
	{
		$this->findAll()->where(Array('status' => self::ACTIVE, 'date_cancel > NOW()', 'NOT date_cancel' => null))
			->update(Array('status' => self::EXPIRED, 'date_edited' => new Nette\Database\SqlLiteral('NOW()')));
	}
	
	public function cancelById($orderId){
		$this->findById($orderId)->update(Array('status' => self::CANCELED, 'date_edited' => new Nette\Database\SqlLiteral('NOW()')));
	}

	/** @return Nette\Database\Table\ActiveRow */
	public function findById($id)
	{
		return $this->findAll()->get($id);
	}


	public function insert($values)
	{
		return $this->findAll()->insert($values);
	}

}