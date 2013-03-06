<?php

class StaffCustomValue extends StaffAppModel {
	public $belongsTo = array(
		'StaffCustomField', 'Staff.Staff'
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
}
