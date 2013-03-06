<?php
App::uses('AppModel', 'Model');

class InstitutionSiteType extends AppModel {
       
	
    public function getSiteTypesList(){
        return $this->find('list',array('conditions'=>array('visible'=>1)));
    }
}
