<?php
App::uses('AppModel', 'Model');

class InstitutionProvider extends AppModel {
        
	var $hasMany = array('Institution');
	
	public function getProviders() {
		$this->unbindModel(array('hasMany' => array('Institution')));
        // $records = $this->find('list', array('conditions' => array('EducationCycle.visible' => 1)));
        $records = $this->find('all', array('conditions' => array('InstitutionProvider.visible' => 1)));
        // $records = $this->find('all', array('conditions' => array('InstitutionProvider.visible' => 1), 'order' => array('InstitutionProvider.name' => 'asc')));
        $records = $this->formatArray($records);

        return $records;
	}
}
