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
	public $actsAs = array(
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $validate = array(
		'search' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a OpenEMIS ID or name.'
			)
		),
		'institution_site_programme_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Programme.'
			)
		)
	);
	public $belongsTo = array('Student', 'StudentStatus', 'InstitutionSiteProgramme');
	
	public $reportMapping = array(
		1 => array(
			'fields' => array(
				'Student' => array(
					'identification_no' => 'OpenEMIS ID',
					'first_name' => 'First Name',
					'middle_name' => 'Middle Name',
					'last_name' => 'Last Name',
					'preferred_name' => 'Preferred Name'
				),
				'StudentContact' => array(
					'GROUP_CONCAT(DISTINCT CONCAT(ContactType.name, "-", StudentContact.value))' => 'Contacts'
				),
				'StudentIdentity' => array(
					'GROUP_CONCAT(DISTINCT CONCAT(IdentityType.name, "-", StudentIdentity.number))' => 'Identities'
				),
				'StudentNationality' => array(
					'GROUP_CONCAT(DISTINCT Country.name)' => 'Nationality'
				),
				'StudentStatus' => array(
					'name' => 'Status'
				),
				'StudentCustomField' => array(
				),
				'EducationProgramme' => array(
					'name' => 'Programme'
				),
				'InstitutionSite' => array(
					'name' => 'Institution Name',
					'code' => 'Institution Code',
				),
				'InstitutionSiteType' => array(
					'name' => 'Institution Type'
				),
				'InstitutionSiteOwnership' => array(
					'name' => 'Institution Ownership'
				),
				'InstitutionSiteStatus' => array(
					'name' => 'Institution Status'
				),
				'InstitutionSite2' => array(
					'date_opened' => 'Date Opened',
					'date_closed' => 'Date Closed',
				),
				'Area' => array(
					'name' => 'Area'
				),
				'AreaEducation' => array(
					'name' => 'Area (Education)'
				),
				'InstitutionSite3' => array(
					'address' => 'Address',
					'postal_code' => 'Postal Code',
					'longitude' => 'Longitude',
					'latitude' => 'Latitude',
					'contact_person' => 'Contact Person',
					'telephone' => 'Telephone',
					'fax' => 'Fax',
					'email' => 'Email',
					'website' => 'Website'
				)
			),
			'fileName' => 'Report_Student_List'
		)
	);
	
	public function getDetails($studentId, $institutionSiteId) {
		$this->unbindModel(array('belongsTo' => array('InstitutionSiteProgramme')));
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
		$this->bindModel(array('belongsTo' => array('InstitutionSiteProgramme')));
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
		$this->unbindModel(array('belongsTo' => array('Student', 'InstitutionSiteProgramme')));
		$data = $this->find('all', array(
			'fields' => array('Student.id', 'Student.identification_no', 'Student.first_name', 'Student.middle_name', 'Student.last_name', 'Student.preferred_name', 'EducationProgramme.name', 'StudentStatus.name'),
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $conditions,
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'group' => array('Student.id', 'EducationProgramme.id'),
			'order' => $order
		));
		$this->bindModel(array('belongsTo' => array('Student', 'InstitutionSiteProgramme')));
		return $data;
	}
	 
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		unset($conditions['order']);
		$this->paginateConditions($conditions);
		$this->unbindModel(array('belongsTo' => array('Student', 'InstitutionSiteProgramme')));
		$count = $this->find('count', array(
			'joins' => $this->paginateJoins($conditions), 
			'conditions' => $conditions
		));
		$this->bindModel(array('belongsTo' => array('Student', 'InstitutionSiteProgramme')));
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

		$page = isset($params->named['page']) ? $params->named['page'] : 1;

		$selectedYear = "";
		$selectedProgramme = "";
		$searchField = "";
		$orderBy = 'Student.first_name';
		$order = 'asc';
		$yearOptions = ClassRegistry::init('SchoolYear')->getYearListValues('start_year');
		$programmeOptions = $this->InstitutionSiteProgramme->getProgrammeOptions($controller->institutionSiteId);
		$statusOptions = $this->StudentStatus->findList(true);
		
		$prefix = 'InstitutionSiteStudent.Search.%s';
		if ($controller->request->is('post')) {
			$searchField = Sanitize::escape(trim($controller->data['Student']['SearchField']));
			$selectedYear = $controller->request->data['Student']['school_year'];
			$selectedProgramme = $controller->request->data['Student']['education_programme_id'];
			$selectedStatus = $controller->request->data['Student']['student_status_id'];
			$orderBy = $controller->request->data['Student']['orderBy'];
			$order = $controller->request->data['Student']['order'];

			$controller->Session->write(sprintf($prefix, 'SearchField'), $searchField);
			$controller->Session->write(sprintf($prefix, 'SchoolYear'), $selectedYear);
			$controller->Session->write(sprintf($prefix, 'EducationProgrammeId'), $selectedProgramme);
			$controller->Session->write(sprintf($prefix, 'StudentStatusId'), $selectedStatus);
			$controller->Session->write(sprintf($prefix, 'order'), $order);
			$controller->Session->write(sprintf($prefix, 'orderBy'), $orderBy);
		} else {
			$searchField = $controller->Session->read(sprintf($prefix, 'SearchField'));
			$selectedYear = $controller->Session->read(sprintf($prefix, 'SchoolYear'));
			$selectedProgramme = $controller->Session->read(sprintf($prefix, 'EducationProgrammeId'));
			$selectedStatus = $controller->Session->read(sprintf($prefix, 'StudentStatusId'));

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

		if (!empty($selectedStatus)) {
			$conditions['student_status_id'] = $selectedStatus;
		}

		
		$controller->paginate = array('limit' => 15, 'maxLimit' => 100);
		$data = $controller->paginate('InstitutionSiteStudent', $conditions);

		if (empty($data)) {
			$controller->Utility->alert($controller->Utility->getMessage('STUDENT_SEARCH_NO_RESULT'), array('type' => 'info', 'dismissOnClick' => false));
		}

		// Checking if user has access to add
		$_add_student = $controller->AccessControl->check('InstitutionSites', 'studentsAdd');
		// End Access Control
		
		$controller->set(compact('_add_student', 'searchField', 'page', 'orderBy', 'order', 'yearOptions', 'programmeOptions', 'selectedYear', 'selectedProgramme', 'data', 'statusOptions'));
	}

	private function studentsSearch($search) {
		$params = array('limit' => 100);
		$Student = ClassRegistry::init('Students.Student');
		$list = $Student->search($search, $params);
		
		$data = array();
		foreach ($list as $obj) {
			$studentInfo = $obj['Student'];
			$data[] = array(
				'label' => sprintf('%s - %s %s %s %s', $studentInfo['identification_no'], $studentInfo['first_name'], $studentInfo['middle_name'], $studentInfo['last_name'], $studentInfo['preferred_name']),
				'value' => $studentInfo['id']
			);
		}
		return $data;
	}

	public function studentsAdd($controller, $params) {
		$formData = isset($controller->request->data['InstitutionSiteStudent'])? $controller->request->data['InstitutionSiteStudent']: array();
		$controller->Navigation->addCrumb('Add Student');
		$yearOptions = ClassRegistry::init('SchoolYear')->getYearList();
		$yearRange = ClassRegistry::init('SchoolYear')->getYearRange();
		$statusOptions = $this->StudentStatus->findList(true);
		$programmeOptions = array();
		$selectedYear = !empty($formData['school_year_id'])?$formData['school_year_id']:key($yearOptions);
	
		if (!empty($yearOptions)) {
			$yearData = ClassRegistry::init('SchoolYear')->findById($selectedYear, array('start_date', 'end_date'));
			$programmeOptions = $this->InstitutionSiteProgramme->getSiteProgrammeForSelection($controller->institutionSiteId, $selectedYear);
		}
		
		$minYear = current($yearRange);
		$maxYear = array_pop($yearRange);
		
		$this->studentsSave($controller, $params);
		
		$controller->set(compact('yearOptions', 'minYear', 'maxYear', 'programmeOptions', 'statusOptions', 'yearData'));
	}

	private function studentsSave($controller, $params) {
		if ($controller->request->is('post')) {
			$data = $controller->data['InstitutionSiteStudent'];
			
			$this->set($controller->request->data);
			if ($this->validates()) {

				if (isset($data['student_id']) && !empty($data['student_id'])) {
					if (isset($data['search'])) {
						unset($data['search']);
					}

					$date = $data['start_date'];
					if (!empty($date)) {
						$startDate = new DateTime($date); //new DateTime(sprintf('%d-%d-%d', $date['year'], $date['month'], $date['day']));

						$data['start_date'] = $startDate->format('Y-m-d');
						$data['start_year'] = $startDate->format('Y');
						$student = $controller->Student->find('first', array('conditions' => array('Student.id' => $data['student_id'])));
						$name = $student['Student']['first_name'] . ' ' . $student['Student']['last_name'];
						$siteProgrammeId = $data['institution_site_programme_id'];
						$exists = $this->isStudentExistsInProgramme($data['student_id'], $siteProgrammeId, $data['start_year']);
						$checkCurrentSite = $this->checkWithinCurrentSite($data['student_id'], $data['start_year'], $controller->institutionSiteId);
						$checkOtherSite = $this->checkWithinOtherSite($data['student_id'], $data['start_year'], $controller->institutionSiteId);

						if (!$checkCurrentSite) {
							$controller->Message->alert('general.exists');
						} else if (!$checkOtherSite) {
							$controller->Message->alert('InstitutionSite.student.student_already_exists_in_other_site');
						} else {
							$duration = ClassRegistry::init('EducationProgramme')->getDurationBySiteProgramme($siteProgrammeId);

							$endDate = $startDate->add(new DateInterval('P' . $duration . 'Y'));
							$endYear = $endDate->format('Y');
							$data['end_date'] = $endDate->format('Y-m-d');
							$data['end_year'] = $endYear;
							
							// status set to 1(Current Student) by default on student add page, refer to PHPOE-870 
							$data['student_status_id'] = 1;
							
							if ($this->save($data)) {
								$controller->Message->alert('general.add.success');
								return $controller->redirect(array('action' => 'students'));
							}
						}
					} else {
						$controller->Message->alert('InstitutionSite.invalidDate');
					}
				} else {
					$controller->Message->alert('InstitutionSite.student.notExist');
				}
			}
		}
	}

	public function studentsView($controller, $params) {
		if (isset($params['pass'][0])) {
			$studentId = $params['pass'][0];
			$controller->Session->write('InstitutionSiteStudentId', $studentId);
			$data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
			$name = sprintf('%s %s %s', $data['Student']['first_name'], $data['Student']['middle_name'], $data['Student']['last_name']);
			$controller->Navigation->addCrumb($name);

			$details = $this->getDetails($studentId, $controller->institutionSiteId);
			$classes = ClassRegistry::init('InstitutionSiteClassStudent')->getListOfClassByStudent($studentId, $controller->institutionSiteId);
			//$results = ClassRegistry::init('AssessmentItemResult')->getResultsByStudent($studentId, $controller->institutionSiteId);
			//$results = ClassRegistry::init('AssessmentItemResult')->groupItemResults($results);
			$_view_details = $controller->AccessControl->check('Students', 'view');
			
			$controller->set(compact('_view_details', 'data', 'classes',/* 'results',*/ 'details'));
		} else {
			return $controller->redirect(array('action' => 'students'));
		}
	}

	public function studentsDelete($controller, $params) {
		if ($controller->Session->check('InstitutionSiteStudentId') && $controller->Session->check('InstitutionSiteId')) {
			$studentId = $controller->Session->read('InstitutionSiteStudentId');
			$InstitutionSiteId = $controller->Session->read('InstitutionSiteId');
			
			$SiteStudentRecordIds = $this->getRecordIdsByStudentIdAndSiteId($studentId, $InstitutionSiteId);
			if (!empty($SiteStudentRecordIds)) {
				$this->deleteAll(array('InstitutionSiteStudent.id' => $SiteStudentRecordIds), false);
			}

			$GradeStudentRecordIds = ClassRegistry::init('InstitutionSiteClassStudent')->getRecordIdsByStudentIdAndSiteId($studentId, $InstitutionSiteId);
			if (!empty($GradeStudentRecordIds)) {
				$controller->InstitutionSiteClassStudent->deleteAll(array('InstitutionSiteClassStudent.id' => $GradeStudentRecordIds), false);
			}

			ClassRegistry::init('AssessmentItemResult')->deleteAll(array(
				'AssessmentItemResult.student_id' => $studentId,
				'AssessmentItemResult.institution_site_id' => $InstitutionSiteId
			), false);

			ClassRegistry::init('StudentBehaviour')->deleteAll(array(
				'StudentBehaviour.student_id' => $studentId,
				'StudentBehaviour.institution_site_id' => $InstitutionSiteId
			), false);

			ClassRegistry::init('StudentAttendance')->deleteAll(array(
				'StudentAttendance.student_id' => $studentId,
				'StudentAttendance.institution_site_id' => $InstitutionSiteId
			), false);

			$ClassRegistry::init('StudentDetailsCustomValue')->deleteAll(array(
				'StudentDetailsCustomValue.student_id' => $studentId,
				'StudentDetailsCustomValue.institution_site_id' => $InstitutionSiteId
			), false);

			$controller->Message->alert('general.delete.success');
			return $controller->redirect(array('action' => 'students'));
		} else {
			return $controller->redirect(array('action' => 'students'));
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
				$this->saveMany($postData, array('validate'=>false));
				$controller->Message->alert('general.edit.success');
				return $controller->redirect(array('action' => 'studentsView', $studentId));
			}

			$data = $controller->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
			$name = sprintf('%s %s %s', $data['Student']['first_name'], $data['Student']['middle_name'], $data['Student']['last_name']);
			$controller->Navigation->addCrumb($name);
			$statusOptions = $this->StudentStatus->findList(true);

			$details = $this->getDetails($studentId, $controller->institutionSiteId);
			$classes = ClassRegistry::init('InstitutionSiteClassStudent')->getListOfClassByStudent($studentId, $controller->institutionSiteId);
			$itemResultObj = ClassRegistry::init('AssessmentItemResult');
			//$results = $itemResultObj->getResultsByStudent($studentId, $controller->institutionSiteId);
			//$results = $itemResultObj->groupItemResults($results);
			$_view_details = $controller->AccessControl->check('Students', 'view');
			
			$controller->set(compact('_view_details', 'data', 'classes', 'details', 'statusOptions'));
		} else {
			$controller->redirect(array('action' => 'students'));
		}
	}
	
	public function getAutoCompleteList($search,  $institutionSiteId = NULL, $limit = NULL) {
		$search = sprintf('%%%s%%', $search);
		
		$options['recursive'] = -1;
		$options['fields'] = array('DISTINCT Student.id', 'Student.*');
		$options['joins'] = array(array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('InstitutionSiteStudent.student_id = Student.id')
				),
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array('InstitutionSiteStudent.institution_site_programme_id = InstitutionSiteProgramme.id')
				));
		if(!empty($institutionSiteId)){
			$options['joins'][] = array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array(
						'InstitutionSiteProgramme.institution_site_id = InstitutionSite.id',
						'InstitutionSite.id' => $institutionSiteId
					)
				);
		}
		$options['conditions'] = array(
				'OR' => array(
					'Student.first_name LIKE' => $search,
					'Student.last_name LIKE' => $search,
					'Student.middle_name LIKE' => $search,
					'Student.preferred_name LIKE' => $search,
					'Student.identification_no LIKE' => $search
				)
			);
		$options['order'] = array('Student.first_name', 'Student.middle_name', 'Student.last_name', 'Student.preferred_name');
		if(!empty($limit)){
			$options['limit'] = $limit;
		}
		
		$list = $this->find('all', $options);
		/*$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT Student.id', 'Student.*'),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('InstitutionSiteStudent.student_id = Student.id')
				),
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array('InstitutionSiteStudent.institution_site_programme_id = InstitutionSiteProgramme.id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array(
						'InstitutionSiteProgramme.institution_site_id = InstitutionSite.id',
						'InstitutionSite.id' => $institutionSiteId
					)
				)
			),
			'conditions' => array(
				'OR' => array(
					'Student.first_name LIKE' => $search,
					'Student.last_name LIKE' => $search,
					'Student.middle_name LIKE' => $search,
					'Student.preferred_name LIKE' => $search,
					'Student.identification_no LIKE' => $search
				)
			),
			'order' => array('Student.first_name', 'Student.middle_name', 'Student.last_name', 'Student.preferred_name')
		));*/
	
		$data = array();
		foreach ($list as $obj) {
			$student = $obj['Student'];
			$data[] = array(
				'label' => sprintf('%s - %s %s %s %s', $student['identification_no'], $student['first_name'], $student['middle_name'], $student['last_name'], $student['preferred_name']),
				'value' => $student['id']
			);
		}
		return $data;
	}
	
	//Ajax method
	public function studentsAjaxFind($controller, $params) {
		if ($controller->request->is('ajax')) {
			$this->render = false;
			$search = $params->query['term'];
			
			$data = $this->studentsSearch($search);

			return json_encode($data);
		}
	}
	
	public function studentsAjaxStartDate($controller, $params) {
		if ($controller->request->is('ajax')) {
			$this->render = false;
			$returnData = array();
			$yearData = ClassRegistry::init('SchoolYear')->findById($controller->request->query['yearId'], array('start_date', 'end_date'));
			if(!empty($yearData)){
				$returnData['dateData'] = array('startDate'=>date('d-m-Y',  strtotime($yearData['SchoolYear']['start_date']) ), 'endDate' => date('d-m-Y',  strtotime($yearData['SchoolYear']['end_date'])));
			}
			
			return json_encode($returnData);
		}
		
	}
	
	public function reportsGetHeader($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->getCSVHeader($this->reportMapping[$index]['fields']);
	}

	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$options = array();
			$options['recursive'] = -1;
			$options['fields'] = $this->getCSVFields($this->reportMapping[$index]['fields']);
			$options['order'] = array('Student.first_name');
			$options['conditions'] = array();
			$options['group'] = array('Student.id', 'InstitutionSiteProgramme.id');

			$options['joins'] = array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('InstitutionSiteStudent.student_id = Student.id')
				),
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteStudent.institution_site_programme_id = InstitutionSiteProgramme.id',
						'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('InstitutionSiteProgramme.education_programme_id = EducationProgramme.id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array('InstitutionSite.id = InstitutionSiteProgramme.institution_site_id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite2',
					'type' => 'inner',
					'conditions' => array('InstitutionSite.id = InstitutionSite2.id')
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite3',
					'type' => 'inner',
					'conditions' => array('InstitutionSite.id = InstitutionSite3.id')
				),
				array(
					'table' => 'institution_site_statuses',
					'alias' => 'InstitutionSiteStatus',
					'conditions' => array('InstitutionSiteStatus.id = InstitutionSite.institution_site_status_id')
				),
				array(
					'table' => 'institution_site_types',
					'alias' => 'InstitutionSiteType',
					'conditions' => array('InstitutionSiteType.id = InstitutionSite.institution_site_type_id')
				),
				array(
					'table' => 'institution_site_ownership',
					'alias' => 'InstitutionSiteOwnership',
					'conditions' => array('InstitutionSiteOwnership.id = InstitutionSite.institution_site_ownership_id')
				),
				array(
					'table' => 'areas',
					'alias' => 'Area',
					'conditions' => array('InstitutionSite.area_id = Area.id')
				),
				array(
					'table' => 'area_educations',
					'alias' => 'AreaEducation',
					'type' => 'left',
					'conditions' => array('InstitutionSite.area_education_id = AreaEducation.id')
				),
				array(
					'table' => 'student_nationalities',
					'alias' => 'StudentNationality',
					'type' => 'left',
					'conditions' => array('InstitutionSiteStudent.student_id = StudentNationality.student_id')
				),
				array(
					'table' => 'student_contacts',
					'alias' => 'StudentContact',
					'type' => 'left',
					'conditions' => array('InstitutionSiteStudent.student_id = StudentContact.student_id')
				),
				array(
					'table' => 'student_identities',
					'alias' => 'StudentIdentity',
					'type' => 'left',
					'conditions' => array('InstitutionSiteStudent.student_id = StudentIdentity.student_id')
				),
				array(
					'table' => 'countries',
					'alias' => 'Country',
					'type' => 'left',
					'conditions' => array('Country.id = StudentNationality.country_id')
				),
				array(
					'table' => 'contact_types',
					'alias' => 'ContactType',
					'type' => 'left',
					'conditions' => array('ContactType.id = StudentContact.contact_type_id')
				),
				array(
					'table' => 'identity_types',
					'alias' => 'IdentityType',
					'type' => 'left',
					'conditions' => array('IdentityType.id = StudentIdentity.identity_type_id')
				),
				array(
					'table' => 'student_statuses',
					'alias' => 'StudentStatus',
					'type' => 'left',
					'conditions' => array('InstitutionSiteStudent.student_status_id = StudentStatus.id')
				)
			);

			$data = $this->find('all', $options);

			$siteCustomFieldModel = ClassRegistry::init('InstitutionSiteCustomField');

			$reportFields = $this->reportMapping[$index]['fields'];

			$studentCustomFieldModel = ClassRegistry::init('StudentCustomField');

			$studentCustomFields = $studentCustomFieldModel->find('all', array(
				'recursive' => -1,
				'fields' => array('StudentCustomField.name as FieldName'),
				'conditions' => array('StudentCustomField.visible' => 1, 'StudentCustomField.type != 1'),
				'order' => array('StudentCustomField.order'),
					)
			);

			foreach ($studentCustomFields as $val) {
				if (!empty($val['StudentCustomField']['FieldName'])) {
					$reportFields['StudentCustomField'][$val['StudentCustomField']['FieldName']] = '';
				}
			}


			$this->reportMapping[$index]['fields'] = $reportFields;

			$newData = array();

			$studentModel = ClassRegistry::init('Student');

			$students = $studentModel->find('list', array(
				'recursive' => -1,
				'fields' => array('Student.id'),
				'joins' => array(
					array(
						'table' => 'institution_site_students',
						'alias' => 'InstitutionSiteStudent',
						'conditions' => array('InstitutionSiteStudent.student_id = Student.id')
					),
					array(
						'table' => 'institution_site_programmes',
						'alias' => 'InstitutionSiteProgramme',
						'conditions' => array(
							'InstitutionSiteStudent.institution_site_programme_id = InstitutionSiteProgramme.id',
						)
					)
				),
				'conditions' => array('InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId),
				'order' => array('Student.first_name')
					)
			);

			$r = 0;
			foreach ($data AS $row) {
				$studentCustomFields = $studentCustomFieldModel->find('all', array(
					'recursive' => -1,
					'fields' => array('StudentCustomField.name as FieldName', 'IFNULL(GROUP_CONCAT(StudentCustomFieldOption.value),StudentCustomValue.value) as FieldValue'),
					'joins' => array(
						array(
							'table' => 'student_custom_values',
							'alias' => 'StudentCustomValue',
							'type' => 'left',
							'conditions' => array(
								'StudentCustomField.id = StudentCustomValue.student_custom_field_id',
								'StudentCustomValue.student_id' => array_slice($students, $r, 1)
							)
						),
						array(
							'table' => 'student_custom_field_options',
							'alias' => 'StudentCustomFieldOption',
							'type' => 'left',
							'conditions' => array(
								'StudentCustomField.id = StudentCustomFieldOption.student_custom_field_id',
								'StudentCustomField.type' => array(3, 4),
								'StudentCustomValue.value = StudentCustomFieldOption.id'
							)
						),
					),
					'conditions' => array('StudentCustomField.visible' => 1, 'StudentCustomField.type !=1'),
					'order' => array('StudentCustomField.order'),
					'group' => array('StudentCustomField.id')
						)
				);

				foreach ($studentCustomFields as $val) {
					if (!empty($val['StudentCustomField']['FieldName'])) {
						$row['StudentCustomField'][$val['StudentCustomField']['FieldName']] = $val[0]['FieldValue'];
					}
				}

				$sortRow = array();
				foreach ($this->reportMapping[$index]['fields'] as $key => $value) {
					if (isset($row[$key])) {
						$sortRow[$key] = $row[$key];
					} else {
						$sortRow[0] = $row[0];
					}
				}

				//pr($sortRow);

				$newData[] = $sortRow;
				$r++;
			}

			return $newData;
		}
	}
	
	public function reportsGetFileName($args){
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->reportMapping[$index]['fileName'];
	}

}
