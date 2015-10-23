<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingSpecialisationsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('TrainingCoursesSpecialisations', ['className' => 'Training.TrainingCoursesSpecialisations', 'foreignKey' => 'training_specialisation_id', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
