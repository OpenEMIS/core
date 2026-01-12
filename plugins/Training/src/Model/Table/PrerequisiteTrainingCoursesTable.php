<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;

class PrerequisiteTrainingCoursesTable extends AppTable {
	public function initialize(array $config): void {
		$this->setTable('training_courses');
		parent::initialize($config);
	}
}
