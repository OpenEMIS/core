<?php
App::uses('AppModel', 'Model');

class InstitutionSiteProgrammeStudent extends AppModel {
	
	public function addStudentToProgramme($studentId, $yearId, $programmeId) {
		$SchoolYear = ClassRegistry::init('SchoolYear');
		$EducationProgramme = ClassRegistry::init('EducationProgramme');
		
		$year = $SchoolYear->field('name', array('SchoolYear.id' => $yearId));
		$educationProgramme = $EducationProgramme->find('all', array(
			'recursive' => -1,
			'fields' => array('EducationProgramme.duration'),
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.id = ' . $programmeId,
						'InstitutionSiteProgramme.education_programme_id = EducationProgramme.id'
					)
				)
			)
		));
		$duration = $educationProgramme[0]['EducationProgramme']['duration'];
		$startDate = $year . '-' . date('m-d');
		$endDate = $year+$duration . '-' . date('m-d');
		
		$obj = array(
			'start_date' => $startDate,
			'end_date' => $endDate,
			'student_id' => $studentId,
			'institution_site_programme_id' => $programmeId,
			'school_year_id' => $yearId
		);
		return $this->save($obj);
	}
	
	public function getFirstLetterPagination($yearId, $programmeId) {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('Student.first_name'),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('Student.id = InstitutionSiteProgrammeStudent.student_id')
				)
			),
			'conditions' => array(
				'InstitutionSiteProgrammeStudent.school_year_id' => $yearId,
				'InstitutionSiteProgrammeStudent.institution_site_programme_id' => $programmeId
			),
			'order' => array('Student.first_name')
		));
		
		$list = array();
		
		foreach($data as $obj) {
			$student = $obj['Student'];
			$first = strtoupper(substr($student['first_name'], 0, 1));
			if(!in_array($first, $list)) {
				$list[] = $first;
			}
		}
		return $list;
	}
	
	public function getStudentListByFirstLetter($first, $yearId, $programmeId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteProgrammeStudent.id', 'SchoolYear.name AS year',
				'InstitutionSiteProgrammeStudent.start_date, InstitutionSiteProgrammeStudent.end_date',
				'Student.identification_no', 'Student.first_name', 'Student.last_name'
			),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('Student.id = InstitutionSiteProgrammeStudent.student_id')
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array('SchoolYear.id = InstitutionSiteProgrammeStudent.school_year_id')
				)
			),
			'conditions' => array(
				'InstitutionSiteProgrammeStudent.school_year_id' => $yearId,
				'InstitutionSiteProgrammeStudent.institution_site_programme_id' => $programmeId,
				'Student.first_name LIKE' => $first . '%'
			),
			'order' => array('Student.first_name')
		));
		return $data;
	}
	
	public function paginateJoins(&$conditions) {
		$institutionSiteId = $conditions['institution_site_id'];
		unset($conditions['institution_site_id']);
		
		$joins = array(
			array(
				'table' => 'students',
				'alias' => 'Student',
				'conditions' => array('Student.id = InstitutionSiteProgrammeStudent.student_id')
			),
			array(
				'table' => 'institution_site_programmes',
				'alias' => 'InstitutionSiteProgramme',
				'conditions' => array(
					'InstitutionSiteProgramme.id = InstitutionSiteProgrammeStudent.institution_site_programme_id',
					'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId
				)
			),
			array(
				'table' => 'education_programmes',
				'alias' => 'EducationProgramme',
				'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
			)
		);
		return $joins;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$order = $conditions['order'];
		unset($conditions['order']);
		
		$data = $this->find('all', array(
			'fields' => array('Student.identification_no', 'Student.first_name', 'Student.last_name', 'EducationProgramme.name'),
			'joins' => $this->paginateJoins($conditions),
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'order' => $order
		));
		return $data;
	}
	 
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$count = $this->find('count', array('joins' => $this->paginateJoins($conditions)));
		return $count;
	}
}