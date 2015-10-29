<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingResultTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('TrainingCoursesResultTypes', ['className' => 'Training.TrainingCoursesResultTypes', 'foreignKey' => 'training_result_type_id']);
	}
}
