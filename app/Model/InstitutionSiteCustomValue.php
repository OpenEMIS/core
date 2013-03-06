<?php
App::uses('AppModel', 'Model');

class InstitutionSiteCustomValue extends AppModel {
	public $belongsTo = array(
		'InstitutionSiteCustomField','InstitutionSite'
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
	

}
