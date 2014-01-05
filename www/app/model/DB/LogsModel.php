<?php

use Nette\Database;

class LogsModel extends BaseDbModel
{
	/** @var Nette\Database\Connection */
	private $database;

	public function __construct(Nette\Database\Connection $database)
	{
		$this->database = $database;
	}

	/** @return Nette\Database\Table\Selection */
	public function findAll()
	{
		return $this->database->table('logs');
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
	
	public function logActiveCoinbaseOrder($orderId, $subtype, $userId, $text){
		$this->database->table('logs')
				->where(Array('relation' => 'order_id', 'relation_id' => $orderId, 'type' => 'CoinbaseCall', 'subtype' => $subtype, 'user_id' => $userId))
				->update(Array('latest' => False));
		
		$this->insert(Array(
			'user_id' => $userId,
			'type' => 'CoinbaseCall',
			'subtype' => $subtype,
			'relation' => 'order_id',
			'relation_id' => $orderId,
			'text' => $text,
			'latest' => True,
		));
	}
	
	public function logException(Exception $exception, $data = NULL){
		$this->insert(Array(
			'type' => 'Exception',
			'subtype' => get_class($exception),
			'text' => $exception->getCode().': '.$exception->getMessage(),
			'input' => isset($exception->data) ? print_r($exception->data) : NULL,
			'output' => print_r($data, true),
		));			
	}

}