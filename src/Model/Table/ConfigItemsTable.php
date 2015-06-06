<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ConfigItemsTable extends AppTable {
	public function value($name) {
		$entity = $this->findByName($name)->first();

		return $entity->value;
	}

	public function defaultValue($name) {
		$entity = $this->findByName($name)->first();

		return $entity->default;
	}
}
