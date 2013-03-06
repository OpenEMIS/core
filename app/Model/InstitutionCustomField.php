<?php
App::uses('AppModel', 'Model');

class InstitutionCustomField extends AppModel {
	public $hasMany = array(
		'InstitutionCustomFieldOption' => array('order'=>'order')
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
	

}
