<?php

class StaffCustomField extends StaffAppModel {
	public $hasMany = array(
		'StaffCustomFieldOption' => array('order'=>'order')
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
	

}
