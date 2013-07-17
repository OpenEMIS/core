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
		'postal_code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Postal Code'
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
	
	public function paginateJoins(&$conditions) {
		$joins = array();
		
		if(strlen($conditions['SearchKey']) != 0) {
			$joins[] = array(
				'table' => 'staff_history',
				'alias' => 'StaffHistory',
				'type' => 'LEFT',
				'conditions' => array('StaffHistory.staff_id = Staff.id')
			);
		}
		
		if(array_key_exists('InstitutionSiteId', $conditions)) {
			$institutionSiteId = $conditions['InstitutionSiteId'];
			unset($conditions['InstitutionSiteId']);
			
			$staffConditions = !empty($institutionSiteId) ? implode(',', $institutionSiteId) : 0;
			
			$joins[] = array(
				'table' => 'institution_site_staff',
				'alias' => 'InstitutionSiteStaff',
				'type' => !empty($institutionSiteId) ? 'LEFT' : 'INNER',
				'conditions' => array(
					'InstitutionSiteStaff.staff_id = Staff.id',
					'InstitutionSiteStaff.institution_site_id IN (' . $staffConditions .')'
				)
			);
		}
		return $joins;
	}
	
	public function paginateConditions($conditions) {
		if(strlen($conditions['SearchKey']) != 0) {
			$search = "%".$conditions['SearchKey']."%";
			$conditions['OR'] = array(
				'Staff.identification_no LIKE' => $search,
				'Staff.first_name LIKE' => $search,
				'Staff.last_name LIKE' => $search,
				'StaffHistory.identification_no LIKE' => $search,
				'StaffHistory.first_name LIKE' => $search,
				'StaffHistory.last_name LIKE' => $search
			);
		}
		unset($conditions['SearchKey']);
		return $conditions;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$fields = array(
			'Staff.id',
			'Staff.identification_no',
			'Staff.first_name',
			'Staff.last_name',
			'Staff.gender',
			'Staff.date_of_birth'
		);
		
		if(strlen($conditions['SearchKey']) != 0) {
			$fields[] = 'StaffHistory.id';
			$fields[] = 'StaffHistory.identification_no';
			$fields[] = 'StaffHistory.first_name';
			$fields[] = 'StaffHistory.last_name';
			$fields[] = 'StaffHistory.gender';
			$fields[] = 'StaffHistory.date_of_birth';
		}
		
		$data = $this->find('all', array(
			'fields' => $fields,
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $this->paginateConditions($conditions),
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'group' => 'Staff.id',
			'order' => $order
		));
		return $data;
	}
        
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$count = $this->find('count', array(
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $this->paginateConditions($conditions),
			'group' => 'Staff.id'
		));
		return $count;
	}
}
?>
