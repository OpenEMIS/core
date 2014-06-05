<?php
class TrainingCourseProvider extends TrainingAppModel {
	//public $useTable = 'student_health_histories';
	
	public $belongsTo = array(
		'TrainingCourse' => array(
			'className' => 'TrainingCourse',
			'foreignKey' => 'training_course_id'
		),
		'TrainingProvider' => array(
			'className' => 'TrainingProvider',
			'foreignKey' => 'training_provider_id'
		),
	);

}
?>