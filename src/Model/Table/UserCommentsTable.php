<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class UserCommentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

}
