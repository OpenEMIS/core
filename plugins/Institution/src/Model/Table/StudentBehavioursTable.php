<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentBehavioursTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		// $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		
	}
}
