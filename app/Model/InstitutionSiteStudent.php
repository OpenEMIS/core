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
App::uses('Sanitize', 'Utility');
App::uses('AppModel', 'Model');

class InstitutionSiteStudent extends AppModel {
	public $actsAs = array(
		'ControllerAction2',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		),
		'DatePicker' => array('start_date', 'end_date'),
		'Year' => array('start_date' => 'start_year', 'end_date' => 'end_year')
	);
	
	public $belongsTo = array(
		'Students.Student',
		'StudentStatus' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'student_status_id'
		),
		'InstitutionSiteProgramme',
		'EducationProgramme',
		'InstitutionSite'
	);
	
	public $validate = array(
		'search' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a OpenEMIS ID or name.'
			)
		),
		'institution_site_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Institution.'
			)
		),
		'education_programme_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Programme.'
			)
		)
	);
	
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
				),
				'InstitutionSiteCustomField' => array(
				)
			),
			'fileName' => 'Report_Student_List'
		)
	);
	
	public function beforeAction() {
		parent::beforeAction();
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$this->fields['institution'] = array(
			'type' => 'disabled',
			'value' => $this->Session->read('InstitutionSite.data.InstitutionSite.name'),
			'visible' => true
		);
		$this->setFieldOrder('institution', 1);
		
		$SchoolYear = ClassRegistry::init('SchoolYear');
		
		$yearOptions = $SchoolYear->find('list', array('order' => array('order')));
		$this->fields['year'] = array(
			'type' => 'select',
			'options' => $yearOptions,
			'visible' => true,
			'attr' => array('onchange' => "$('#reload').click()")
		);
		$this->setFieldOrder('year', 2);
		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['student_id']['type'] = 'hidden';
		$this->fields['student_id']['attr'] = array('class' => 'student_id');
		$this->fields['student_status_id']['type'] = 'select';
		$this->fields['student_status_id']['options'] = $this->StudentStatus->getList();
		$this->fields['institution_site_id']['type'] = 'hidden';
		$this->fields['institution_site_id']['value'] = $institutionSiteId;
		$this->fields['education_programme_id']['type'] = 'select';
		$this->fields['institution_site_programme_id']['visible'] = false; // to be remove after column dropped
		
		if ($this->action == 'add') {
			if ($this->request->is('get')) {
				$yearId = key($yearOptions);
				$yearObj = $SchoolYear->findById($yearId);
				$startDate = $yearObj['SchoolYear']['start_date'];
				$endDate = $yearObj['SchoolYear']['end_date'];
				$date = new DateTime($startDate);
				$date->add(new DateInterval('P1D')); // plus 1 day
				
				$this->fields['start_date']['attr'] = array(
					'startDate' => $startDate,
					'endDate' => $endDate,
					'data-date' => $startDate
				);
				$this->fields['end_date']['attr'] = array(
					'startDate' => $date->format('d-m-Y'),
					'data-date' => $date->format('d-m-Y')
				);
				$programmeOptions = $this->EducationProgramme->getProgrammeOptionsByInstitution($institutionSiteId, $yearId, true);
				$this->fields['education_programme_id']['options'] = $programmeOptions;
			}
		}
	}
	
	public function index() {
		$this->Navigation->addCrumb('List of Students');
		$params = $this->controller->params;
		$page = isset($params->named['page']) ? $params->named['page'] : 1;

		$selectedYear = "";
		$selectedProgramme = "";
		$searchField = "";
		$orderBy = 'Student.first_name';
		$order = 'asc';
		$yearOptions = ClassRegistry::init('SchoolYear')->getYearListValues('start_year');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$programmeOptions = $this->InstitutionSiteProgramme->getProgrammeOptions($institutionSiteId);
		$statusOptions = $this->StudentStatus->getList();
		
		$prefix = 'InstitutionSiteStudent.Search.%s';
		if ($this->request->is('post')) {
			$searchField = Sanitize::escape(trim($this->request->data[$this->alias]['SearchField']));
			$selectedYear = $this->request->data[$this->alias]['school_year'];
			$selectedProgramme = $this->request->data[$this->alias]['education_programme_id'];
			$selectedStatus = $this->request->data[$this->alias]['student_status_id'];
			$orderBy = $this->request->data[$this->alias]['orderBy'];
			$order = $this->request->data[$this->alias]['order'];

			$this->Session->write(sprintf($prefix, 'SearchField'), $searchField);
			$this->Session->write(sprintf($prefix, 'SchoolYear'), $selectedYear);
			$this->Session->write(sprintf($prefix, 'EducationProgrammeId'), $selectedProgramme);
			$this->Session->write(sprintf($prefix, 'StudentStatusId'), $selectedStatus);
			$this->Session->write(sprintf($prefix, 'order'), $order);
			$this->Session->write(sprintf($prefix, 'orderBy'), $orderBy);
		} else {
			$searchField = $this->Session->read(sprintf($prefix, 'SearchField'));
			$selectedYear = $this->Session->read(sprintf($prefix, 'SchoolYear'));
			$selectedProgramme = $this->Session->read(sprintf($prefix, 'EducationProgrammeId'));
			$selectedStatus = $this->Session->read(sprintf($prefix, 'StudentStatusId'));

			if ($this->Session->check(sprintf($prefix, 'orderBy'))) {
				$orderBy = $this->Session->read(sprintf($prefix, 'orderBy'));
			}
			if ($this->Session->check(sprintf($prefix, 'order'))) {
				$order = $this->Session->read(sprintf($prefix, 'order'));
			}
		}
		$conditions = array('InstitutionSiteStudent.institution_site_id' => $institutionSiteId);
		
		if (!empty($searchField)) {
			$search = '%' . $searchField . '%';
			$conditions['OR'] = array(
				'Student.identification_no LIKE' => $search,
				'Student.first_name LIKE' => $search,
				'Student.middle_name LIKE' => $search,
				'Student.last_name LIKE' => $search,
				'Student.preferred_name LIKE' => $search
			);
		}
		
		if (!empty($selectedYear)) {
			// if the year falls between the start and end date
			$conditions['InstitutionSiteStudent.start_year <='] = $selectedYear;
			$conditions['InstitutionSiteStudent.end_year >='] = $selectedYear;
		}

		if (!empty($selectedProgramme)) {
			$conditions['EducationProgramme.id'] = $selectedProgramme;
		}

		if (!empty($selectedStatus)) {
			$conditions['InstitutionSiteStudent.student_status_id'] = $selectedStatus;
		}
		
		$this->controller->paginate = array('limit' => 15, 'maxLimit' => 100, 'order' => $orderBy. ' ' . $order);
		$data = $this->controller->paginate($this->alias, $conditions);

		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		$this->setVar(compact('searchField', 'page', 'orderBy', 'order', 'yearOptions', 'programmeOptions', 'selectedYear', 'selectedProgramme', 'data', 'statusOptions'));
	}
	
	public function add() {
		$this->Navigation->addCrumb('Add existing Student');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$SchoolYear = ClassRegistry::init('SchoolYear');
		
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$yearId = $data[$this->alias]['year'];
			
			$yearObj = $SchoolYear->findById($yearId);
			$startDate = $yearObj['SchoolYear']['start_date'];
			$endDate = $yearObj['SchoolYear']['end_date'];
			$date = new DateTime($startDate);
			$date->add(new DateInterval('P1D')); // plus 1 day
			
			$this->fields['start_date']['attr'] = array(
				'startDate' => $startDate,
				'endDate' => $endDate,
				'data-date' => $startDate
			);
			$this->fields['end_date']['attr'] = array(
				'startDate' => $date->format('d-m-Y'),
				'data-date' => $date->format('d-m-Y')
			);
			$programmeOptions = $this->EducationProgramme->getProgrammeOptionsByInstitution($institutionSiteId, $yearId);
			$this->fields['education_programme_id']['options'] = $programmeOptions;
			
			$submit = $this->request->data['submit'];
			if ($submit == 'Save') {
				$studentId = $data[$this->alias]['student_id'];
				$data[$this->alias]['institution_site_programme_id'] = 0;
				
				$this->set($data[$this->alias]);
				if ($this->validates()) {
					$count = $this->find('count', array(
						'conditions' => array(
							$this->alias . '.institution_site_id' => $institutionSiteId, 
							$this->alias . '.student_id' => $studentId,
							$this->alias . '.education_programme_id' => $data[$this->alias]['education_programme_id']
						)
					));
					
					if ($count > 0) {
						$this->Message->alert('general.exists');
					} else {
						$programmeId = $this->EducationProgramme->InstitutionSiteProgramme->field('id', array(
							'institution_site_id' => $institutionSiteId,
							'education_programme_id' => $data[$this->alias]['education_programme_id'],
							'school_year_id' => $yearId
						));
						$data[$this->alias]['institution_site_programme_id'] = $programmeId;
						if ($this->save($data)) {
							$this->Message->alert('general.add.success');
							return $this->redirect(array('action' => get_class($this)));
						} else {
							$this->Message->alert('general.add.failed');
						}
					}
				} else {
					$this->Message->alert('general.add.failed');
				}
			}
		}
	}
	
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
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$data = $this->find('all', array(
			'fields' => array(
				'Student.id', 'Student.identification_no', 'Student.first_name', 'Student.middle_name', 
				'Student.last_name', 'EducationProgramme.name', 'StudentStatus.name'
			),
			'conditions' => $conditions,
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'group' => array('Student.id', 'EducationProgramme.id'),
			'order' => $order
		));
		return $data;
	}
	 
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$count = $this->find('count', array('conditions' => $conditions));
		return $count;
	}
	
	/*
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
	*/
	
	public function autocomplete() {
		if ($this->request->is('ajax')) {
			$this->render = false;
			$params = $this->controller->params;
			$search = $params->query['term'];
			$list = $this->Student->autocomplete($search);
			
			$data = array();
			foreach ($list as $obj) {
				$studentInfo = $obj['Student'];
				$data[] = array(
					'label' => sprintf('%s - %s %s', $studentInfo['identification_no'], $studentInfo['first_name'], $studentInfo['last_name']),
					'value' => array('student_id' => $studentInfo['id']) 
				);
			}
			return json_encode($data);
		}
	}
	
	/*
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
			
			$controller->set(compact('_view_details', 'data', 'classes', 'details'));
		} else {
			return $controller->redirect(array('action' => 'students'));
		}
	}

	public function studentsDelete($controller, $params) {
		if ($controller->Session->check('InstitutionSiteStudentId') && $controller->Session->check('InstitutionSite.id')) {
			$studentId = $controller->Session->read('InstitutionSiteStudentId');
			$InstitutionSiteId = $controller->Session->read('InstitutionSite.id');
			
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
	*/
	
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

			$institutionSiteCustomFields = $siteCustomFieldModel->find('all', array(
				'recursive' => -1,
				'fields' => array('InstitutionSiteCustomField.name as FieldName'),
				'joins' => array(
					array(
						'table' => 'institution_sites',
						'alias' => 'InstitutionSite',
						'conditions' => array(
							'OR' => array(
								'InstitutionSiteCustomField.institution_site_type_id = InstitutionSite.institution_site_type_id',
								'InstitutionSiteCustomField.institution_site_type_id' => 0
							)
						)
					)
				),
				'conditions' => array(
					'InstitutionSiteCustomField.visible' => 1,
					'InstitutionSiteCustomField.type != 1',
					'InstitutionSite.id' => $institutionSiteId
				),
				'order' => array('InstitutionSiteCustomField.order')
					)
			);


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

			foreach ($institutionSiteCustomFields as $val) {
				if (!empty($val['InstitutionSiteCustomField']['FieldName'])) {
					$reportFields['InstitutionSiteCustomField'][$val['InstitutionSiteCustomField']['FieldName']] = '';
				}
			}

			$this->reportMapping[$index]['fields'] = $reportFields;

			$newData = array();

			$institutionSiteCustomFields2 = $siteCustomFieldModel->find('all', array(
				'recursive' => -1,
				'fields' => array('InstitutionSiteCustomField.id', 'InstitutionSiteCustomField.name as FieldName', 'IFNULL(GROUP_CONCAT(InstitutionSiteCustomFieldOption.value),InstitutionSiteCustomValue.value) as FieldValue'),
				'joins' => array(
					array(
						'table' => 'institution_sites',
						'alias' => 'InstitutionSite',
						'conditions' => array(
							'InstitutionSite.id' => $institutionSiteId,
							'OR' => array(
								'InstitutionSiteCustomField.institution_site_type_id = InstitutionSite.institution_site_type_id',
								'InstitutionSiteCustomField.institution_site_type_id' => 0
							)
						)
					),
					array(
						'table' => 'institution_site_custom_values',
						'alias' => 'InstitutionSiteCustomValue',
						'type' => 'left',
						'conditions' => array(
							'InstitutionSiteCustomField.id = InstitutionSiteCustomValue.institution_site_custom_field_id',
							'InstitutionSiteCustomValue.institution_site_id = InstitutionSite.id'
						)
					),
					array(
						'table' => 'institution_site_custom_field_options',
						'alias' => 'InstitutionSiteCustomFieldOption',
						'type' => 'left',
						'conditions' => array(
							'InstitutionSiteCustomField.id = InstitutionSiteCustomFieldOption.institution_site_custom_field_id',
							'InstitutionSiteCustomField.type' => array(3, 4),
							'InstitutionSiteCustomValue.value = InstitutionSiteCustomFieldOption.id'
						)
					),
				),
				'conditions' => array(
					'InstitutionSiteCustomField.visible' => 1,
					'InstitutionSiteCustomField.type !=1',
				),
				'order' => array('InstitutionSiteCustomField.order'),
				'group' => array('InstitutionSiteCustomField.id')
					)
			);
			
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
								'StudentCustomValue.student_id' => array_shift(array_slice($students, $r, 1))
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

				foreach ($institutionSiteCustomFields2 as $val) {
					if (!empty($val['InstitutionSiteCustomField']['FieldName'])) {
						$row['InstitutionSiteCustomField'][$val['InstitutionSiteCustomField']['FieldName']] = $val[0]['FieldValue'];
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
