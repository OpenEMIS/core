<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentGuardiansTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('StudentUsers', ['className' => 'User.Users', 'foreignKey' => 'student_user_id']);
		$this->belongsTo('GuardianUsers', ['className' => 'User.Users', 'foreignKey' => 'guardian_user_id']);
		$this->belongsTo('GuardianRelations', ['className' => 'Student.GuardianRelations']);
		// $this->belongsTo('GuardianEducationLevels', ['className' => 'FieldOption.GuardianEducationLevels']); // Not in used currently
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('guardian_relation_id', [
			])
			->add('guardian_education_level_id', [
			])
			;
	}
}
