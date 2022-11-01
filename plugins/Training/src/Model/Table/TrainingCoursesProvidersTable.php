<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class TrainingCoursesProvidersTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('TrainingCourses', ['className' => 'Training.TrainingCourses']);
		$this->belongsTo('TrainingProviders', ['className' => 'Training.TrainingProviders']);
	}
}
