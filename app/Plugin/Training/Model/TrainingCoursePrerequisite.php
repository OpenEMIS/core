<?php
class TrainingCoursePrerequisite extends TrainingAppModel {
	//public $useTable = 'student_health_histories';
	
	public $belongsTo = array(
		'TrainingCourse' => array(
			'className' => 'TrainingCourse',
			'foreignKey' => 'training_course_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'TrainingPrerequisiteCourse' => array(
			'className' => 'TrainingCourse',
			'foreignKey' => 'training_prerequisite_course_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

}
?>
