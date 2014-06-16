<?php
class TrainingCourseTargetPopulation extends TrainingAppModel {
	
	public $belongsTo = array(
		'TrainingCourse' => array(
			'className' => 'TrainingCourse',
			'foreignKey' => 'training_course_id'
		),
		'StaffPositionTitle' => array(
			'className' => 'StaffPositionTitle',
			'foreignKey' => 'staff_position_title_id'
		)
	);

}
?>