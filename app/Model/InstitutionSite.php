<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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
	
	public function getInstitutionsByAreas($areas) {
		$list = $this->find('all', array(
			'recursive' => 0,
			'fields' => array('InstitutionSite.id', 'InstitutionSite.institution_id'),
			'conditions' => array('InstitutionSite.area_id' => $areas)
		));
		return $list;
	}

	public function calculateTotalSitesByAreaId($areaId, $year) {

		$startDate = mktime(0,0,0,1,1,$year);
		$endDate = mktime(0,0,0,12,31,$year);

		$this->unbindModel(array('belongsTo' => array('Institution', 'InstitutionSiteStatus', 'InstitutionSiteLocality', 'InstitutionSiteType', 'InstitutionSiteOwnership')));

		$options['fields'] = array(
            'COUNT(InstitutionSite.id) as TotalInstitutionSites'
        );

		// $options['conditions'] = array('AND' => array('InstitutionSite.area_id' => $areaId, 'InstitutionSite.date_opened >=' => date('Y-m-d', $startDate), 'InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('InstitutionSite.area_id'=>null));
// $options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'InstitutionSite.area_id' => $areaId, 'InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('InstitutionSite.area_id'=>null, 'InstitutionSite.date_closed' => null, 'InstitutionSite.date_closed' => ""));
		$options['conditions'] = array('AND' => array('InstitutionSite.area_id' => $areaId, 'InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('InstitutionSite.area_id'=>null, 'InstitutionSite.date_closed' => null, 'InstitutionSite.date_closed' => "0000-00-00"));
		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		$data = ($values[0]['TotalInstitutionSites'] > 0) ? $values[0]['TotalInstitutionSites'] : 0;
		return $data;
	}

	public function calculateTotalSitesByLevelAndAreaId($year, $areaId, $eduCycleId) {

		$startDate = mktime(0,0,0,1,1,$year);
		$endDate = mktime(0,0,0,12,31,$year);

		$this->unbindModel(array('belongsTo' => array('Institution', 'InstitutionSiteStatus', 'InstitutionSiteLocality', 'InstitutionSiteType', 'InstitutionSiteOwnership')));
		$this->bindModel(array('hasOne' => array(
			'InstitutionSiteProgramme' =>
            array(
                'className' => 'InstitutionSiteProgramme',
                'joinTable' => 'institution_site_programmes',
				'foreignKey' => false,
				'dependent'    => false,
                'conditions' => array(' InstitutionSite.id = InstitutionSiteProgramme.institution_site_id '),
            ),
            'EducationProgramme' =>
            array(
                'className' => 'EducationProgramme',
                'joinTable' => 'education_programmes',
				'foreignKey' => false,
				'dependent'    => false,
                'conditions' => array(' EducationProgramme.id = InstitutionSiteProgramme.education_programme_id '),
            ),
		)));
		$options['fields'] = array(
			// 'Area.id AS AreaId',
			// 'Area.name AS AreaName',
            'COUNT(InstitutionSite.id) as TotalInstitutionSites'
        );

		// $options['group'] = array('InstitutionSite.area_id');
		// $options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'InstitutionSite.area_id' => $areaId), 'NOT' => array('InstitutionSite.area_id'=>null));
		// $options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'InstitutionSite.area_id' => $areaId), 'NOT' => array('InstitutionSite.area_id'=>null));
		$options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'InstitutionSite.area_id' => $areaId, 'InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('InstitutionSite.area_id'=>null, 'InstitutionSite.date_closed' => null, 'InstitutionSite.date_closed' => "0000-00-00"));
		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		return $values;
	}
}
