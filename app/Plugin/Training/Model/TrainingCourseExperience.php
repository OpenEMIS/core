<?php
class TrainingCourseExperience extends TrainingAppModel {
	
	public $belongsTo = array(
		'TrainingCourse' => array(
			'className' => 'TrainingCourse',
		  	'foreignKey' => 'training_course_id'
		)
	);

}