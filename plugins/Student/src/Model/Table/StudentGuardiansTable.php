<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentGuardiansTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('StudentUsers', ['className' => 'User.Users', 'foreignKey' => 'student_user_id']);
		$this->belongsTo('GuardianUsers', ['className' => 'User.Users', 'foreignKey' => 'guardian_user_id']);
		$this->belongsTo('GuardianRelations', ['className' => 'FieldOption.GuardianRelations']);
		$this->belongsTo('GuardianEducationLevels', ['className' => 'FieldOption.GuardianEducationLevels']);
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->add('guardian_relation_id', [
			])
			->add('guardian_education_level_id', [
			])
			;
	}
}
