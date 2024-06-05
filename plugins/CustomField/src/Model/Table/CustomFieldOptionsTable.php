<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class CustomFieldOptionsTable extends AppTable {
	public function initialize(array $config): void {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);

		if ($this->behaviors()->has('Reorder')) {
			$reorderBehavior = $this->behaviors()->get('Reorder');
			$reorderBehavior->setConfig('filter', 'custom_field_id');
		}
	}

	public function validationDefault(Validator $validator): Validator {
		$validator = parent::validationDefault($validator);
		$validator
			->requirePresence('name')
			->notEmpty('name', 'Please enter a name.');

		return $validator;
	}
}
