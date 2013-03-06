<?php
App::uses('AppModel', 'Model');

class InstitutionSiteCustomField extends AppModel {
	public $hasMany = array(
		'InstitutionSiteCustomFieldOption' => array('order'=>'order')
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
	

}
