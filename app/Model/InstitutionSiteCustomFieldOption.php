<?php
App::uses('AppModel', 'Model');

class InstitutionSiteCustomFieldOption extends AppModel {
	public $belongsTo = array(
		'InstitutionSiteCustomField' 
	);
	
}
