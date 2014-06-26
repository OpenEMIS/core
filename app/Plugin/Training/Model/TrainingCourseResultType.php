<?php
class TrainingCourseResultType extends TrainingAppModel {
	
	public $belongsTo = array(
		'TrainingResultType' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'training_result_type_id'
		),
		'TrainingCourse' => array(
			'className' => 'TrainingCourse',
		  	'foreignKey' => 'training_course_id'
		)
	);

}