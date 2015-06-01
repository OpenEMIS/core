<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentBehavioursTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
		// $this->belongsTo('SecurityUsers', ['className' => 'SecurityUsers', 'foreignKey' => 'student_id']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		
	}
}
