<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class GuardianEducationLevelsTable extends ControllerActionTable {
	public function initialize(array $config)
    {
		$this->addBehavior('FieldOption.FieldOption');

		$this->hasMany('StudentGuardians', ['className' => 'Student.StudentGuardians', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
