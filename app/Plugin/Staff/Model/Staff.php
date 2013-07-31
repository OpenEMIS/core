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

class Staff extends StaffAppModel {
	public $useTable = 'staff';

	public $actsAs = array(
		'UserAccess',
		'TrackHistory' => array('historyTable' => 'Staff.StaffHistory'),
		'CascadeDelete' => array(
			'cascade' => array(
				'Staff.StaffAttachment',
				'Staff.StaffCustomValue'
			)
		)
	);

	public $sqlPaginateCount;
	public $validate = array(
		'first_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid First Name'
			)
		),
		'last_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Last Name'
			)
		),
		'identification_no' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Identification No'
			),
			'ruleUnique' => array(
        		'rule' => 'isUnique',
        		'required' => true,
        		'message' => 'Please enter a unique Identification No'
		    )
		),
		'email' => array(
			'ruleRequired' => array(
				'rule' => 'email',
				'allowEmpty' => true,
				'message' => 'Please enter a valid Email'
			)
		),
		'gender' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', 'not equal', '0'),
				'required' => true,
				'message' => 'Please select a Gender'
			)
		),
		'address' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Address'
			)
		),
		'date_of_birth' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Date of Birth'
			),
			'ruleCompare' => array(
				'rule' => array('comparison', 'NOT EQUAL', '0000-00-00'),
				'required' => true,
				'message' => 'Please select a Date of Birth'
			)
		),
		'email' => array(
			'ruleRequired' => array(
				'rule' => 'email',
				'allowEmpty' => true,
				'message' => 'Please enter a valid Email'
			)
		)/*,
		'address_area_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select an Address Area'
			)
		),
		'birthplace_area_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a Birthplace Area'
			)
		)*/
	);
	
	public function getLookupVariables() {
		$lookup = array(
			'Positions' => array('model' => 'Staff.StaffCategory')
		);
		return $lookup;
	}
	
	public function search($search, $params=array()) {
		$model = $this->alias;
		$data = array();
		$search = '%' . $search . '%';
		$limit = isset($params['limit']) ? $params['limit'] : false;
		
		$conditions = array(
			'OR' => array(
				$model . '.identification_no LIKE' => $search,
				$model . '.first_name LIKE' => $search,
				$model . '.last_name LIKE' => $search
			)
		);
		
		$options = array(
			'recursive' => -1,
			'conditions' => $conditions,
			'order' => array($model . '.first_name')
		);
		
		$count = $this->find('count', $options);
		
		$data = false;
		if($limit === false || $count < $limit) {
			$options['fields'] = array($model . '.*');
			$data = $this->find('all', $options);
		}
		return $data;
	}
	
	public function paginateJoins($search, $institutionSiteId, $userId, $noSites=true) {
		$joins = array();
		
		if($search) {
			$joins[] = array(
				'table' => 'staff_history',
				'alias' => 'StaffHistory',
				'type' => 'LEFT',
				'conditions' => array('StaffHistory.staff_id = Staff.id')
			);
		}
		
		if(is_array($institutionSiteId)) {
			if(!$noSites) { // for staff with no institution sites
				$conditions = !empty($institutionSiteId) ? implode(',', $institutionSiteId) : 0;
				$joins[] = array(
					'table' => 'institution_site_staff',
					'alias' => 'InstitutionSiteStaff',
					'conditions' => array(
						'InstitutionSiteStaff.staff_id = Staff.id',
						'InstitutionSiteStaff.institution_site_id IN (' . $conditions .')'
					)
				);
			} else {
				if($userId !== false) {
					$joins[] = array(
						'table' => 'security_group_users',
						'alias' => 'GroupA',
						'conditions' => array('GroupA.security_user_id = Staff.created_user_id')
					);
					$joins[] = array(
						'table' => 'security_group_users',
						'alias' => 'GroupB',
						'conditions' => array(
							'GroupB.security_group_id = GroupA.security_group_id',
							'GroupB.security_user_id = ' . $userId
						)
					);
				}
			}
		}
		return $joins;
	}
	
	public function paginateConditions($conditions, $noSites=true, $access=array()) {
		$paginateConditions = array();
		if(strlen($conditions['SearchKey']) != 0) {
			$search = "%".$conditions['SearchKey']."%";
			$paginateConditions['OR'] = array(
				'Staff.identification_no LIKE' => $search,
				'Staff.first_name LIKE' => $search,
				'Staff.last_name LIKE' => $search,
				'StaffHistory.identification_no LIKE' => $search,
				'StaffHistory.first_name LIKE' => $search,
				'StaffHistory.last_name LIKE' => $search
			);
		}
		if(!empty($access)) {
			$paginateConditions['AND']['Staff.id'] = $access;
		}
		if($noSites) {
			$paginateConditions['AND'] = array(
				'NOT EXISTS (SELECT institution_site_staff.staff_id FROM institution_site_staff WHERE institution_site_staff.staff_id = Staff.id)'
			);
			$userId = array_key_exists('UserId', $conditions) ? $conditions['UserId'] : false;
			if($userId !== false) { // applies only to non-super user
				$paginateConditions['AND']['OR'] = array( // only creator or users in creator's group can access
					'GroupA.security_group_id IS NULL',
					'AND' => array('GroupA.security_group_id IS NOT NULL', 'GroupB.security_group_id IS NOT NULL')
				);
			}
		}
		return $paginateConditions;
	}
	
	public function paginateQuery($conditions, $order, $limit, $page, $count = false) {
		$fields = $count 
				? array('Staff.id')
				: array(
					'Staff.id', 'Staff.identification_no',
					'Staff.first_name', 'Staff.last_name',
					'Staff.gender', 'Staff.date_of_birth'
				);
		
		$search = strlen($conditions['SearchKey']) != 0;
		if($search && $count == false) {
			$fields[] = 'StaffHistory.identification_no AS history_identification_no';
			$fields[] = 'StaffHistory.first_name AS history_first_name';
			$fields[] = 'StaffHistory.last_name AS history_last_name';
		}
		
		$institutionSiteId = array_key_exists('InstitutionSiteId', $conditions) ? $conditions['InstitutionSiteId'] : false;
		$userId = array_key_exists('UserId', $conditions) ? $conditions['UserId'] : false;
		$dbo = $this->getDataSource();
		
		// retrieve the list of staff without a site
		$dataNoSites = $dbo->buildStatement(array(
			'fields' => $fields,
			'table' => $dbo->fullTableName($this),
			'alias' => 'Staff',
			'limit' => null, 
			'offset' => null,
			'joins' => $this->paginateJoins($search, $institutionSiteId, $userId),
			'conditions' => $this->paginateConditions($conditions),
			'group' => null,
			'order' => null
		), $this);
		
		// retrieve the list of staff with sites that the user can access
		$dataWithSites = $dbo->buildStatement(array(
			'fields' => $fields,
			'table' => $dbo->fullTableName($this),
			'alias' => 'Staff',
			'limit' => null, 
			'offset' => null,
			'joins' => $this->paginateJoins($search, $institutionSiteId, $userId, false),
			'conditions' => $this->paginateConditions($conditions, false),
			'group' => null,
			'order' => null
		), $this);
		
		// Security User Access
		$access = $this->getUserAccess($userId);
		$dataWithAccess = null;
		if(!empty($access)) {
			$dataWithAccess = $dbo->buildStatement(array(
				'fields' => $fields,
				'table' => $dbo->fullTableName($this),
				'alias' => 'Staff',
				'limit' => null, 
				'offset' => null,
				'joins' => $this->paginateJoins($search, null, $userId, false),
				'conditions' => $this->paginateConditions($conditions, false, $access),
				'group' => null,
				'order' => null
			), $this);
		}
		// End
		
		$unions = array($dataNoSites, $dataWithSites);
		if(!is_null($dataWithAccess)) {
			$unions[] = $dataWithAccess;
		}
		
		if($count==false) {
			$fields = array(
				'Staff.id', 'Staff.identification_no',
				'Staff.first_name', 'Staff.last_name',
				'Staff.gender', 'Staff.date_of_birth'
			);
			
			if($search) {
				$fields[] = 'history_identification_no';
				$fields[] = 'history_first_name';
				$fields[] = 'history_last_name';
			}
		} else {
			$fields = array('COUNT(1) AS COUNT');
		}
		
		$options = array(
			'fields' => $fields,
			'table' => '(' . implode(' UNION ', $unions) . ')',
			'alias' => 'Staff',
			'limit' => null,
			'conditions' => array(),
			'group' => null,
			'order' => null
		);
		
		if($count==false) {
			$options['limit'] = $limit;
			$options['offset'] = (($page-1)*$limit);
			$options['order'] = $order;
		}
		$query = $dbo->buildStatement($options, $this);
		return $query;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$data = array();
		$institutionSiteId = array_key_exists('InstitutionSiteId', $conditions) ? $conditions['InstitutionSiteId'] : false;
		
		if(is_array($institutionSiteId)) {
			$query = $this->paginateQuery($conditions, $order, $limit, $page);
			$dbo = $this->getDataSource();
			$data = $dbo->fetchAll($query);
		} else {
			$fields = array(
				'Staff.id', 'Staff.identification_no',
				'Staff.first_name', 'Staff.last_name',
				'Staff.gender', 'Staff.date_of_birth'
			);
			$search = strlen($conditions['SearchKey']) != 0;
			if($search != 0) {
				$fields[] = 'StaffHistory.identification_no AS history_identification_no';
				$fields[] = 'StaffHistory.first_name AS history_first_name';
				$fields[] = 'StaffHistory.last_name AS history_last_name';
			}
			$data = $this->find('all', array(
				'fields' => $fields,
				'joins' => $this->paginateJoins($search, $institutionSiteId, false, false),
				'conditions' => $this->paginateConditions($conditions, false),
				'limit' => $limit,
				'offset' => (($page-1)*$limit),
				'group' => 'Staff.id',
				'order' => $order
			));
		}
		return $data;
	}
        
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$count = 0;
		$institutionSiteId = array_key_exists('InstitutionSiteId', $conditions) ? $conditions['InstitutionSiteId'] : false;
		if(is_array($institutionSiteId)) {
			$query = $this->paginateQuery($conditions, null, 0, 1, true);
			$dbo = $this->getDataSource();
			$data = $dbo->fetchAll($query);
			$count = $data[0][0]['COUNT'];
		} else {
			$search = strlen($conditions['SearchKey']) != 0;
			$count = $this->find('count', array(
				'joins' => $this->paginateJoins($search, $institutionSiteId, false),
				'conditions' => $this->paginateConditions($conditions, false),
				'group' => 'Staff.id'
			));
		}
		return $count;
	}
}
?>
