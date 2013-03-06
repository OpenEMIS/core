<?php
App::uses('AppModel', 'Model');

class CensusCustomValue extends AppModel {
	public $belongsTo = array(
		'CensusCustomField','InstitutionSite'
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
	

}
