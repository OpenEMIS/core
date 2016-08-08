<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingCoursesTargetPopulationsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('TrainingCourses', ['className' => 'Training.TrainingCourses']);
		$this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles', 'foreignKey' => 'target_population_id',]);
	}
}
