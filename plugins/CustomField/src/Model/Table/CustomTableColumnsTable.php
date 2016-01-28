<?php
namespace CustomField\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class CustomTableColumnsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->hasMany('CustomTableCells', ['className' => 'CustomField.CustomTableCells', 'dependent' => true]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->requirePresence('name')
			->notEmpty('name', 'Please enter a name.');

		return $validator;
	}
}
