<?php
App::uses('AppModel', 'Model');

class CensusRoom extends AppModel {
    public $belongsTo = array(
		'InfrastructureRoom' => array('foreignKey' => 'infrastructure_room_id')
	);

	/*public function getTest() {
        $this->bindModel(array(
            'belongsTo'=> array(
				'SchoolYear' => array('foreignKey' => 'school_year_id'),
				'InfrastructureStatus' => array('foreignKey' => 'infrastructure_status_id'),
				'InstitutionSite'=>array('foreignKey' => 'institution_site_id'),
				'Institution' => array(
	                'joinTable'  => 'institutions',
					'foreignKey' => false,
	                'conditions' => array(' Institution.id = InstitutionSite.institution_id '),
	            )
   			)
        ));
        return $this->find('all', array('limit' => 5));
	}*/
}