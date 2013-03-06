<?php
App::uses('AppModel', 'Model');

class InstitutionSiteHistory extends AppModel {
	var $useTable = 'institution_site_history';
        public $belongsTo = array(
		'Institution',
		'InstitutionSiteStatus',
		'InstitutionSiteLocality',
		'Area',
		'InstitutionSiteOwnership',
                'InstitutionSiteType'
	);
}
