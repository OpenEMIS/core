<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffBehavioursTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
		// $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		
	}
}