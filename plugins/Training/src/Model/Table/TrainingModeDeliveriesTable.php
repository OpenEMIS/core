<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingModeDeliveriesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('TrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_mode_of_delivery_id', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
