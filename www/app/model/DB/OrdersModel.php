<?php

use Nette\Database;

class OrdersModel extends BaseDbModel {

	/** @var Nette\Database\Connection */
	private $database;

	const ACTIVE = 'ACTIVE',
			EXPIRED = 'EXPIRED',
			EXECUTED = 'EXECUTED',
			CANCELED = 'CANCELED';

	public function __construct(Nette\Database\Connection $database) {
		$this->database = $database;
	}

	/** @return Nette\Database\Table\Selection */
	public function findAll() {
		return $this->database->table('orders');
	}

	public function cancelExpired() {
		$this->findAll()->where(Array('status' => self::ACTIVE, 'date_cancel > NOW()', 'NOT date_cancel' => null))
				->update(Array('status' => self::EXPIRED, 'date_edited' => new Nette\Database\SqlLiteral('NOW()')));
	}

	public function cancelById($orderId) {
		$this->findById($orderId)->update(Array('status' => self::CANCELED, 'date_edited' => new Nette\Database\SqlLiteral('NOW()')));
	}

	/** @return Nette\Database\Table\ActiveRow */
	public function findById($id) {
		return $this->findAll()->get($id);
	}

	public function findExposure($user_id, $action) {
		$sqlWhere = Array('status' => 'ACTIVE', 'user_id' => $user_id, 'action' => $action);
		$exposure = $this->findAll()->where($sqlWhere)->sum($action == 'SELL' ? "amount" : "amount*at_price");

		if(empty($exposure)) {
			$exposure = 0;
		}
		return $exposure;
	}

	public function findSellExposure($user_id) {
		return $this->findExposure($user_id, 'SELL');
	}

	public function findBuyExposure($user_id) {
		return $this->findExposure($user_id, 'BUY');
	}

	/**
	 * Calculates active/executed buy/sell orders.
	 * This is a heavy operation.
	 */
	public function calculateOrderStats() {
		$statsQuery = "
			SELECT
				SUM(IF(status = 'ACTIVE' AND action = 'BUY',1,0)) as activeBuyCount,
				SUM(IF(status = 'ACTIVE' AND action = 'BUY',amount,0)) as activeBuyBTCSum,
				SUM(IF(status = 'ACTIVE' AND action = 'BUY',amount_currency,0)) as activeBuyUSDSum,
				SUM(IF(status = 'ACTIVE' AND action = 'SELL',1,0)) as activeSellCount,
				SUM(IF(status = 'ACTIVE' AND action = 'SELL',amount,0)) as activeSellBTCSum,
				SUM(IF(status = 'ACTIVE' AND action = 'SELL',amount_currency,0)) as activeSellUSDSum,
				SUM(IF(status = 'EXECUTED' AND action = 'BUY',1,0)) as executedBuyCount,
				SUM(IF(status = 'EXECUTED' AND action = 'BUY',amount,0)) as executedBuyBTCSum,
				SUM(IF(status = 'EXECUTED' AND action = 'BUY',amount_currency,0)) as executedBuyUSDSum,
				SUM(IF(status = 'EXECUTED' AND action = 'SELL',1,0)) as executedSellCount,
				SUM(IF(status = 'EXECUTED' AND action = 'SELL',amount,0)) as executedSellBTCSum,
				SUM(IF(status = 'EXECUTED' AND action = 'SELL',amount_currency,0)) as executedSellUSDSum
			FROM
				orders
			WHERE
				status = 'ACTIVE' OR status = 'EXECUTED'";

		$row = $this->database->query($statsQuery)->execute();
		return Array(
			"activeBuyCount" => $row->fetchField("activeBuyCount"),
			"activeBuyBTCSum" => $row->fetchField("activeBuyBTCSum"),
			"activeBuyUSDSum" => $row->fetchField("activeBuyUSDSum"),
			"activeSellCount" => $row->fetchField("activeSellCount"),
			"activeSellBTCSum" => $row->fetchField("activeSellBTCSum"),
			"activeSellUSDSum" => $row->fetchField("activeSellUSDSum"),
			"executedBuyCount" => $row->fetchField("executedBuyCount"),
			"executedBuyBTCSum" => $row->fetchField("executedBuyBTCSum"),
			"executedBuyUSDSum" => $row->fetchField("executedBuyUSDSum"),
			"executedSellCount" => $row->fetchField("executedSellCount"),
			"executedSellBTCSum" => $row->fetchField("executedSellBTCSum"),
			"executedSellUSDSum" => $row->fetchField("executedSellUSDSum")
		);
	}

	public function insert($values) {
		return $this->findAll()->insert($values);
	}

}
