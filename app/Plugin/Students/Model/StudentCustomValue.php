<?php

class StudentCustomValue extends StudentsAppModel {
	public $belongsTo = array(
		'StudentCustomField', 
		'Student'
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
}
