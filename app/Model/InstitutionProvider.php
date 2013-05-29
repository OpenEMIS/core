<?php
App::uses('AppModel', 'Model');

class InstitutionProvider extends AppModel {
        
	var $hasMany = array('Institution');

    public function findListAsSubgroups() {
        return $this->findList(true);
    }
	
}
