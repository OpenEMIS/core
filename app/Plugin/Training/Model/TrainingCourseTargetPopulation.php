<?php
class TrainingCourseTargetPopulation extends TrainingAppModel {
	//public $useTable = 'student_health_histories';
	
	public $belongsTo = array(
		'TrainingCourse' => array(
			'className' => 'TrainingCourse',
			'foreignKey' => 'training_course_id'
		),
		'TeacherPositionTitle' => array(
			'className' => 'TeacherPositionTitle',
			'foreignKey' => 'teacher_position_title_id'
		)
	);

}
?>