<?php
App::uses('AppModel', 'Model');

class InstitutionSiteStaff extends AppModel {
	public $useTable = 'institution_site_staff';
	
	public function checkEmployment($institutionSiteId, $staffId) {
		$count = $this->find('count', array(
			'conditions' => array(
				'InstitutionSiteStaff.institution_site_id' => $institutionSiteId,
				'InstitutionSiteStaff.staff_id' => $staffId
			)
		));
		return $count;
	}
	
	public function saveEmployment($data, $institutionSiteId, $staffId) {
		$categoryList = array();
		$startDateList = array();
		$index = 0;
		foreach($data as $i => &$obj) {
			$obj['institution_site_id'] = $institutionSiteId;
			$obj['staff_id'] = $staffId;
			$obj['start_year'] = date('Y', strtotime($obj['start_date']));
			if(strtotime($obj['end_date']) < 0) {
				unset($obj['end_date']);
			} else {
				$obj['end_year'] = date('Y', strtotime($obj['end_date']));
			}
		}
		$this->saveMany($data);
	}
	
	public function getPositions($staffId, $institutionSiteId) {
		$data = $this->find('all', array(
			'fields' => array(
				'InstitutionSiteStaff.id', 'InstitutionSiteStaff.start_date',
				'InstitutionSiteStaff.end_date', 'InstitutionSiteStaff.salary',
				'StaffCategory.name'
			),
			'joins' => array(
				array(
					'table' => 'staff_categories',
					'alias' => 'StaffCategory',
					'conditions' => array('StaffCategory.id = InstitutionSiteStaff.staff_category_id')
				)
			),
			'conditions' => array(
				'InstitutionSiteStaff.staff_id' => $staffId,
				'InstitutionSiteStaff.institution_site_id' => $institutionSiteId
			),
			'order' => array('InstitutionSiteStaff.start_date DESC', 'InstitutionSiteStaff.end_date')
		));
		return $data;
	}
	
	public function paginateJoins($conditions) {
		$year = $conditions['year'];
		$joins = array(
			array(
				'table' => 'staff',
				'alias' => 'Staff',
				'conditions' => array('Staff.id = InstitutionSiteStaff.staff_id')
			),
			array(
				'table' => 'staff_categories',
				'alias' => 'StaffCategory',
				'conditions' => array('StaffCategory.id = InstitutionSiteStaff.staff_category_id')
			)
		);
		return $joins;
	}
	
	public function paginateConditions($conditions) {
		if(isset($conditions['year'])) {
			$year = $conditions['year'];
			unset($conditions['year']);
			
			$conditions = array_merge($conditions, array( // if the year falls between the start and end date
				'InstitutionSiteStaff.start_year <=' => $year,
				'OR' => array(
					'InstitutionSiteStaff.end_year >=' => $year,
					'InstitutionSiteStaff.end_year IS NULL'
				)
			));
		}
		return $conditions;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$data = $this->find('all', array(
			'fields' => array('Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.last_name', 'StaffCategory.name'),
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $this->paginateConditions($conditions),
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'order' => $order
		));
		return $data;
	}
	 
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$count = $this->find('count', array(
			'joins' => $this->paginateJoins($conditions), 
			'conditions' => $this->paginateConditions($conditions)
		));
		return $count;
	}
}