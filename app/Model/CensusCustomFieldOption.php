<?php
App::uses('AppModel', 'Model');

class CensusCustomFieldOption extends AppModel {
	public $belongsTo = array(
		'CensusCustomField'
	);
	
}
