<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class EmploymentTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('Employments', ['className' => 'Staff.Employments', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
