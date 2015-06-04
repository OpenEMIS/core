<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffExtracurricularsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator) {
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		// 'ExtracurricularType',
		return $validator;
	}

	public function beforeAction() {
		
	}
}
