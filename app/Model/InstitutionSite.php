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
		'AreaEducation',
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

	public $hasMany = array(
		'InstitutionSiteBankAccount',
		'InstitutionSitePosition',
		'InstitutionSiteProgramme',
		'InstitutionSiteShift',
		'InstitutionSiteSection',
		'InstitutionSiteClass',
		'InstitutionSiteFee'
	);
	
	public $actsAs = array(
		'Excel',
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
		'DatePicker' => array('date_opened', 'date_closed')
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
			),
			'ruleMaximum' => array(
				'rule' => array('maxLength', 255),
				'required' => true,
				'message' => 'Please eneter an address within 255 characters'
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
		'area_id_select' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a valid Area'
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
		),
		'date_closed' => array(
			'ruleCompare' => array(
				'rule' => 'compareDates',
				'message' => 'Date Closed cannot be earlier than Date Opened'
			)
		),
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
		),
		'institution_site_gender_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a Gender'
			)
		)
	);
	
	public function compareDates() {
		if(!empty($this->data[$this->alias]['date_closed'])) {
			$startDate = $this->data[$this->alias]['date_opened'];
			$startTimestamp = strtotime($startDate);
			$endDate = $this->data[$this->alias]['date_closed'];
			$endTimestamp = strtotime($endDate);
			return $endTimestamp > $startTimestamp;
		}
		return true;
	}
    
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

    /* Excel Behaviour */
	public function excelGetConditions() {
		$id = CakeSession::read('InstitutionSite.id');
		$conditions = array('InstitutionSite.id' => $id);
		return $conditions;
	}
	public function excelGetModels() {
		$models = array(
			array('model' => $this),
			array('model' => $this->InstitutionSiteBankAccount),
			array('model' => $this->InstitutionSitePosition),
			array('model' => $this->InstitutionSiteProgramme),
			array('model' => $this->InstitutionSiteShift),
			array('model' => $this->InstitutionSiteSection),
			array('model' => $this->InstitutionSiteClass),
			array('model' => $this->InstitutionSiteFee)
		);
		return $models;
	}
	/* End Excel Behaviour */
	
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
		
		$conditions = array();
		
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
			$conditions[] = 'InstitutionSite.institution_site_provider_id = ' . $extras['providerId'];
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
		$options['conditions'] = $conditions;
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
	
	// To get the list of institutions based on the security settings on areas
	public function getQueryFromSecurityAreas($params) {
		$joins = array(
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
			'fields' => array('InstitutionSite.id'),
			'table' => $dbo->fullTableName($this),
			'alias' => get_class($this),
			'limit' => null, 
			'offset' => null,
			'joins' => $joins,
			'conditions' => null,
			'group' => array('InstitutionSite.id'),
			'order' => null
		), $this);
		return $query;
	}
	
	public function getQueryFromSecuritySites($params) {
		$joins = array(
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
			'fields' => array('InstitutionSite.id'),
			'table' => $dbo->fullTableName($this),
			'alias' => get_class($this),
			'limit' => null, 
			'offset' => null,
			'joins' => $joins,
			'conditions' => null,
			'group' => array('InstitutionSite.id'),
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

		if(!is_null($params['AdvancedSearch'])) {
			$advanced = $params['AdvancedSearch'];
			if($advanced['Search']['area_id'] > 0) { // search by area and all its children
				$joins[] = array(
					'table' => 'areas',
					'alias' => 'AreaAll',
					'conditions' => array('AreaAll.lft <= Area.lft', 'AreaAll.rght >= Area.rght', 'AreaAll.id = ' . $advanced['Search']['area_id'])
				);
			}
			if($advanced['Search']['education_programme_id'] > 0) { 
				$joins[] = array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array('InstitutionSiteProgramme.institution_site_id = InstitutionSite.id', 'InstitutionSiteProgramme.education_programme_id = ' . $advanced['Search']['education_programme_id'])
				);
			}
		}
                
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

			if(count($arrAdvanced) > 0 ) {
			    foreach($arrAdvanced as $key => $advanced){
			       //echo $key;
			       
			        if(strpos($key,'CustomValue') > 0){
			           //pr($advanced);
			           
			           if ($key == 'InstitutionSiteCustomValue') { //hack
			                $arrCond = array();
			           		//echo $key; pr(array('or'=>$arrCond));die;
			               
			                $dbo = $this->getDataSource();
			                $rawTableName = Inflector::tableize($key);
			                $mainTable = str_replace("CustomValue","",$key); //InstitutionSite
			                $fkey = strtolower(str_replace("_custom_values", "_id", $rawTableName)); //institution_site_id
			                $fkey2 = strtolower(str_replace("_values", "_field_id", $rawTableName)); //institution_site_custom_field_id
			                //$field = $key.'.'.$fkey;
			                $field = 'InstitutionSite.id';
			                
			                foreach($advanced as $arrIdVal) {
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
			                
			                if (!empty($arrCond)) {
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
			    }
			}
		}

		return $conditions;
	}
	
	public function paginateQuery($conditions, $fields=null, $order=null, $limit=null, $page = 1) {
           
		$dbo = $this->getDataSource();
		$queries = array(
			$this->getQueryFromSecurityAreas($conditions),
			$this->getQueryFromSecuritySites($conditions)
		);
		$union = implode(' UNION ', $queries);
		$joins = array(
			array(
				'table' => '(' . $union . ')',
				'alias' => 'InstitutionFilter',
				'conditions' => array('InstitutionFilter.id = InstitutionSite.id')
			)
		);
		$query = $dbo->buildStatement(array(
			'fields' => !is_null($fields) ? $fields : array('COUNT(*) AS COUNT'),
			'table' => $dbo->fullTableName($this),
			'alias' => 'InstitutionSite',
			'limit' => $limit,
			'offset' => !is_null($fields) ? (($page-1)*$limit) : null,
			'joins' => $this->paginateJoins($joins, $conditions),
			'conditions' => $this->paginateConditions($conditions),
			'group' => !is_null($fields) ? array('InstitutionSite.id') : null,
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
			'Area.name',
			'Area.id'
		);
		if(strlen($conditions['SearchKey']) != 0) {
			$fields[] = 'InstitutionSiteHistory.code';
			$fields[] = 'InstitutionSiteHistory.name';
		}
		
		$joins = array();
		$data = array();

		// if super admin

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
				'conditions' => $this->paginateConditions($conditions),
				//'group' => array('InstitutionSite.id')
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

	/*
	public function processFields($fieldsArr){
		$fields = array();
		$columns = array();
		foreach($fieldsArr AS $key => $value){
			$fields[] = $key;
			$columns[] = $value;
		}
		
		$data['fields'] = $fields;
		$data['columns'] = $columns;
		
		return $data;
	}

	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$options = array();
			$options['recursive'] = 0;
			$fieldsResult = $this->processFields($this->reportMapping[$index]['fields']);
			$options['fields'] = $fieldsResult['fields'];
			$options['conditions'] = array('InstitutionSite.id' => $institutionSiteId);

			$commonFielsResult = $this->find('first', $options);
			$institutionResultArr = array();
			foreach($commonFielsResult AS $model => $arr){
				foreach($arr AS $field => $value){
					$institutionResultArr[$model . '.' . $field] = $value;
				}
			}
			
			$csvHeader = array();
			$csvDataRecord = array();
			foreach($this->reportMapping[$index]['fields'] AS $key => $value){
				if(isset($institutionResultArr[$key])){
					$csvHeader[] = $value;
					
					$recordValue = $institutionResultArr[$key];
					if($key == 'InstitutionSite.date_opened' || $key == 'InstitutionSite.date_closed'){
						if($recordValue == '0000-00-00'){
							$recordValue = '';
						}
					}
					
					$csvDataRecord[] = !empty($recordValue) ? $recordValue : '';
				}
			}
			
			$institutionObj = $this->findById($institutionSiteId);
			$institutionSiteTypeId = $institutionObj['InstitutionSite']['institution_site_type_id'];
			
			$customFieldModel = ClassRegistry::init('InstitutionSiteCustomField');
			$customFielsData = $customFieldModel->find('all', array(
				'recursive' => -1,
				'fields' => array('InstitutionSiteCustomField.id', 'InstitutionSiteCustomField.type', 'InstitutionSiteCustomField.name', 'GROUP_CONCAT(InstitutionSiteCustomFieldOption.value ORDER BY InstitutionSiteCustomFieldOption.order) AS ValueFromOption', 'InstitutionSiteCustomValue.value'),
				'joins' => array(
					array(
						'table' => 'institution_site_custom_values',
						'alias' => 'InstitutionSiteCustomValue',
						'type' => 'left',
						'conditions' => array(
							'InstitutionSiteCustomField.id = InstitutionSiteCustomValue.institution_site_custom_field_id',
							'InstitutionSiteCustomValue.institution_site_id = ' . $institutionSiteId
						)
					),
					array(
						'table' => 'institution_site_custom_field_options',
						'alias' => 'InstitutionSiteCustomFieldOption',
						'type' => 'left',
						'conditions' => array(
							'InstitutionSiteCustomField.id = InstitutionSiteCustomFieldOption.institution_site_custom_field_id',
							'InstitutionSiteCustomValue.value = InstitutionSiteCustomFieldOption.id'
						)
					),
				),
				'conditions' => array(
					'InstitutionSiteCustomField.visible = 1',
					'InstitutionSiteCustomField.type !=1',
					'OR' => array(
						'InstitutionSiteCustomField.institution_site_type_id = ' . $institutionSiteTypeId,
						'InstitutionSiteCustomField.institution_site_type_id = 0'
					)
				),
				'order' => array('InstitutionSiteCustomField.institution_site_type_id', 'InstitutionSiteCustomField.order'),
				'group' => array('InstitutionSiteCustomField.id')
			));	
			
			foreach($customFielsData AS $key => $arr){
				$fieldType = $arr['InstitutionSiteCustomField']['type'];
				$fieldName = $arr['InstitutionSiteCustomField']['name'];
				$value = $arr['InstitutionSiteCustomValue']['value'];
				$valueFromOption = $arr[0]['ValueFromOption'];
				
				if($fieldType == 3 || $fieldType == 4 ){
					$finalValue = $valueFromOption;
				}else{
					$finalValue = $value;
				}
				
				$csvHeader[] = $fieldName;
				$csvDataRecord[] = $finalValue;
			}
			
			$newData[] = $csvHeader;
			$newData[] = $csvDataRecord;
			
			return $newData;
		}
	}
	
	public function reportsGetFileName($args){
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->reportMapping[$index]['fileName'];
	}
	*/

	public function displayByAreaLevel($data, $model='Area', $areaLevelID){
		$levelModels = array('Area' => 'AreaLevel', 'AreaEducation' => 'AreaEducationLevel');
		$foreignKey = Inflector::underscore($levelModels[$model]).'_id';
		
		$AreaHandler = new AreaHandlerComponent(new ComponentCollection);
		foreach($data as $key=>$value){
			$areaID = $value['Area']['id'];
			$path = $AreaHandler->{$model}->getPath($areaID);
			if(is_array($path)){
				foreach($path as $i => $obj) {
					if($obj[$model]['area_level_id']!=$areaLevelID){
						continue;
					}
					$data[$key][$model]['name'] = $obj[$model]['name'];
					break;
				}
			}
		}
		return $data;
	}
	
}
