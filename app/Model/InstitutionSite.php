<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

App::uses('AppModel', 'Model');

class InstitutionSite extends AppModel {
    
    public $belongsTo = array(
		'Institution',
		'InstitutionSiteStatus',
		'InstitutionSiteLocality',
		'InstitutionSiteType',
		'InstitutionSiteOwnership',
		'Area'
	);
	
	public $actsAs = array(
		'TrackHistory',
		'CascadeDelete' => array(
			'cascade' => array(
				'InstitutionSiteAttachment',
				'InstitutionSiteCustomValue',
				'InstitutionSiteBankAccount',
				'InstitutionSiteProgramme',
				'CensusBuilding',
				'CensusEnergy',
				'CensusFinance',
				'CensusFurniture',
				'CensusGridValue',
				'CensusResource',
				'CensusRoom',
				'CensusSanitation',
				'CensusStaff',
				'CensusTeacherTraining',
				'CensusWater'
			)
		)
	);
	
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Name'
			)
		),
		'code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Code'
			),
			'ruleUnique' => array(
        		'rule' => 'isUnique',
        		'required' => true,
        		'message' => 'Please enter a unique Code'
		    )
		),
		'address' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Address'
			)
		),
		'postal_code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Postal Code'
			)
		),
		'institution_site_locality_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a Provider'
			)
		),
		'institution_site_status_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a Status'
			)
		),
		'institution_site_type_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a Site Type'
			)
		),
		'institution_site_ownership_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select an Ownership'
			)
		),
		'area_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select an Area'
			)
		),
		'email' => array(
			'ruleRequired' => array(
				'rule' => 'email',
				'allowEmpty' => true,
				'message' => 'Please enter a valid Email'
			)
		),
		'date_opened' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select the Date Opened'
			),
			'ruleCompare' => array(
				'rule' => array('comparison', 'NOT EQUAL', '0000-00-00'),
				'required' => true,
				'message' => 'Please select the Date Opened'
			)
		),'longitude' => array(
				'rule' => array('checkLongitude'),
				'allowEmpty' => true,
				'message' => 'Please enter a valid Longitude'
		),'latitude' => array(
				'rule' => array('checkLatitude'),
				'allowEmpty' => true,
				'message' => 'Please enter a valid Latitude'
		)
	);
    
	public function checkNumeric($arrVal){
		$o = array_values($arrVal);
		
		if(is_float($o[0]) || is_float($o[0])){
			
			return true;
		}
		
		return false;
		
	}

    public function checkLongitude($check){

        $isValid = false;
        $longitude = trim($check['longitude']);

        if(is_numeric($longitude) && floatval($longitude) >= -180.00 && floatval($longitude <= 180.00)){
            $isValid = true;
        }
        return $isValid;
    }

    public function checkLatitude($check){

        $isValid = false;
        $latitude = trim($check['latitude']);

        if(is_numeric($latitude) && floatval($latitude) >= -90.00 && floatval($latitude <= 90.00)){
            $isValid = true;
        }
        return $isValid;
    }

	public function getLookupVariables() {
		$lookup = array(
			'Type' => array('model' => 'InstitutionSiteType'),
			'Ownership' => array('model' => 'InstitutionSiteOwnership'),
			'Locality' => array('model' => 'InstitutionSiteLocality'),
			'Status' => array('model' => 'InstitutionSiteStatus')
		);
		return $lookup;
	}
	
	// Used by SecurityController
	public function getGroupAccessList($exclude) {
		$conditions = array();
		if(!empty($exclude)) {
			$conditions['InstitutionSite.id NOT'] = $exclude;
		}
		
		$data = $this->find('list', array(
			'fields' => array('Institution.id', 'Institution.name'),
			'joins' => array(
				array(
					'table' => 'institutions',
					'alias' => 'Institution',
					'conditions' => array('Institution.id = InstitutionSite.institution_id')
				)
			),
			'conditions' => $conditions,
			'group' => array('Institution.id HAVING COUNT(InstitutionSite.id) > 0'),
			'order' => array('Institution.name')
		));
		return $data;
	}
	
	public function getGroupAccessValueList($parentId, $exclude) {
		$conditions = array('InstitutionSite.institution_id' => $parentId);
		if(!empty($exclude)) {
			$conditions['InstitutionSite.id NOT'] = $exclude;
		}
		
		$data = $this->find('list', array(
			'fields' => array('InstitutionSite.id', 'InstitutionSite.name'),
			'conditions' => $conditions,
			'order' => array('InstitutionSite.name')
		));
		return $data;
	}
	
	public function getInstitutionsByAreas($areas) {
		$list = $this->find('all', array(
			'recursive' => 0,
			'fields' => array('InstitutionSite.id', 'InstitutionSite.institution_id'),
			'conditions' => array('InstitutionSite.area_id' => $areas)
		));
		return $list;
	}
	
	// Yearbook
	public function getCountByCycleId($yearId, $cycleId, $extras=array()) {
		$options = array('recursive' => -1);
		
		$joins = array(
			array(
				'table' => 'institution_site_programmes',
				'alias' => 'InstitutionSiteProgramme',
				'conditions' => array('InstitutionSiteProgramme.institution_site_id = InstitutionSite.id')
			),
			array(
				'table' => 'education_programmes',
				'alias' => 'EducationProgramme',
				'conditions' => array(
					'EducationProgramme.id = InstitutionSiteProgramme.education_programme_id',
					'EducationProgramme.education_cycle_id = ' . $cycleId
				)
			),
			array(
				'table' => 'school_years',
				'alias' => 'SchoolYear',
				'conditions' => array(
					'SchoolYear.id = ' . $yearId,
					'SchoolYear.end_date >= InstitutionSite.date_opened'
				)
			)
		);
		if(isset($extras['providerId'])) {
			$joins[] = array(
				'table' => 'institutions',
				'alias' => 'Institution',
				'conditions' => array(
					'Institution.id = InstitutionSite.institution_id',
					'Institution.institution_provider_id = ' . $extras['providerId']
				)
			);
		}
		if(isset($extras['areaId'])) {
			$joins[] = array(
				'table' => 'areas',
				'alias' => 'AreaSite',
				'conditions' => array('AreaSite.id = InstitutionSite.area_id')
			);
			$joins[] = array(
				'table' => 'areas',
				'alias' => 'Area',
				'conditions' => array(
					'Area.id = ' . $extras['areaId'],
					'Area.lft <= AreaSite.lft',
					'Area.rght >= AreaSite.rght'
				)
			);
		}
		$options['joins'] = $joins;
		$options['group'] = array('EducationProgramme.education_cycle_id');
		
		$data = $this->find('count', $options);
		if(empty($data)) {
			$data = 0;
		}
		return $data;
	}
	
	public function getCountByAreaId($yearId, $areaId) {
		$data = $this->find('count', array(
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'areas',
					'alias' => 'AreaSite',
					'conditions' => array('AreaSite.id = InstitutionSite.area_id')
				),
				array(
					'table' => 'areas',
					'alias' => 'Area',
					'conditions' => array(
						'Area.id = ' . $areaId,
						'Area.lft <= AreaSite.lft',
						'Area.rght >= AreaSite.rght'
					)
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array(
						'SchoolYear.id = ' . $yearId,
						'SchoolYear.end_date >= InstitutionSite.date_opened'
					)
				)
			)
		));
		return $data;
	}
	// End Yearbook
}