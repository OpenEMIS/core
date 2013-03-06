<?php
App::uses('AppModel', 'Model');

class InstitutionSector extends AppModel {
	
	public function findListAsSubgroups() {
		return $this->findList(true);
	}
}
