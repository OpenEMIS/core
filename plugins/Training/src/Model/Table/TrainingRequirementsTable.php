<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingRequirementsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
        $this->table('training_requirements');
		parent::initialize($config);
		$this->hasMany('TrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_requirement_id']);
		$this->hasMany('TrainingNeeds', ['className' => 'Staff.TrainingNeeds', 'foreignKey' => 'training_requirement_id']);
	}
}
