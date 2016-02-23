<?php
namespace CustomField\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

class CustomFieldOptionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);

		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'custom_field_id',
			]);
		}
	}

	public function validationDefault(Validator $validator) {
		$validator
			->requirePresence('name')
			->notEmpty('name', 'Please enter a name.');

		return $validator;
	}
}
