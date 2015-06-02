<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteStudentAbsencesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'security_user_id']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->belongsTo('StudentAbsenceReasons', ['className' => 'Student.StudentAbsenceReasons']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		
	}
}
