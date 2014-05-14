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
    public $actsAs = array('ControllerAction');
    
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
        
        public function checkWithinCurrentSite($studentId, $startYear, $InstitutionSiteId){
            $data = $this->find('all', array(
                    'recursive' => -1,
                    'joins' => array(
                        array(
                            'table' => 'institution_site_programmes',
                            'alias' => 'InstitutionSiteProgramme',
                            'conditions' => array(
                                'InstitutionSiteStudent.institution_site_programme_id = InstitutionSiteProgramme.id'
                            )
                        )
                    ),
                    'conditions' => array(
                        'InstitutionSiteStudent.student_id = ' . $studentId,
                        'InstitutionSiteStudent.start_year = ' . $startYear,
                        'InstitutionSiteProgramme.institution_site_id = ' . $InstitutionSiteId
                    )
                )
            );
            
            if(count($data) >= 1){
                return false;
            }else{
                return true;
            }
        }
        
        public function checkWithinOtherSite($studentId, $startYear, $InstitutionSiteId){
            $data = $this->find('all', array(
                    'recursive' => -1,
                    'joins' => array(
                        array(
                            'table' => 'student_statuses',
                            'alias' => 'StudentStatus',
                            'conditions' => array(
                                'InstitutionSiteStudent.student_status_id = StudentStatus.id'
                            )
                        ),
                        array(
                            'table' => 'institution_site_programmes',
                            'alias' => 'InstitutionSiteProgramme',
                            'conditions' => array(
                                'InstitutionSiteStudent.institution_site_programme_id = InstitutionSiteProgramme.id'
                            )
                        )
                    ),
                    'conditions' => array(
                        'InstitutionSiteStudent.student_id = ' . $studentId,
                        'InstitutionSiteStudent.start_year = ' . $startYear,
                        'InstitutionSiteProgramme.institution_site_id <> ' . $InstitutionSiteId,
                        'StudentStatus.id = 1'
                    )
                )
            );
            
            if(count($data) >= 1){
                return false;
            }else{
                return true;
            }
        }
        
        public function students($controller, $params) {
        App::uses('Sanitize', 'Utility');
        $controller->Navigation->addCrumb('List of Students');

        $page = isset($controller->params->named['page']) ? $controller->params->named['page'] : 1;

        $selectedYear = "";
        $selectedProgramme = "";
        $searchField = "";
        $orderBy = 'Student.first_name';
        $order = 'asc';
        $yearOptions = $controller->SchoolYear->getYearListValues('start_year');
        $programmeOptions = $controller->InstitutionSiteProgramme->getProgrammeOptions($controller->institutionSiteId);
        $prefix = 'InstitutionSiteStudent.Search.%s';
        if ($controller->request->is('post')) {
            $searchField = Sanitize::escape(trim($controller->data['Student']['SearchField']));
            $selectedYear = $controller->data['Student']['school_year'];
            $selectedProgramme = $controller->data['Student']['education_programme_id'];
            $orderBy = $controller->data['Student']['orderBy'];
            $order = $controller->data['Student']['order'];

            $controller->Session->write(sprintf($prefix, 'SearchField'), $searchField);
            $controller->Session->write(sprintf($prefix, 'SchoolYear'), $selectedYear);
            $controller->Session->write(sprintf($prefix, 'EducationProgrammeId'), $selectedProgramme);
            $controller->Session->write(sprintf($prefix, 'order'), $order);
            $controller->Session->write(sprintf($prefix, 'orderBy'), $orderBy);
        } else {
            $searchField = $controller->Session->read(sprintf($prefix, 'SearchField'));
            $selectedYear = $controller->Session->read(sprintf($prefix, 'SchoolYear'));
            $selectedProgramme = $controller->Session->read(sprintf($prefix, 'EducationProgrammeId'));

            if ($controller->Session->check(sprintf($prefix, 'orderBy'))) {
                $orderBy = $controller->Session->read(sprintf($prefix, 'orderBy'));
            }
            if ($controller->Session->check(sprintf($prefix, 'order'))) {
                $order = $controller->Session->read(sprintf($prefix, 'order'));
            }
        }
        $conditions = array('institution_site_id' => $controller->institutionSiteId, 'order' => array($orderBy => $order));
        $conditions['search'] = $searchField;
        if (!empty($selectedYear)) {
            $conditions['year'] = $selectedYear;
        }

        if (!empty($selectedProgramme)) {
            $conditions['education_programme_id'] = $selectedProgramme;
        }

        $controller->paginate = array('limit' => 15, 'maxLimit' => 100);
        $data = $controller->paginate('InstitutionSiteStudent', $conditions);

        if (empty($data)) {
            $controller->Utility->alert($controller->Utility->getMessage('STUDENT_SEARCH_NO_RESULT'), array('type' => 'info', 'dismissOnClick' => false));
        }

        // Checking if user has access to add
        $_add_student = $controller->AccessControl->check('InstitutionSites', 'studentsAdd');
        
        // End Access Control
        
        $controller->set(compact('_add_student', 'searchField', 'page', 'orderBy', 'order', 'yearOptions', 'programmeOptions', 'selectedYear', 'selectedProgramme', 'data'));
    }

    public function studentsSearch($controller, $params) {
        $controller->layout = 'ajax';
        $search = trim($controller->params->query['searchString']);
        $params = array('limit' => 100);
        $data = $controller->Student->search($search, $params);
        
        $controller->set(compact('search', 'data'));
    }

    public function studentsAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Student');
        $yearOptions = $controller->SchoolYear->getYearList();
        $yearRange = $controller->SchoolYear->getYearRange();
        $statusOptions = $controller->StudentStatus->findList(true);
        $programmeOptions = array();
        $selectedYear = '';
        if (!empty($yearOptions)) {
            $selectedYear = key($yearOptions);
            $programmeOptions = $controller->InstitutionSiteProgramme->getSiteProgrammeForSelection($controller->institutionSiteId, $selectedYear);
        }
        $minYear = current($yearRange);
        $maxYear = array_pop($yearRange);
        
        $controller->set(compact('yearOptions', 'minYear', 'maxYear', 'programmeOptions', 'statusOptions'));
    }

    public function studentsSave($controller, $params) {
        if ($controller->request->is('post')) {
            $data = $controller->data['InstitutionSiteStudent'];
            if (isset($data['student_id'])) {
                $date = $data['start_date'];
                if (!empty($date)) {
					$startDate = new DateTime($date);//new DateTime(sprintf('%d-%d-%d', $date['year'], $date['month'], $date['day']));
					
					$data['start_date'] = $startDate->format('Y-m-d');
                    $data['start_year'] = $startDate->format('Y');
//                    $yr = $date['year'];
//                    $mth = $date['month'];
//                    $day = $date['day'];

//                    while (!checkdate($mth, $day, $yr)) {
//                        $day--;
//                    }
                    //$data['start_date'] = sprintf('%d-%d-%d', $yr, $mth, $day);
                    //$date = $data['start_date'];
                    $student = $controller->Student->find('first', array('conditions' => array('Student.id' => $data['student_id'])));
                    $name = $student['Student']['first_name'] . ' ' . $student['Student']['last_name'];
                    $siteProgrammeId = $data['institution_site_programme_id'];
                    $exists = $controller->InstitutionSiteStudent->isStudentExistsInProgramme($data['student_id'], $siteProgrammeId, $data['start_year']);
                    $checkCurrentSite = $controller->InstitutionSiteStudent->checkWithinCurrentSite($data['student_id'], $data['start_year'], $controller->institutionSiteId);
                    $checkOtherSite = $controller->InstitutionSiteStudent->checkWithinOtherSite($data['student_id'], $data['start_year'], $controller->institutionSiteId);

                    
                    if (!$checkCurrentSite) {
                        $controller->Utility->alert($name . ' ' . $controller->Utility->getMessage('STUDENT_ALREADY_ADDED'), array('type' => 'error'));
                    } else if(!$checkOtherSite){
                        $controller->Utility->alert($name . ' ' . $controller->Utility->getMessage('STUDENT_ALREADY_EXISTS_IN_OTHER_SITE'), array('type' => 'error'));
                    }else {
                        $duration = $controller->EducationProgramme->getDurationBySiteProgramme($siteProgrammeId);
                        
                        $endDate = $startDate->add(new DateInterval('P' . $duration . 'Y'));
                        $endYear = $endDate->format('Y');
                        $data['end_date'] = $endDate->format('Y-m-d');
                        $data['end_year'] = $endYear;
                        $controller->InstitutionSiteStudent->save($data);
                        $controller->Utility->alert($controller->Utility->getMessage('CREATE_SUCCESS'));
                    }
                } else {
                    $controller->Utility->alert($controller->Utility->getMessage('INVALID_DATE'), array('type' => 'error'));
                }
                $controller->redirect(array('action' => 'studentsAdd'));
            }
        }
    }

    public function studentsView($controller, $params) {
        if (isset($controller->params['pass'][0])) {
            $studentId = $controller->params['pass'][0];
            $controller->Session->write('InstitutionSiteStudentId', $studentId);
            $data = $controller->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
            $name = sprintf('%s %s %s', $data['Student']['first_name'], $data['Student']['middle_name'], $data['Student']['last_name']);
            $controller->Navigation->addCrumb($name);

            $details = $controller->InstitutionSiteStudent->getDetails($studentId, $controller->institutionSiteId);
            $classes = $controller->InstitutionSiteClassGradeStudent->getListOfClassByStudent($studentId, $controller->institutionSiteId);
            $results = $controller->AssessmentItemResult->getResultsByStudent($studentId, $controller->institutionSiteId);
            $results = $controller->AssessmentItemResult->groupItemResults($results);
            $_view_details = $controller->AccessControl->check('Students', 'view');
            
            $controller->set(compact('_view_details', 'data', 'classes', 'results', 'details'));
        } else {
            $controller->redirect(array('action' => 'students'));
        }
    }

    public function studentsDelete($controller, $params) {
        if ($controller->Session->check('InstitutionSiteStudentId') && $controller->Session->check('InstitutionSiteId')) {
            $studentId = $controller->Session->read('InstitutionSiteStudentId');
            $InstitutionSiteId = $controller->Session->read('InstitutionSiteId');

            $SiteStudentRecordIds = $controller->InstitutionSiteStudent->getRecordIdsByStudentIdAndSiteId($studentId, $InstitutionSiteId);
            if (!empty($SiteStudentRecordIds)) {
                $controller->InstitutionSiteStudent->deleteAll(array('InstitutionSiteStudent.id' => $SiteStudentRecordIds), false);
            }

            $GradeStudentRecordIds = $controller->InstitutionSiteClassGradeStudent->getRecordIdsByStudentIdAndSiteId($studentId, $InstitutionSiteId);
            if (!empty($GradeStudentRecordIds)) {
                $controller->InstitutionSiteClassGradeStudent->deleteAll(array('InstitutionSiteClassGradeStudent.id' => $GradeStudentRecordIds), false);
            }

            $controller->AssessmentItemResult->deleteAll(array(
                'AssessmentItemResult.student_id' => $studentId,
                'AssessmentItemResult.institution_site_id' => $InstitutionSiteId
                    ), false);

            $controller->StudentBehaviour->deleteAll(array(
                'StudentBehaviour.student_id' => $studentId,
                'StudentBehaviour.institution_site_id' => $InstitutionSiteId
                    ), false);

            $controller->StudentAttendance->deleteAll(array(
                'StudentAttendance.student_id' => $studentId,
                'StudentAttendance.institution_site_id' => $InstitutionSiteId
                    ), false);

            $StudentDetailsCustomValueObj = ClassRegistry::init('StudentDetailsCustomValue');
            $StudentDetailsCustomValueObj->deleteAll(array(
                'StudentDetailsCustomValue.student_id' => $studentId,
                'StudentDetailsCustomValue.institution_site_id' => $InstitutionSiteId
                    ), false);


            $controller->Utility->alert($controller->Utility->getMessage('DELETE_SUCCESS'));
            $controller->redirect(array('action' => 'students'));
        } else {
            $controller->redirect(array('action' => 'students'));
        }
    }

    public function studentsEdit($controller, $params) {
        if ($controller->Session->check('InstitutionSiteStudentId')) {
            $studentId = $controller->Session->read('InstitutionSiteStudentId');
            if ($controller->request->is('post')) {
                $postData = $controller->request->data['InstitutionSiteStudent'];
                foreach ($postData as $i => $obj) {
                    $postData[$i]['start_year'] = date('Y', strtotime($obj['start_date']));
                    $postData[$i]['end_year'] = date('Y', strtotime($obj['end_date']));
                }
                $controller->InstitutionSiteStudent->saveMany($postData);
                $controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
                return $controller->redirect(array('action' => 'studentsView', $studentId));
            }

            $data = $controller->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
            $name = sprintf('%s %s %s', $data['Student']['first_name'], $data['Student']['middle_name'], $data['Student']['last_name']);
            $controller->Navigation->addCrumb($name);
            $statusOptions = $controller->StudentStatus->findList(true);

            $details = $controller->InstitutionSiteStudent->getDetails($studentId, $controller->institutionSiteId);
            $classes = $controller->InstitutionSiteClassGradeStudent->getListOfClassByStudent($studentId, $controller->institutionSiteId);
            $results = $controller->AssessmentItemResult->getResultsByStudent($studentId, $controller->institutionSiteId);
            $results = $controller->AssessmentItemResult->groupItemResults($results);
            $_view_details = $controller->AccessControl->check('Students', 'view');
            
            $controller->set(compact('_view_details', 'data', 'classes', 'results', 'details', 'statusOptions'));
        } else {
            $controller->redirect(array('action' => 'students'));
        }
    }
}
