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

class Institution extends AppModel {
	public $belongsTo = array(
		'Area',
		'InstitutionStatus',
		'InstitutionProvider',
		'InstitutionSector'
	);
	
	public $hasMany = array('InstitutionSite');
	public $actsAs = array(
		'TrackHistory',
		'CascadeDelete' => array(
			'cascade' => array(
				'InstitutionAttachment',
				'InstitutionCustomValue',
				'InstitutionSite'
			)
		)
	);
        
	public $sqlPaginateCount;
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
		'institution_provider_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a Provider'
			)
		),
		'institution_status_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a Status'
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
		)
	);
	
	public function getLookupVariables() {
		$lookup = array(
			'Provider' => array('model' => 'InstitutionProvider'),
			'Sector' => array('model' => 'InstitutionSector'),
			'Status' => array('model' => 'InstitutionStatus')
		);
		return $lookup;
	}
	
	// Used in AccessControlComponent
	public function getInstitutionsWithoutSites() {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('Institution.*'),
			'joins' => array(
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'type' => 'LEFT',
					'conditions' => array('InstitutionSite.institution_id = Institution.id')
				)
			),
			'group' => array('Institution.id HAVING COUNT(InstitutionSite.id) = 0')
		));
		return $data;
	}
	
	public function getQueryFromInstitutionsWithoutSites($params) {
		/*
		SELECT
		Institution.id
		FROM `institutions` AS `Institution`
		JOIN `security_group_users` AS `CreatorGroup` 
			ON (`CreatorGroup`.`security_user_id` = `Institution`.`created_user_id`) 
		JOIN `security_group_users` AS `UserGroup` 
			ON (`UserGroup`.`security_group_id` = `CreatorGroup`.`security_group_id` AND `UserGroup`.`security_user_id` = 2)
		WHERE NOT EXISTS (
			SELECT `institution_sites`.`id`
			FROM `institution_sites`
			WHERE `institution_sites`.`institution_id` = `Institution`.`id`
		)
		AND (`CreatorGroup`.`security_group_id` IS NULL
			OR (`CreatorGroup`.`security_group_id` IS NOT NULL AND `UserGroup`.`security_group_id` IS NOT NULL))
		GROUP BY Institution.id
		*/
		$userId = $params['userId'];
		$dbo = $this->getDataSource();
		$query = $dbo->buildStatement(array(
			'fields' => array('Institution.id'),
			'table' => $dbo->fullTableName($this),
			'alias' => 'Institution',
			'limit' => null, 
			'offset' => null,
			'joins' => array(
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
						'UserGroup.security_user_id = ' . $userId
					)
				)
			),
			'conditions' => array(
				'NOT EXISTS (SELECT id FROM institution_sites WHERE institution_id = Institution.id)',
				'OR' => array(
					'CreatorGroup.security_group_id IS NULL',
					'AND' => array(
						'CreatorGroup.security_group_id IS NOT NULL',
						'UserGroup.security_group_id IS NOT NULL'
					)
				)
			),
			'group' => array('Institution.id'),
			'order' => null
		), $this);
		return $query;
	}
	
	// To get the list of institutions based on the security settings on areas
	public function getQueryFromSecurityAreas($params) {
		/*
		SELECT 
		Institution.id,
		Institution.code,
		Institution.name
		FROM `institutions` AS `Institution`
		JOIN `institution_sites`
			ON `institution_sites`.`institution_id` = Institution.id
		JOIN `areas`
			ON `areas`.`id` = `institution_sites`.`area_id`
		JOIN `areas` AS `area2`
			ON `area2`.`lft` >= `areas`.`lft`
			AND `area2`.`rght` <= `areas`.`rght`
		JOIN `security_group_areas`
			ON `security_group_areas`.`area_id` = `area2`.`id`
		JOIN `security_group_users`
			ON `security_group_users`.`security_group_id` = `security_group_areas`.`security_group_id`
			AND `security_group_users`.`security_user_id` = 2
		GROUP BY Institution.id
		*/
		$userId = $params['userId'];
		$dbo = $this->getDataSource();
		$query = $dbo->buildStatement(array(
			'fields' => array('Institution.id'),
			'table' => $dbo->fullTableName($this),
			'alias' => 'Institution',
			'limit' => null, 
			'offset' => null,
			'joins' => array(
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
						'SecurityGroupUser.security_user_id = ' . $userId
					)
				)
			),
			'conditions' => null,
			'group' => array('Institution.id'),
			'order' => null
		), $this);
		return $query;
	}
	
	public function getQueryFromSecuritySites($params) {
		/*
		SELECT 
		Institution.id
		FROM `institutions` AS `Institution`
		JOIN `institution_sites`
			ON `institution_sites`.`institution_id` = Institution.id
		JOIN `security_group_institution_sites`
			ON `security_group_institution_sites`.`institution_site_id` = `institution_sites`.`id`
		JOIN `security_group_users`
			ON `security_group_users`.`security_group_id` = `security_group_institution_sites`.`security_group_id`
			AND `security_group_users`.`security_user_id` = 2
		GROUP BY Institution.id
		*/
		$userId = $params['userId'];
		$dbo = $this->getDataSource();
		$query = $dbo->buildStatement(array(
			'fields' => array('Institution.id'),
			'table' => $dbo->fullTableName($this),
			'alias' => 'Institution',
			'limit' => null, 
			'offset' => null,
			'joins' => array(
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
						'SecurityGroupUser.security_user_id = ' . $userId
					)
				)
			),
			'conditions' => null,
			'group' => array('Institution.id'),
			'order' => null
		), $this);
		return $query;
	}
	
	public function paginateJoins($joins, $params) {
		if(strlen($params['SearchKey']) != 0) {
			$joins[] = array(
				'table' => 'institution_history',
				'alias' => 'InstitutionHistory',
				'type' => 'LEFT',
				'conditions' => array('InstitutionHistory.institution_id = Institution.id')
			);
		}
		$joins[] = array(
			'table' => 'institution_providers',
			'alias' => 'InstitutionProvider',
			'conditions' => array('InstitutionProvider.id = Institution.institution_provider_id')
		);
		$joins[] = array(
			'table' => 'institution_sectors',
			'alias' => 'InstitutionSector',
			'conditions' => array('InstitutionSector.id = Institution.institution_sector_id')
		);
		return $joins;
	}
	
	public function paginateConditions($params) {
		$conditions = array();
		if(strlen($params['SearchKey']) != 0) {
			$search = "%".$params['SearchKey']."%";
			$conditions['OR'] = array(
				'Institution.name LIKE' => $search,
				'Institution.code LIKE' => $search,
				'InstitutionHistory.name LIKE' => $search,
				'InstitutionHistory.code LIKE' => $search
			);
		}
		if(!is_null($params['AdvancedSearch'])) {
			$advanced = $params['AdvancedSearch'];
			
			if($advanced['area_id'] > 0) { // search by area and all its children
				/*
				$joins[] = array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array('InstitutionSite.institution_id = Institution.id')
				);
				$joins[] = array(
					'table' => 'areas',
					'alias' => 'Area',
					'conditions' => array('Area.id = InstitutionSite.area_id')
				);
				$joins[] = array( // to get all child areas including the current parent
					'table' => 'areas',
					'alias' => 'AreaAll',
					'conditions' => array('AreaAll.lft <= Area.lft', 'AreaAll.rght >= Area.rght', 'AreaAll.id = ' . $advanced['area_id'])
				);
				*/
				
				$dbo = $this->getDataSource();
				$query = $dbo->buildStatement(array(
					'fields' => array('InstitutionSite.institution_id'),
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'limit' => null, 
					'offset' => null,
					'joins' => array(
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
					),
					'conditions' => array('InstitutionSite.institution_id = Institution.id'),
					'group' => array('InstitutionSite.institution_id'),
					'order' => null
				), $this);
				$conditions[] = 'EXISTS (' . $query . ')';
			}
		}
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
			'fields' => !is_null($fields) ? $fields : array('COUNT(1) AS COUNT'),
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
		$isSuperAdmin = $conditions['isSuperAdmin'];
		$fields = array(
			'Institution.id',
			'Institution.code',
			'Institution.name',
			'InstitutionProvider.name',
			'InstitutionSector.name'
		);
		if(strlen($conditions['SearchKey']) != 0) {
			$fields[] = 'InstitutionHistory.code';
			$fields[] = 'InstitutionHistory.name';
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
				'group' => array('Institution.id'),
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
}
