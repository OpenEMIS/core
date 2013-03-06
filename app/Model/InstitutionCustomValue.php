<?php
App::uses('AppModel', 'Model');

class InstitutionCustomValue extends AppModel {
	public $belongsTo = array(
		'InstitutionCustomField','Institution'
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
}
