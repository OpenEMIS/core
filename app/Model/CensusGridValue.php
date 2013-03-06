<?php
App::uses('AppModel', 'Model');

class CensusGridValue extends AppModel {
	var $useTable = 'census_grid_values';

	

	public function getTest() {
        $this->bindModel(array(
		    'belongsTo'=> array(
		        'SchoolYear' => array('foreignKey' => 'school_year_id'),
		        'CensusGrid',
		        'CensusGridXCategory',
		        'CensusGridYCategory',
		        'InstitutionSite'=>array('foreignKey' => 'institution_site_id'),
		        'Institution' => array(
		            'joinTable'  => 'institutions',
		            'foreignKey' => false,
		            'conditions' => array(' Institution.id = InstitutionSite.institution_id '),
		        )
		    )
			));

        $data = $this->find('all',array('fields'=>array('SchoolYear.name AS AcademicYear','Institution.name AS InstitutionName','InstitutionSite.name AS SiteName','CensusGridXCategory.name AS GridXCategory','CensusGridYCategory.name AS GridYCategory','CensusGridValue.value AS Value'), 'limit' => 5));
		return $data;
    }
}
