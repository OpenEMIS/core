<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ConfigItemsTable extends AppTable {
	private $configurations = [];

	public function value($name) {
		$value = '';
		if (array_key_exists($name, $this->configurations)) {
			$value = $this->configurations[$name];
		} else {
			$entity = $this->findByName($name)->first();
			$value = strlen($entity->value) ? $entity->value : $entity->default_value;
			$this->configurations[$name] = $value;
		}
		return $value;
	}

	public function defaultValue($name) {
		$value = '';
		if (array_key_exists($name, $this->configurations)) {
			$value = $this->configurations[$name];
		} else {
			$entity = $this->findByName($name)->first();
			$value = $entity->default;
			$this->configurations[$name] = $value;
		}
		return $value;
	}
}
