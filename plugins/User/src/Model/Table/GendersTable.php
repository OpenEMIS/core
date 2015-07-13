<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class GendersTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		// todo-mlee sort out all these hasmany associations when census is created
		$this->hasMany('Users', ['className' => 'User.Users', 'dependent' => true, 'cascadeCallbacks' => true]);
		// $this->hasMany('CensusBehaviours', ['className' => 'User.CensusBehaviours']);
		// $this->hasMany('CensusTeachers', ['className' => 'User.CensusTeachers']);
		// $this->hasMany('CensusTeacherTrainings', ['className' => 'User.CensusTeacherTrainings']);
		// $this->hasMany('CensusAttendances', ['className' => 'User.CensusAttendances']);
		// $this->hasMany('CensusStudents', ['className' => 'User.CensusStudents']);
		// $this->hasMany('CensusTeacherFtes', ['className' => 'User.CensusTeacherFtes']);
		// $this->hasMany('CensusStaffs', ['className' => 'User.CensusStaffs']);
		// $this->hasMany('CensusSanitations', ['className' => 'User.CensusSanitations']);
		// $this->hasMany('CensusGraduates', ['className' => 'User.CensusGraduates']);	
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

}
