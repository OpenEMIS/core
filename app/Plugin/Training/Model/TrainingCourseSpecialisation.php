<?php
class TrainingCourseSpecialisation extends TrainingAppModel {
	
	public $belongsTo = array(
		'Training.QualificationSpecialisation',
		'TrainingCourse' => array(
			'className' => 'TrainingCourse',
		  	'foreignKey' => 'training_course_id'
		)
	);

}