<?php
App::uses('AppModel', 'Model');

class InstitutionCustomFieldOption extends AppModel {
	public $belongsTo = array(
		'InstitutionCustomField'
	);
	
}
