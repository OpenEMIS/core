<?php
class TrainingCourseSpecialisation extends TrainingAppModel {
	
	public $belongsTo = array(
		'QualificationSpecialisation' => array(
			'className' => 'QualificationSpecialisation',
			'foreignKey' => 'qualification_specialisation_id'
		),
		'TrainingCourse' => array(
			'className' => 'TrainingCourse',
		  	'foreignKey' => 'training_course_id'
		)
	);

}