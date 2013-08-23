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

class Student extends StudentsAppModel {
	public $actsAs = array(
		'Search',
		'TrackHistory' => array('historyTable' => 'Students.StudentHistory'),
		'CascadeDelete' => array(
			'cascade' => array(
				'Students.StudentAttachment',
				'Students.StudentCustomValue'
			)
		)
	);
	
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
		)
	);
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$isSuperAdmin = $conditions['isSuperAdmin'];
		$fields = array(
			'Student.id', 'Student.identification_no',
			'Student.first_name', 'Student.last_name',
			'Student.gender', 'Student.date_of_birth'
		);
		if(strlen($conditions['SearchKey']) != 0) {
			$fields[] = 'StudentHistory.identification_no AS history_identification_no';
			$fields[] = 'StudentHistory.first_name AS history_first_name';
			$fields[] = 'StudentHistory.last_name AS history_last_name';
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
				'group' => array('Student.id'),
				'order' => $order
			));
		} else {
			//$this->searchStart($conditions['userId']);
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
				'group' => array('Student.id')
			));
		} else {
			$data = $this->paginateQuery($conditions);
			$count = isset($data[0][0]['COUNT']) ? $data[0][0]['COUNT'] : 0;
			//$this->searchEnd($conditions['userId']);
		}
		return $count;
	}
	
	/*
	public function paginateJoins($search, $institutionSiteId, $userId, $noSites=true) {
		$joins = array();
		
		if($search) {
			$joins[] = array(
				'table' => 'student_history',
				'alias' => 'StudentHistory',
				'type' => 'LEFT',
				'conditions' => array('StudentHistory.student_id = Student.id')
			);
		}
		
		if(is_array($institutionSiteId)) {
			if(!$noSites) { // for students with no institution sites
				$conditions = !empty($institutionSiteId) ? implode(',', $institutionSiteId) : 0;
				$joins[] = array(
					'table' => 'institution_site_students',
					'alias' => 'InstitutionSiteStudent',
					'conditions' => array('InstitutionSiteStudent.student_id = Student.id')
				);
				$joins[] = array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.id = InstitutionSiteStudent.institution_site_programme_id',
						'InstitutionSiteProgramme.institution_site_id IN (' . $conditions .')'
					)
				);
			} else {
				if($userId !== false) {
					$joins[] = array(
						'table' => 'security_group_users',
						'alias' => 'GroupA',
						'conditions' => array('GroupA.security_user_id = Student.created_user_id')
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
				'Student.identification_no LIKE' => $search,
				'Student.first_name LIKE' => $search,
				'Student.last_name LIKE' => $search,
				'StudentHistory.identification_no LIKE' => $search,
				'StudentHistory.first_name LIKE' => $search,
				'StudentHistory.last_name LIKE' => $search
			);
		}
		if(!empty($access)) {
			$paginateConditions['AND']['Student.id'] = $access;
		}
		if($noSites) {
			$paginateConditions['AND'] = array(
				'NOT EXISTS (SELECT institution_site_students.student_id FROM institution_site_students WHERE institution_site_students.student_id = Student.id)'
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
				? array('Student.id')
				: array(
					'Student.id', 'Student.identification_no',
					'Student.first_name', 'Student.last_name',
					'Student.gender', 'Student.date_of_birth'
				);
		
		$search = strlen($conditions['SearchKey']) != 0;
		if($search && $count == false) {
			$fields[] = 'StudentHistory.identification_no AS history_identification_no';
			$fields[] = 'StudentHistory.first_name AS history_first_name';
			$fields[] = 'StudentHistory.last_name AS history_last_name';
		}
		
		$institutionSiteId = array_key_exists('InstitutionSiteId', $conditions) ? $conditions['InstitutionSiteId'] : false;
		$userId = array_key_exists('UserId', $conditions) ? $conditions['UserId'] : false;
		$dbo = $this->getDataSource();
		
		// retrieve the list of students without a site
		$dataNoSites = $dbo->buildStatement(array(
			'fields' => $fields,
			'table' => $dbo->fullTableName($this),
			'alias' => 'Student',
			'limit' => null, 
			'offset' => null,
			'joins' => $this->paginateJoins($search, $institutionSiteId, $userId),
			'conditions' => $this->paginateConditions($conditions),
			'group' => null,
			'order' => null
		), $this);
		
		// retrieve the list of students with sites that the user can access
		$dataWithSites = $dbo->buildStatement(array(
			'fields' => $fields,
			'table' => $dbo->fullTableName($this),
			'alias' => 'Student',
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
				'alias' => 'Student',
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
				'Student.id', 'Student.identification_no',
				'Student.first_name', 'Student.last_name',
				'Student.gender', 'Student.date_of_birth'
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
			'alias' => 'Student',
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
				'Student.id', 'Student.identification_no',
				'Student.first_name', 'Student.last_name',
				'Student.gender', 'Student.date_of_birth'
			);
			$search = strlen($conditions['SearchKey']) != 0;
			if($search != 0) {
				$fields[] = 'StudentHistory.identification_no AS history_identification_no';
				$fields[] = 'StudentHistory.first_name AS history_first_name';
				$fields[] = 'StudentHistory.last_name AS history_last_name';
			}
			$data = $this->find('all', array(
				'fields' => $fields,
				'joins' => $this->paginateJoins($search, $institutionSiteId, false, false),
				'conditions' => $this->paginateConditions($conditions, false),
				'limit' => $limit,
				'offset' => (($page-1)*$limit),
				'group' => 'Student.id',
				'order' => $order
			));
		}
		pr($this->getQueryWithoutSites(array('userId' => 2)));
		pr($this->getQueryFromSecurityAreas(array('userId' => 2)));
		pr($this->getQueryFromSecuritySites(array('userId' => 2)));
		pr($this->getQueryFromAccess(array('userId' => 2)));
		$this->paginateJoins2(array(), array());
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
				'group' => 'Student.id'
			));
		}
		return $count;
	}
	*/
}
?>