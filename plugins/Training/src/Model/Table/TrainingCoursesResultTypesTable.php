<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingCoursesResultTypesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('TrainingCourses', ['className' => 'Training.TrainingCourses']);
		$this->belongsTo('TrainingResultTypes', ['className' => 'Training.TrainingResultTypes']);
	}
}
