<?php
class TrainingCourseProvider extends TrainingAppModel {
	
	public $belongsTo = array(
		'TrainingCourse' => array(
			'className' => 'TrainingCourse',
			'foreignKey' => 'training_course_id'
		),
		'TrainingProvider'
	);

}
?>