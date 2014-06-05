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
		'InstitutionSiteStatus',
		'InstitutionSiteLocality',
		'InstitutionSiteType',
		'InstitutionSiteOwnership',
		'Area',
		'InstitutionSiteProvider' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'institution_site_provider_id'
		),
		'InstitutionSiteSector' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'institution_site_sector_id'
		),
		'InstitutionSiteGender' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'institution_site_gender_id'
		)
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
		),
		'CustomReport' => array(
			'_default' => array('visible'),
			'belongsTo' => array(
				'Area' => array('lft', 'rght')
			)
		),
		'DatePicker' => array('date_opened', 'date_closed'),
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
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
				'message' => 'Please select a Locality'
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
				'message' => 'Please select a Type'
			)
		),
		'institution_site_ownership_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select an Ownership'
			)
		),
//		'area_id' => array(
//			'ruleRequired' => array(
//				'rule' => array('comparison', '>', 0),
//				'required' => true,
//				'message' => 'Please select an Area'
//			)
//		),
		'email' => array(
			'ruleRequired' => array(
				'rule' => 'email',
				'allowEmpty' => true,
				'message' => 'Please enter a valid Email'
			)
		),
//		'date_opened' => array(
//			'ruleRequired' => array(
//				'rule' => 'notEmpty',
//				'required' => true,
//				'message' => 'Please select the Date Opened'
//			),
//			'ruleCompare' => array(
//				'rule' => array('comparison', 'NOT EQUAL', '0000-00-00'),
//				'required' => true,
//				'message' => 'Please select the Date Opened'
//			)
//		),
		'longitude' => array(
				'rule' => array('checkLongitude'),
				'allowEmpty' => true,
				'message' => 'Please enter a valid Longitude'
		),
		'latitude' => array(
				'rule' => array('checkLatitude'),
				'allowEmpty' => true,
				'message' => 'Please enter a valid Latitude'
		),
		'institution_site_provider_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a Provider'
			)
		),
		'institution_site_sector_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a Sector'
			)
		)
	);
	
	public $reportMapping = array(
		1 => array(
			'fields' => array(
				'InstitutionSite' => array(
					'name' => 'Institution Name',
					'code' => 'Institution Code'
				),
				'InstitutionSiteType' => array(
					'name' => 'Institution Type'
				),
				'InstitutionSiteOwnership' => array(
					'name' => 'Institution Ownership'
				),
				'InstitutionSiteStatus' => array(
					'name' => 'Institution Status'
				),
				'InstitutionSite2' => array(
					'date_opened' => 'Date Opened',
					'date_closed' => 'Date Closed',
				),
				'Area' => array(
					'name' => 'Area'
				),
				'AreaEducation' => array(
					'name' => 'Area (Education)'
				),
				'InstitutionSite3' => array(
					'address' => 'Address',
					'postal_code' => 'Postal Code',
					'longitude' => 'Longitude',
					'latitude' => 'Latitude',
					'contact_person' => 'Contact Person',
					'telephone' => 'Telephone',
					'fax' => 'Fax',
					'email' => 'Email',
					'website' => 'Website'
				),
				'InstitutionSiteCustomField' => array(
				)
			),
			'fileName' => 'Report_General_Overview'
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
		//$conditions = array('InstitutionSite.institution_id' => $parentId);
		if(!empty($exclude)) {
			$conditions['InstitutionSite.id NOT'] = $exclude;
		}
		
		$data = $this->find('list', array(
			'fields' => array('InstitutionSite.id', 'InstitutionSite.name'),
			//'conditions' => $conditions,
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
        
	public function getQueryFromInstitutionsWithoutSites($params) {
		$joins = array(
			array(
				'table' => 'security_group_users',
				'alias' => 'CreatorGroup',
				'conditions' => array('CreatorGroup.security_user_id = Institution.created_user_id')
			),
			array(
				'table' => 'security_group_users',
				'alias' => 'UserGroup',
				'conditions' => array(
					'UserGroup.security_group_id = CreatorGroup.security_group_id',
					'UserGroup.security_user_id = ' . $params['userId']
				)
			)
		);
		$conditions = array(
			'NOT EXISTS (SELECT id FROM institution_sites WHERE institution_id = Institution.id)',
			'OR' => array(
				'CreatorGroup.security_group_id IS NULL',
				'AND' => array(
					'CreatorGroup.security_group_id IS NOT NULL',
					'UserGroup.security_group_id IS NOT NULL'
				)
			)
		);
		$dbo = $this->getDataSource();
		$query = $dbo->buildStatement(array(
			'fields' => array('Institution.id'),
			'table' => $dbo->fullTableName($this),
			'alias' => get_class($this),
			'limit' => null, 
			'offset' => null,
			'joins' => $joins,
			'conditions' => $conditions,
			'group' => array('Institution.id'),
			'order' => null
		), $this);
		return $query;
	}
	
	// To get the list of institutions based on the security settings on areas
	public function getQueryFromSecurityAreas($params) {
		$joins = array(
			array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.institution_id = Institution.id')
			),
			array(
				'table' => 'areas',
				'alias' => 'Area',
				'conditions' => array('Area.id = InstitutionSite.area_id')
			),
			array( // to get all child areas including the current parent
				'table' => 'areas',
				'alias' => 'AreaAll',
				'conditions' => array('AreaAll.lft <= Area.lft', 'AreaAll.rght >= Area.rght')
			),
			array(
				'table' => 'security_group_areas',
				'alias' => 'SecurityGroupArea',
				'conditions' => array('SecurityGroupArea.area_id = AreaAll.id')
			),
			array(
				'table' => 'security_group_users',
				'alias' => 'SecurityGroupUser',
				'conditions' => array(
					'SecurityGroupUser.security_group_id = SecurityGroupArea.security_group_id',
					'SecurityGroupUser.security_user_id = ' . $params['userId']
				)
			)
		);
		$dbo = $this->getDataSource();
		$query = $dbo->buildStatement(array(
			'fields' => array('Institution.id'),
			'table' => $dbo->fullTableName($this),
			'alias' => get_class($this),
			'limit' => null, 
			'offset' => null,
			'joins' => $joins,
			'conditions' => null,
			'group' => array('Institution.id'),
			'order' => null
		), $this);
		return $query;
	}
	
	public function getQueryFromSecuritySites($params) {
		$joins = array(
			array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.institution_id = Institution.id')
			),
			array(
				'table' => 'security_group_institution_sites',
				'alias' => 'SecurityGroupInstitutionSite',
				'conditions' => array('SecurityGroupInstitutionSite.institution_site_id = InstitutionSite.id')
			),
			array(
				'table' => 'security_group_users',
				'alias' => 'SecurityGroupUser',
				'conditions' => array(
					'SecurityGroupUser.security_group_id = SecurityGroupInstitutionSite.security_group_id',
					'SecurityGroupUser.security_user_id = ' . $params['userId']
				)
			)
		);
		$dbo = $this->getDataSource();
		$query = $dbo->buildStatement(array(
			'fields' => array('Institution.id'),
			'table' => $dbo->fullTableName($this),
			'alias' => get_class($this),
			'limit' => null, 
			'offset' => null,
			'joins' => $joins,
			'conditions' => null,
			'group' => array('Institution.id'),
			'order' => null
		), $this);
		return $query;
	}
	
	public function paginateJoins($joins, $params) {
                
		if(strlen($params['SearchKey']) != 0) {
			$joins[] = array(
				'table' => 'institution_site_history',
				'alias' => 'InstitutionSiteHistory',
				'type' => 'LEFT',
				'conditions' => array('InstitutionSiteHistory.institution_site_id = InstitutionSite.id')
			);
		}
                
		$joins[] = array(
			'table' => 'institution_site_types',
			'alias' => 'InstitutionSiteType',
                        'type' => 'LEFT',
			'conditions' => array('InstitutionSite.institution_site_type_id = InstitutionSiteType.id')
		);
                
        $joins[] = array(
			'table' => 'areas',
			'alias' => 'Area',
                        'type' => 'LEFT',
			'conditions' => array('InstitutionSite.area_id = Area.id')
		);
                
                //aids
                /*if(count($params['AdvancedSearch']) > 0) {
                    foreach ($params['AdvancedSearch'] as $key => $value) {
                        if(strpos($key,'CustomValue') > 0){
                            $rawTableName = Inflector::tableize($key);
                            $fkey = strtolower(str_replace("_custom_values", "_id", $rawTableName));
                            $joins[] = array(
				'table' => $rawTableName,
				'alias' => $key,
				'type' => 'LEFT',
				'conditions' => array($key.'.'.$fkey.' = '.  str_replace('CustomValue', '', $key).'.id')
                            );
                        }
                    }
		}*/
                
//		$joins[] = array(
//			'table' => 'institution_providers',
//			'alias' => 'InstitutionProvider',
//			'conditions' => array('InstitutionProvider.id = Institution.institution_provider_id')
//		);
//		$joins[] = array(
//			'table' => 'institution_sectors',
//			'alias' => 'InstitutionSector',
//			'conditions' => array('InstitutionSector.id = Institution.institution_sector_id')
//		);
                
		return $joins;
	}
	
	public function paginateConditions($params) {
		$conditions = array();
		if(strlen($params['SearchKey']) != 0) {
			$search = "%".$params['SearchKey']."%";
			$conditions['OR'] = array(
				'InstitutionSite.name LIKE' => $search,
				'InstitutionSite.code LIKE' => $search,
				'InstitutionSiteHistory.name LIKE' => $search,
				'InstitutionSiteHistory.code LIKE' => $search
			);
		}
		if(!is_null($params['AdvancedSearch'])) {
			$arrAdvanced = $params['AdvancedSearch'];
                        //pr($arrAdvanced);
			if(count($arrAdvanced) > 0 ){
                            foreach($arrAdvanced as $key => $advanced){
                               //echo $key;
                                if($key == 'Search'){
                                    if($advanced['area_id'] > 0) { // search by area and all its children
                                            $joins = array(
                                                    array(
                                                            'table' => 'areas',
                                                            'alias' => 'Area',
                                                            'conditions' => array('Area.id = InstitutionSite.area_id')
                                                    ),
                                                    array(
                                                            'table' => 'areas',
                                                            'alias' => 'AreaAll',
                                                            'conditions' => array('AreaAll.lft <= Area.lft', 'AreaAll.rght >= Area.rght', 'AreaAll.id = ' . $advanced['area_id'])
                                                    )
                                            );
                                            $dbo = $this->getDataSource();
                                            $query = $dbo->buildStatement(array(
                                                    'fields' => array('InstitutionSite.id'),
                                                    'table' => 'institution_sites',
                                                    'alias' => 'InstitutionSite',
                                                    'limit' => null, 
                                                    'offset' => null,
                                                    'joins' => $joins,
                                                    //'conditions' => array('InstitutionSite.institution_id = Institution.id'),
                                                    'group' => array('InstitutionSite.id'),
                                                    'order' => null
                                            ), $this);
                                            //pr($query);
                                            $conditions[] = 'InstitutionSite.id IN (' . $query . ')';
                                    }
                                }
                                /*
                                 * 
                                 * 
                                 `Institution`.id in (SELECT institution_id FROM institution_custom_values WHERE id = 10) 

AND 
`Institution`.id in (SELECT institution_id FROM institution_sites WHERE institution_sites.id in (SELECT institution_site_id FROM  institution_site_custom_values WHERE id = 10)) 
                                 */
                                if(strpos($key,'CustomValue') > 0){
                                   //pr($advanced);
                                   
                                   if($key == 'InstitutionSiteCustomValue'){ //hack
                                        $arrCond = array();
                                   //echo $key; pr(array('or'=>$arrCond));die;
                                       
                                        $dbo = $this->getDataSource();
                                        $rawTableName = Inflector::tableize($key);
                                        $mainTable = str_replace("CustomValue","",$key); //InstitutionSite
                                        $fkey = strtolower(str_replace("_custom_values", "_id", $rawTableName)); //institution_site_id
                                        $fkey2 = strtolower(str_replace("_values", "_field_id", $rawTableName)); //institution_site_custom_field_id
                                        //$field = $key.'.'.$fkey;
                                        $field = 'InstitutionSite.id';
                                        
                                        foreach($advanced as $arrIdVal){
                                            foreach ($arrIdVal as $id => $val) {
                                                if(!empty($val['value']))
                                                $arrCond[] = array($key.'.'.$fkey2=>$id,$key.'.value'=>$val['value']);
                                            }

                                        }
                                        $joins = array();
                                        $joins[] = array(
                                            'table' => 'institution_sites',
                                            'alias' => 'InstitutionSite',
                                            'type' => 'LEFT',
                                            'conditions' => array($key.'.'.$fkey.' = '.$mainTable.'.id')
                                        );
                                        
                                        if(!empty($arrCond)){
                                            $query = $dbo->buildStatement(array(
                                                    'fields' => array($field),
                                                    'table' => $rawTableName,
                                                    'alias' => $key,
                                                    'limit' => null, 
                                                    'offset' => null,
                                                    'joins' => $joins,
                                                    'conditions' => array('OR'=>$arrCond),
                                                    'group' => array($field),
                                                    'order' => null
                                            ), $this);
                                            //pr($query);
                                            $conditions[] = 'InstitutionSite.id IN (' . $query . ')';
                                           
                                        }
                                        
                                        
                                   }
                                   
                                }
                            
                                /*
                                 * 
                                 * if($key == 'InstitutionCustomValue'){
                                   
                                    if($advanced > 0) { 
                                       
                                        
                                            $joins = array(
                                                    array(
                                                            'table' => Inflector::tableize($key),
                                                            'alias' => $key,
                                                            'conditions' => array($key.'.institution_site_id = InstitutionSite.id')
                                                    )
                                            );
                                            
                                             foreach($advanced as $type => $arrTypeVals){
                                            
                                                    echo $this->inputsMapping[$type];
                                                    pr($arrTypeVals);
                                                }

                                       
                                            $dbo = $this->getDataSource();
                                            $query = $dbo->buildStatement(array(
                                                    'fields' => array('InstitutionSite.institution_id'),
                                                    'table' => 'institution_sites',
                                                    'alias' => 'InstitutionSite',
                                                    'limit' => null, 
                                                    'offset' => null,
                                                    'joins' => $joins,
                                                    'conditions' => array('InstitutionSite.institution_id = Institution.id'),
                                                    'group' => array('InstitutionSite.institution_id'),
                                                    'order' => null
                                            ), $this);
                                            $conditions[] = 'EXISTS (' . $query . ')';
                                    }
                                }
                                 */
                            }
                            
                            
                        }
		}
                //pr($conditions);
		return $conditions;
	}
	
	public function paginateQuery($conditions, $fields=null, $order=null, $limit=null, $page = 1) {
           
		$dbo = $this->getDataSource();
		$queries = array(
			$this->getQueryFromInstitutionsWithoutSites($conditions),
			$this->getQueryFromSecurityAreas($conditions),
			$this->getQueryFromSecuritySites($conditions)
		);
		$union = implode(' UNION ', $queries);
		$joins = array(
			array(
				'table' => '(' . $union . ')',
				'alias' => 'InstitutionFilter',
				'conditions' => array('InstitutionFilter.id = Institution.id')
			)
		);
		$query = $dbo->buildStatement(array(
			'fields' => !is_null($fields) ? $fields : array('COUNT(*) AS COUNT'),
			'table' => $dbo->fullTableName($this),
			'alias' => 'Institution',
			'limit' => $limit,
			'offset' => !is_null($fields) ? (($page-1)*$limit) : null,
			'joins' => $this->paginateJoins($joins, $conditions),
			'conditions' => $this->paginateConditions($conditions),
			'group' => !is_null($fields) ? array('Institution.id') : null,
			'order' => $order
		), $this);
                
		$data = $dbo->fetchAll($query);
		return $data;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
                if(array_key_exists('order', $conditions)){
                    $order = $conditions['order'];
                }
            
		$isSuperAdmin = $conditions['isSuperAdmin'];
		$fields = array(
			'InstitutionSite.id',
			'InstitutionSite.code',
			'InstitutionSite.name',
                        'InstitutionSiteType.name',
                        'Area.name'
		);
		if(strlen($conditions['SearchKey']) != 0) {
			$fields[] = 'InstitutionSiteHistory.code';
			$fields[] = 'InstitutionSiteHistory.name';
		}
		
		$joins = array();
		$data = array();
		// if super admin
                
               // pr($this->paginateConditions($conditions));die;
		if($isSuperAdmin) {
			$data = $this->find('all', array(
				'recursive' => -1,
				'fields' => $fields,
				'joins' => $this->paginateJoins($joins, $conditions),
				'conditions' => $this->paginateConditions($conditions),
				'limit' => $limit,
				'offset' => (($page-1)*$limit),
				'group' => array('InstitutionSite.id'),
				'order' => $order
			));
		} else {
			$data = $this->paginateQuery($conditions, $fields, $order, $limit, $page);
		}
                
		return $data;
	}
	
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$isSuperAdmin = $conditions['isSuperAdmin'];
		$joins = array();
		$count = 0;
		if($isSuperAdmin) {
			$count = $this->find('count', array(
				'recursive' => -1,
				'joins' => $this->paginateJoins($joins, $conditions),
				'conditions' => $this->paginateConditions($conditions)
			));
		} else {
			$data = $this->paginateQuery($conditions);
			$count = isset($data[0][0]['COUNT']) ? $data[0][0]['COUNT'] : 0;
		}
		return $count;
	}

	public function getAutoCompleteList($search) {
		$search = sprintf('%%%s%%', $search);

		$list = $this->find('all', array(
				'recursive' => -1,
				'fields' => array('InstitutionSite.name, InstitutionSite.id'),
				'conditions' => array(
					'InstitutionSite.name LIKE' => $search
				),
				'order' => array('InstitutionSite.name')
			));

		$data = array();
		foreach ($list as $obj) {
			$site = $obj['InstitutionSite'];
			$data[] = array(
				'label' => $site['name'],
				'value' => $site['id'],
			);
		}
		return $data;
	}
	
	public function getInstitutionSiteById($institutionSiteId){
		$data = $this->find('first', array(
			'recursive' => -1,
			'conditions' => array('InstitutionSite.id' => $institutionSiteId)
		));
		
		return $data;
	}
	
	public function reportsGetHeader($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->getCSVHeader($this->reportMapping[$index]['fields']);
	}

	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		// General > Overview and More
		if ($index == 1) {
			$options = array();
			//$options['recursive'] = -1;
			$options['fields'] = $this->getCSVFields($this->reportMapping[$index]['fields']);
			$options['group'] = array('InstitutionSite.id');
			$options['conditions'] = array('InstitutionSite.id' => $institutionSiteId);

			$options['joins'] = array(
				array(
					'table' => 'area_educations',
					'alias' => 'AreaEducation',
					'type' => 'left',
					'conditions' => array('InstitutionSite.area_education_id = AreaEducation.id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite2',
					'type' => 'inner',
					'conditions' => array('InstitutionSite.id = InstitutionSite2.id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite3',
					'type' => 'inner',
					'conditions' => array('InstitutionSite.id = InstitutionSite3.id')
				)
			);

			$data = $this->find('all', $options);

			$reportFields = $this->reportMapping[$index]['fields'];
			
			$customFieldModel = ClassRegistry::init('InstitutionSiteCustomField');
			$institutionSiteCustomFields = $customFieldModel->find('all', array(
				'recursive' => -1,
				'fields' => array('InstitutionSiteCustomField.name as FieldName'),
				'joins' => array(
					array(
						'table' => 'institution_sites',
						'alias' => 'InstitutionSite',
						'conditions' => array(
							'OR' => array(
								'InstitutionSiteCustomField.institution_site_type_id = InstitutionSite.institution_site_type_id',
								'InstitutionSiteCustomField.institution_site_type_id' => 0
							)
						)
					)
				),
				'conditions' => array(
					'InstitutionSiteCustomField.visible' => 1,
					'InstitutionSiteCustomField.type != 1',
					'InstitutionSite.id' => $institutionSiteId
				),
				'order' => array('InstitutionSiteCustomField.order')
					)
			);

			foreach ($institutionSiteCustomFields as $val) {
				if (!empty($val['InstitutionSiteCustomField']['FieldName'])) {
					$reportFields['InstitutionSiteCustomField'][$val['InstitutionSiteCustomField']['FieldName']] = '';
				}
			}

			$this->reportMapping[$index]['fields'] = $reportFields;

			$newData = array();
			$dateFormat = 'd F, Y';
			
			$institutionSiteCustomFields2 = $customFieldModel->find('all', array(
				'recursive' => -1,
				'fields' => array('InstitutionSiteCustomField.id', 'InstitutionSiteCustomField.name as FieldName', 'IFNULL(GROUP_CONCAT(InstitutionSiteCustomFieldOption.value),InstitutionSiteCustomValue.value) as FieldValue'),
				'joins' => array(
					array(
						'table' => 'institution_sites',
						'alias' => 'InstitutionSite',
						'conditions' => array(
							'InstitutionSite.id' => $institutionSiteId,
							'OR' => array(
								'InstitutionSiteCustomField.institution_site_type_id = InstitutionSite.institution_site_type_id',
								'InstitutionSiteCustomField.institution_site_type_id' => 0
							)
						)
					),
					array(
						'table' => 'institution_site_custom_values',
						'alias' => 'InstitutionSiteCustomValue',
						'type' => 'left',
						'conditions' => array(
							'InstitutionSiteCustomField.id = InstitutionSiteCustomValue.institution_site_custom_field_id',
							'InstitutionSiteCustomValue.institution_site_id = InstitutionSite.id'
						)
					),
					array(
						'table' => 'institution_site_custom_field_options',
						'alias' => 'InstitutionSiteCustomFieldOption',
						'type' => 'left',
						'conditions' => array(
							'InstitutionSiteCustomField.id = InstitutionSiteCustomFieldOption.institution_site_custom_field_id',
							'InstitutionSiteCustomField.type' => array(3, 4),
							'InstitutionSiteCustomValue.value = InstitutionSiteCustomFieldOption.id'
						)
					),
				),
				'conditions' => array(
					'InstitutionSiteCustomField.visible' => 1,
					'InstitutionSiteCustomField.type !=1',
				),
				'order' => array('InstitutionSiteCustomField.order'),
				'group' => array('InstitutionSiteCustomField.id')
					)
			);
			
			

			foreach ($data AS $row) {
				if ($row['InstitutionSite2']['date_opened'] == '0000-00-00') {
					$row['InstitutionSite2']['date_opened'] = '';
				} else {
					$originalDate = new DateTime($row['InstitutionSite2']['date_opened']);
					$row['InstitutionSite2']['date_opened'] = $originalDate->format($dateFormat);
				}

				if ($row['InstitutionSite2']['date_closed'] == '0000-00-00') {
					$row['InstitutionSite2']['date_closed'] = '';
				} else {
					$originalDate = new DateTime($row['InstitutionSite2']['date_closed']);
					$row['InstitutionSite2']['date_closed'] = $originalDate->format($dateFormat);
				}
				foreach ($institutionSiteCustomFields2 as $val) {
					if (!empty($val['InstitutionSiteCustomField']['FieldName'])) {
						$row['InstitutionSiteCustomField'][$val['InstitutionSiteCustomField']['FieldName']] = $val[0]['FieldValue'];
					}
				}

				$sortRow = array();
				foreach ($this->reportMapping[$index]['fields'] as $key => $value) {
					if (isset($row[$key])) {
						$sortRow[$key] = $row[$key];
					} else {
						$sortRow[0] = $row[0];
					}
				}

				$newData[] = $sortRow;
			}
			
			return $newData;
		}
	}
	
	public function reportsGetFileName($args){
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->reportMapping[$index]['fileName'];
	}
}
