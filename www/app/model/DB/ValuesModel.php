<?php

use Nette\Database;

class ValuesModel extends BaseDbModel
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
		return $this->database->table('values');
	}
	
	public function update($group, $name, $newValue){
		$this->database->table('values')->where(Array('group' => $group, 'name' => $name))
			->update(Array('value' => $newValue));
	}

	/** @return Nette\Database\Table\ActiveRow */
	public function get($group, $name){
		return $this->findAll()->where(Array('group' => $group, 'name' => $name))->fetch();
	}
}