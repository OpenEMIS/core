<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentAssessmentsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('assessment_item_results');
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		
	}
}
