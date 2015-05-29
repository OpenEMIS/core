<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class UserContactsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

	}

	public function beforeAction() {
		$this->fields['contact_type_id']['type'] = 'select';
		pr($this->fields);
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

}
