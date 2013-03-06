<?php

class TeacherCustomField extends TeachersAppModel {
	public $hasMany = array(
		'TeacherCustomFieldOption' => array('order'=>'order')
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
	

}
