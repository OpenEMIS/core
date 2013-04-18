<?php
App::uses('AppModel', 'Model');

class InstitutionSiteProgrammeStudent extends AppModel {

	public function getGenderTotal($siteProgrammeId, $yearId) {
		$joins = array(
			array('table' => 'students', 'alias' => 'Student')
		);
		
		$gender = array('M' => 0, 'F' => 0);
		$studentConditions = array('Student.id = InstitutionSiteProgrammeStudent.student_id');
		
		foreach($gender as $i => $val) {
			$studentConditions[1] = sprintf("Student.gender = '%s'", $i);
			$joins[0]['conditions'] = $studentConditions;
			$gender[$i] = $this->find('count', array(
				'joins' => $joins,
				'conditions' => array(
					'InstitutionSiteProgrammeStudent.institution_site_programme_id' => $siteProgrammeId,
					'InstitutionSiteProgrammeStudent.school_year_id' => $yearId
				)
			));
		}
		return $gender;
	}
	
	public function getStudentSelectList($year, $institutionSiteId, $gradeId) {
		// if datasource is mysql
		$conditions = array( // if the year falls between the start and end date
			'YEAR(InstitutionSiteProgrammeStudent.start_date) <=' => $year,
			'YEAR(InstitutionSiteProgrammeStudent.end_date) >=' => $year
		);
		
		$InstitutionSiteClassGrade = ClassRegistry::init('InstitutionSiteClassGrade');
		$exclude = $InstitutionSiteClassGrade->getStudentIdsByProgramme($gradeId);
		
		$studentConditions = array('Student.id = InstitutionSiteProgrammeStudent.student_id');
		if(!empty($exclude)) {
			$studentConditions[] = 'Student.id NOT IN (' . implode(',', array_keys($exclude)) . ')';
		}
		
		$data = $this->find('all', array(
			'fields' => array(
				'Student.id', 'Student.identification_no', 'Student.first_name', 
				'Student.last_name', 'Student.gender'
			),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => $studentConditions
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
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.education_programme_id = InstitutionSiteProgramme.education_programme_id')
				),
				array(
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array(
						'InstitutionSiteClassGrade.id = ' . $gradeId,
						'InstitutionSiteClassGrade.education_grade_id = EducationGrade.id'
					)
				)
			),
			'conditions' => $conditions,
			'order' => array('Student.first_name')
		));
		return $data;
	}
	
	public function addStudentToProgramme($studentId, $programmeId, $institutionSiteId, $yearId) {
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
		
		$siteProgramme = $InstitutionSiteProgramme->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteProgramme.id',
				'EducationProgramme.duration',
				'SchoolYear.name AS year'
			),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array('SchoolYear.id = InstitutionSiteProgramme.school_year_id')
				)
			),
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
				'InstitutionSiteProgramme.education_programme_id' => $programmeId,
				'InstitutionSiteProgramme.school_year_id' => $yearId
			)
		));
		
		$siteProgrammeId = $siteProgramme[0]['InstitutionSiteProgramme']['id'];
		$duration = $siteProgramme[0]['EducationProgramme']['duration'];
		$year = $siteProgramme[0]['SchoolYear']['year'];
		
		$startDate = $year . '-' . date('m-d');
		$endDate = $year+$duration . '-' . date('m-d');
		
		$obj = array(
			'start_date' => $startDate,
			'end_date' => $endDate,
			'student_id' => $studentId,
			'institution_site_programme_id' => $siteProgrammeId,
			'school_year_id' => $yearId
		);
		return $this->save($obj);
	}
	
	public function getStudentList($programmeId, $institutionSiteId, $yearId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteProgrammeStudent.id', 'SchoolYear.name AS year',
				'InstitutionSiteProgrammeStudent.start_date, InstitutionSiteProgrammeStudent.end_date',
				'Student.id AS student_id',	'Student.identification_no', 
				'Student.first_name', 'Student.last_name'
			),
			'joins' => array(
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
						'InstitutionSiteProgramme.education_programme_id = ' . $programmeId,
						'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId,
						'InstitutionSiteProgramme.school_year_id = ' . $yearId
					)
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array('SchoolYear.id = InstitutionSiteProgrammeStudent.school_year_id')
				)
			),
			'order' => array('Student.first_name', 'Student.last_name')
		));
		return $data;
	}
	
	public function paginateJoins(&$conditions) {
		$institutionSiteId = $conditions['institution_site_id'];
		unset($conditions['institution_site_id']);
		
		$programmeConditions = array(
			'InstitutionSiteProgramme.id = InstitutionSiteProgrammeStudent.institution_site_programme_id',
			'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId
		);
		
		if(isset($conditions['InstitutionSiteProgrammeStudent.school_year_id'])) {
			$programmeConditions[] = 'InstitutionSiteProgramme.school_year_id = ' . $conditions['InstitutionSiteProgrammeStudent.school_year_id'];
		}
		
		if(isset($conditions['education_programme_id'])) {
			$programmeConditions[] = 'InstitutionSiteProgramme.education_programme_id = ' . $conditions['education_programme_id'];
			unset($conditions['education_programme_id']);
		}
		
		$joins = array(
			array(
				'table' => 'students',
				'alias' => 'Student',
				'conditions' => array('Student.id = InstitutionSiteProgrammeStudent.student_id')
			),
			array(
				'table' => 'institution_site_programmes',
				'alias' => 'InstitutionSiteProgramme',
				'conditions' => $programmeConditions
			),
			array(
				'table' => 'education_programmes',
				'alias' => 'EducationProgramme',
				'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
			)
		);
		return $joins;
	}
	
	public function paginateConditions(&$conditions) {
		if(isset($conditions['search']) && !empty($conditions['search'])) {
			$search = $conditions['search'];
			$search = '%' . $search . '%';
			$conditions['OR'] = array(
				'Student.identification_no LIKE' => $search,
				'Student.first_name LIKE' => $search,
				'Student.last_name LIKE' => $search
			);
		}
		unset($conditions['search']);
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$order = $conditions['order'];
		unset($conditions['order']);
		$this->paginateConditions($conditions);
		$data = $this->find('all', array(
			'fields' => array('Student.identification_no', 'Student.first_name', 'Student.last_name', 'EducationProgramme.name'),
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $conditions,
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'order' => $order
		));
		return $data;
	}
	 
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		unset($conditions['order']);
		$this->paginateConditions($conditions);
		$count = $this->find('count', array(
			'joins' => $this->paginateJoins($conditions), 
			'conditions' => $conditions
		));
		return $count;
	}
}