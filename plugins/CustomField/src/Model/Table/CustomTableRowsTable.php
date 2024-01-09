<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class CustomTableRowsTable extends AppTable {
	public function initialize(array $config): void {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->hasMany('CustomTableCells', ['className' => 'CustomField.CustomTableCells', 'dependent' => true]);
		if ($this->behaviors()->has('Reorder')) {
			// $this->behaviors()->get('Reorder')->config([
			// 	'filter' => 'custom_field_id',
			// ]);
			$reorderBehavior = $this->behaviors()->get('Reorder');
			$reorderBehavior->setConfig('filter', 'staff_custom_field_id');
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
