<?php
App::uses('AppModel', 'Model');

class CensusGrid extends AppModel {
	public $hasMany = array('CensusGridXCategory','CensusGridYCategory');
	public $belongsTo = array('InstitutionSiteType');
}
