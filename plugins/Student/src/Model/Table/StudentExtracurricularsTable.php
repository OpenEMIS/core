<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentExtracurricularsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator) {
		// $validator->add('name', 'notBlank', [
		// 	'rule' => 'notBlank'
		// ]);
		return $validator;
	}

	public function beforeAction() {
		
	}
}
