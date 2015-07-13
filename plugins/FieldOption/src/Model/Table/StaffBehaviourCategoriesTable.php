<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffBehaviourCategoriesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('StaffBehaviours', ['className' => 'Staff.StaffBehaviours', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
