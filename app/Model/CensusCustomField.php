<?php
App::uses('AppModel', 'Model');

class CensusCustomField extends AppModel {
	public $hasMany = array(
		'CensusCustomFieldOption'=> array('order'=>'order')
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
	

}
