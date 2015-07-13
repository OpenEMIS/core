<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class GuardianEducationLevelsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');

		$this->hasMany('StudentGuardians', ['className' => 'Student.StudentGuardians']);
	}
}
