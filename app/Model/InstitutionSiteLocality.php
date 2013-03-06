<?php
App::uses('AppModel', 'Model');

class InstitutionSiteLocality extends AppModel {
	public $hasMany = array('InstitutionsSite');
	
	public function findListAsSubgroups() {
		return $this->findList(true);
	}
}
