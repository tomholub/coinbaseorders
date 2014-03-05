<?php

use Nette\Database;

class ValuesModel extends BaseDbModel {

	/** @var Nette\Database\Connection */
	private $database;

	public function __construct(Nette\Database\Connection $database) {
		$this->database = $database;
	}

	/** @return Nette\Database\Table\Selection */
	public function findAll() {
		return $this->database->table('values');
	}

	public function update($group, $name, $newValue) {
		$success = $this->database->table('values')->where(Array('group' => $group, 'name' => $name))
				->update(Array('value' => $newValue, 'updated' => NULL));

		if (!$success) {
			// TODO(mattfaus): This should really be an atomic "upsert"
			$this->database->table('values')->insert(Array(
				'group' => $group,
				'name' => $name,
				'value' => $newValue,
				'updated' => NULL));
		}
	}

	public function updateDateTime($group, $name, $dateTime) {
		$this->update($group, $name, $dateTime->format(DateTime::ISO8601));
	}

	/** @return Nette\Database\Table\ActiveRow */
	public function get($group, $name) {
		return $this->findAll()->where(Array('group' => $group, 'name' => $name))->fetch();
	}

	/** @return array */
	public function getGroup($group) {
		return $this->findAll()->where(Array('group' => $group))->fetchPairs("name", "value");
	}

	public function getDateTime($group, $name) {
		$value = $this->get($group, $name);
		if (empty($value)) {
			return NULL;
		}

		// Returns a new DateTime instance or FALSE on failure.
		$value = $this->get($group, $name)->value;
		return DateTime::createFromFormat(DateTime::ISO8601, $value);
	}
}
