<?php
App::uses('AppModel', 'Model');

class InstitutionHistory extends AppModel {
	var $useTable = 'institution_history';
        public $belongsTo = array(
		'InstitutionStatus',
		'InstitutionProvider',
		'Area',
		'InstitutionSector'
	);
}
