<?php
App::uses('AppModel', 'Model');

class InstitutionSiteTeacher extends AppModel {
	public function checkEmployment($institutionSiteId, $teacherId) {
		$count = $this->find('count', array(
			'conditions' => array(
				'InstitutionSiteTeacher.institution_site_id' => $institutionSiteId,
				'InstitutionSiteTeacher.teacher_id' => $teacherId
			)
		));
		return $count;
	}
	
	public function saveEmployment($data, $institutionSiteId, $teacherId) {
		$categoryList = array();
		$startDateList = array();
		$index = 0;
		foreach($data as $i => &$obj) {
			$obj['institution_site_id'] = $institutionSiteId;
			$obj['teacher_id'] = $teacherId;
			$obj['start_year'] = date('Y', strtotime($obj['start_date']));
			if(strtotime($obj['end_date']) < 0) {
				unset($obj['end_date']);
			} else {
				$obj['end_year'] = date('Y', strtotime($obj['end_date']));
			}
		}
		$this->saveMany($data);
	}
	
	public function getPositions($teacherId, $institutionSiteId) {
		$data = $this->find('all', array(
			'fields' => array(
				'InstitutionSiteTeacher.id', 'InstitutionSiteTeacher.start_date',
				'InstitutionSiteTeacher.end_date', 'InstitutionSiteTeacher.salary',
				'TeacherCategory.name'
			),
			'joins' => array(
				array(
					'table' => 'teacher_categories',
					'alias' => 'TeacherCategory',
					'conditions' => array('TeacherCategory.id = InstitutionSiteTeacher.teacher_category_id')
				)
			),
			'conditions' => array(
				'InstitutionSiteTeacher.teacher_id' => $teacherId,
				'InstitutionSiteTeacher.institution_site_id' => $institutionSiteId
			),
			'order' => array('InstitutionSiteTeacher.start_date DESC', 'InstitutionSiteTeacher.end_date')
		));
		return $data;
	}
	
	// Used by institution site classes
	public function getTeacherSelectList($year, $institutionSiteId) {
		$data = $this->find('all', array(
			'fields' => array(
				'Teacher.id', 'Teacher.identification_no', 'Teacher.first_name', 
				'Teacher.last_name', 'Teacher.gender'
			),
			'joins' => array(
				array(
					'table' => 'teachers',
					'alias' => 'Teacher',
					'conditions' => array('Teacher.id = InstitutionSiteTeacher.teacher_id')
				)
			),
			'conditions' => array(
				'InstitutionSiteTeacher.institution_site_id' => $institutionSiteId,
				'InstitutionSiteTeacher.start_year <=' => $year,
				'OR' => array(
					'InstitutionSiteTeacher.end_year >=' => $year,
					'InstitutionSiteTeacher.end_year IS NULL'
				)
			),
			'group' => array('Teacher.id'),
			'order' => array('Teacher.first_name')
		));
		return $data;
	}
	
	public function paginateJoins($conditions) {
		$year = $conditions['year'];
		$joins = array(
			array(
				'table' => 'teachers',
				'alias' => 'Teacher',
				'conditions' => array('Teacher.id = InstitutionSiteTeacher.teacher_id')
			),
			array(
				'table' => 'teacher_categories',
				'alias' => 'TeacherCategory',
				'conditions' => array('TeacherCategory.id = InstitutionSiteTeacher.teacher_category_id')
			)
		);
		return $joins;
	}
	
	public function paginateConditions($conditions) {
		if(isset($conditions['year'])) {
			$year = $conditions['year'];
			unset($conditions['year']);
			
			$conditions = array_merge($conditions, array( // if the year falls between the start and end date
				'InstitutionSiteTeacher.start_year <=' => $year,
				'OR' => array(
					'InstitutionSiteTeacher.end_year >=' => $year,
					'InstitutionSiteTeacher.end_year IS NULL'
				)
			));
		}
		return $conditions;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$data = $this->find('all', array(
			'fields' => array('Teacher.id', 'Teacher.identification_no', 'Teacher.first_name', 'Teacher.last_name', 'TeacherCategory.name'),
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