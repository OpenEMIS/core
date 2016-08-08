<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingFieldStudiesTable extends ControllerActionTable {
	public function initialize(array $config)
    {
		$this->addBehavior('FieldOption.FieldOption');
        $this->table('training_field_of_studies');
		parent::initialize($config);
		$this->hasMany('TrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_field_of_study_id']);
	}
}
