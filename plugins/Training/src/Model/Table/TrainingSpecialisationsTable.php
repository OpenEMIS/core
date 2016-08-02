<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingSpecialisationsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
        $this->table('training_specialisations');
		parent::initialize($config);
		$this->hasMany('TrainingCoursesSpecialisations', ['className' => 'Training.TrainingCoursesSpecialisations', 'foreignKey' => 'training_specialisation_id']);
	}
}
