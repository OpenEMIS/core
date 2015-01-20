<?php
class TrainingCourseTargetPopulation extends TrainingAppModel {
	
	public $belongsTo = array(
		'TrainingCourse' => array(
			'className' => 'TrainingCourse',
			'foreignKey' => 'training_course_id'
		),
		'StaffPositionTitle'
	);

}
?>