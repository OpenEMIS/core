<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingCoursesPrerequisitesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('TrainingCourses', ['className' => 'Training.TrainingCourses']);
		$this->belongsTo('PrerequisitesTrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'prerequisite_training_course_id']);
	}
}
