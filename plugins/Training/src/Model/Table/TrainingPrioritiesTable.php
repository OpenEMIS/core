<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingPrioritiesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		// $this->hasMany('TrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
