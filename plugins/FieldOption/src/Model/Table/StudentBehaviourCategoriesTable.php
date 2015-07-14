<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentBehaviourCategoriesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('StudentBehaviours', ['className' => 'Student.StudentBehaviours', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
