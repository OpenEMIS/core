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

class InstitutionSiteStudent extends AppModel {
	public $belongsTo = array('StudentStatus');
	
	public function getDetails($studentId, $institutionSiteId) {
		$data = $this->find('all', array(
			'fields' => array('InstitutionSiteStudent.*', 'EducationProgramme.*', 'StudentStatus.*'),
			'recursive' => 0,
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId,
						'InstitutionSiteProgramme.id = InstitutionSiteStudent.institution_site_programme_id'
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
				)
			),
			'conditions' => array('InstitutionSiteStudent.student_id' => $studentId),
			'order' => array('InstitutionSiteStudent.start_date')
		));
		return $data;
	}
	
	public function getGenderTotal($siteProgrammeId) {
		$joins = array(
			array('table' => 'students', 'alias' => 'Student')
		);
		
		$gender = array('M' => 0, 'F' => 0);
		$studentConditions = array('Student.id = InstitutionSiteStudent.student_id');
		
		foreach($gender as $i => $val) {
			$studentConditions[1] = sprintf("Student.gender = '%s'", $i);
			$joins[0]['conditions'] = $studentConditions;
			$gender[$i] = $this->find('count', array(
				'joins' => $joins,
				'conditions' => array('InstitutionSiteStudent.institution_site_programme_id' => $siteProgrammeId)
			));
		}
		return $gender;
	}
	
	public function getStudentSelectList($year, $institutionSiteId, $gradeId) {
		$conditions = array( // if the year falls between the start and end date
			'InstitutionSiteStudent.start_year <=' => $year,
			'InstitutionSiteStudent.end_year >=' => $year
		);
		
		$InstitutionSiteClassGrade = ClassRegistry::init('InstitutionSiteClassGrade');
		$exclude = $InstitutionSiteClassGrade->getStudentIdsByProgramme($gradeId);
		
		$studentConditions = array('Student.id = InstitutionSiteStudent.student_id');
		if(!empty($exclude)) {
			$studentConditions[] = 'Student.id NOT IN (' . implode(',', array_keys($exclude)) . ')';
		}
		
		$data = $this->find('all', array(
			'fields' => array(
				'Student.id', 'Student.identification_no', 
				'Student.first_name', 'Student.middle_name', 'Student.last_name'
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
						'InstitutionSiteProgramme.id = InstitutionSiteStudent.institution_site_programme_id',
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
			'group' => array('Student.id'),
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
				'SchoolYear.start_year AS year'
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
		$endYear = $year+$duration;
		$endDate = $endYear . '-' . date('m-d');
		
		$obj = array(
			'start_date' => $startDate,
			'start_year' => $year,
			'end_date' => $endDate,
			'end_year' => $endYear,
			'student_id' => $studentId,
			'institution_site_programme_id' => $siteProgrammeId
		);
		return $this->save($obj);
	}
	
	public function getStudentList($programmeId, $institutionSiteId, $yearId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteStudent.id', 'SchoolYear.name AS year',
				'InstitutionSiteStudent.start_date, InstitutionSiteStudent.end_date',
				'Student.id AS student_id',	'Student.identification_no', 
				'Student.first_name', 'Student.middle_name', 'Student.last_name'
			),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('Student.id = InstitutionSiteStudent.student_id')
				),
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.id = InstitutionSiteStudent.institution_site_programme_id',
						'InstitutionSiteProgramme.education_programme_id = ' . $programmeId,
						'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId,
						'InstitutionSiteProgramme.school_year_id = ' . $yearId
					)
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array('SchoolYear.id = InstitutionSiteProgramme.school_year_id')
				)
			),
			'order' => array('Student.first_name', 'Student.last_name')
		));
		return $data;
	}
	
	public function isStudentExistsInProgramme($studentId, $siteProgrammeId, $year) {
		$count = $this->find('count', array(
			'conditions' => array(
				'InstitutionSiteStudent.student_id' => $studentId,
				'InstitutionSiteStudent.institution_site_programme_id' => $siteProgrammeId,
				'InstitutionSiteStudent.start_year' => $year
			)
		));
		return $count==1;
	}
	
	public function paginateJoins(&$conditions) {
		$institutionSiteId = $conditions['institution_site_id'];
		unset($conditions['institution_site_id']);
		
		$programmeConditions = array(
			'InstitutionSiteProgramme.id = InstitutionSiteStudent.institution_site_programme_id',
			'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId
		);
		
		if(isset($conditions['year'])) {
			$year = $conditions['year'];
			unset($conditions['year']);
			
			$conditions = array_merge($conditions, array( // if the year falls between the start and end date
				'InstitutionSiteStudent.start_year <=' => $year,
				'InstitutionSiteStudent.end_year >=' => $year
			));
		}
		
		if(isset($conditions['education_programme_id'])) {
			$programmeConditions[] = 'InstitutionSiteProgramme.education_programme_id = ' . $conditions['education_programme_id'];
			unset($conditions['education_programme_id']);
		}
		
		$joins = array(
			array(
				'table' => 'students',
				'alias' => 'Student',
				'conditions' => array('Student.id = InstitutionSiteStudent.student_id')
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
                                'Student.middle_name LIKE' => $search,
				'Student.last_name LIKE' => $search,
                                'Student.preferred_name LIKE' => $search
			);
		}
		unset($conditions['search']);
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$order = $conditions['order'];
		unset($conditions['order']);
		$this->paginateConditions($conditions);
		$data = $this->find('all', array(
			'fields' => array('Student.id', 'Student.identification_no', 'Student.first_name', 'Student.middle_name', 'Student.last_name', 'Student.preferred_name', 'EducationProgramme.name'),
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $conditions,
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'group' => array('Student.id', 'EducationProgramme.id'),
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
        
        public function getRecordIdsByStudentIdAndSiteId($studentId, $InstitutionSiteId) {
		$data = $this->find('list', array(
			'fields' => array('InstitutionSiteStudent.id'),
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
                                                            'InstitutionSiteStudent.institution_site_programme_id = InstitutionSiteProgramme.id',
                                                            'InstitutionSiteProgramme.institution_site_id = ' . $InstitutionSiteId
                                                        )
				)
			),
                        'conditions' => array('InstitutionSiteStudent.student_id = ' . $studentId)
		));
		return $data;
	}
}
