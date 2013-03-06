<?php

class StudentCustomField extends StudentsAppModel {
	public $hasMany = array(
		'StudentCustomFieldOption' => array('order'=>'order')
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
	

}
