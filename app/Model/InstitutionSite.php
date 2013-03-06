<?php
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
}
