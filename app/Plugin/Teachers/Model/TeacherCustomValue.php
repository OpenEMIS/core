<?php

class TeacherCustomValue extends TeachersAppModel {
	public $belongsTo = array(
		'TeacherCustomField', 
		'Teacher'
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
}
