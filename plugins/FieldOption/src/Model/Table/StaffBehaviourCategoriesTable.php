<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffBehaviourCategoriesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);

		$this->hasMany('StaffBehaviours', ['className' => 'Staff.StaffBehaviours', 'foreignKey' => 'staff_behaviour_category_id']);
	}
}
