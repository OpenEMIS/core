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
		'Search',
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
		'student_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a student.'
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
					'third_name' => 'Third Name',
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
	
	public function beforeAction() {
		parent::beforeAction();
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$this->fields['institution'] = array(
			'type' => 'disabled',
			'value' => $this->Session->read('InstitutionSite.data.InstitutionSite.name'),
			'visible' => true
		);
		$this->setFieldOrder('institution', 0);
		
		$SchoolYear = ClassRegistry::init('SchoolYear');
		
		$yearOptions = $SchoolYear->find('list', array('order' => array('order')));
		$this->fields['year'] = array(
			'type' => 'select',
			'options' => $yearOptions,
			'visible' => true,
			'attr' => array('onchange' => "$('#reload').click()")
		);
		$this->setFieldOrder('year', 1);
		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['student_id']['type'] = 'hidden';
		$this->fields['student_id']['attr'] = array('autocomplete' => 'student_id');
		$this->fields['student_status_id']['type'] = 'select';
		$this->fields['student_status_id']['options'] = $this->StudentStatus->getList();
		$this->fields['institution_site_id']['type'] = 'hidden';
		$this->fields['institution_site_id']['value'] = $institutionSiteId;
		$this->fields['education_programme_id']['type'] = 'select';
		$this->fields['institution_site_programme_id']['visible'] = false;
		$this->fields['student_status_id']['visible'] = false;
		
		$this->setFieldOrder('education_programme_id', 2);
		$this->setFieldOrder('start_date', 3);
		$this->setFieldOrder('end_date', 4);
		
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
		
				$programmeOptions = $this->InstitutionSiteProgramme->getSiteProgrammeOptions($institutionSiteId, $yearId, true);
				$this->fields['education_programme_id']['options'] = $programmeOptions;
			}
		}
	}
	
	public function index() {
		$this->Navigation->addCrumb('List of Students');
		$params = $this->controller->params;

		$prefix = 'InstitutionSiteStudent.search.';
		$yearOptions = ClassRegistry::init('SchoolYear')->getYearListValues('start_year');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$programmeOptions = $this->InstitutionSiteProgramme->getProgrammeOptions($institutionSiteId);
		$statusOptions = $this->StudentStatus->getList();
		$conditions = array();

		if ($this->Session->check($prefix . 'conditions')) {
			$conditions = $this->Session->read($prefix . 'conditions');
		}
		$conditions['InstitutionSiteStudent.institution_site_id'] = $institutionSiteId;

		if ($this->request->is('post')) {
			$searchField = Sanitize::escape(trim($this->request->data[$this->alias]['search']));
			$selectedYear = $this->request->data[$this->alias]['school_year_id'];
			$selectedProgramme = $this->request->data[$this->alias]['education_programme_id'];
			$selectedStatus = $this->request->data[$this->alias]['student_status_id'];

			if (strlen($selectedYear) != '') {
				// if the year falls between the start and end date
				$conditions['InstitutionSiteStudent.start_year <='] = $selectedYear;
				$conditions['InstitutionSiteStudent.end_year >='] = $selectedYear;
			} else {
				unset($conditions['InstitutionSiteStudent.start_year <=']);
				unset($conditions['InstitutionSiteStudent.end_year >=']);
			}

			if (strlen($selectedProgramme) != '') {
				$conditions['EducationProgramme.id'] = $selectedProgramme;
			} else {
				unset($conditions['EducationProgramme.id']);
			}

			if (strlen($selectedStatus) != '') {
				$conditions['InstitutionSiteStudent.student_status_id'] = $selectedStatus;
			} else {
				unset($conditions['InstitutionSiteStudent.student_status_id']);
			}
		} else {
			if (array_key_exists('InstitutionSiteStudent.start_year <=', $conditions)) {
				$this->request->data[$this->alias]['school_year_id'] = $conditions['InstitutionSiteStudent.start_year <='];
			}
			if (array_key_exists('EducationProgramme.id', $conditions)) {
				$this->request->data[$this->alias]['education_programme_id'] = $conditions['EducationProgramme.id'];
			}
			if (array_key_exists('InstitutionSiteStudent.student_status_id', $conditions)) {
				$this->request->data[$this->alias]['student_status_id'] = $conditions['InstitutionSiteStudent.student_status_id'];
			}
		}
		
		if (!empty($searchField)) {
			$search = '%' . $searchField . '%';
			$conditions['OR'] = array(
				'Student.identification_no LIKE' => $search,
				'Student.first_name LIKE' => $search,
				'Student.middle_name LIKE' => $search,
				'Student.third_name LIKE' => $search,
				'Student.last_name LIKE' => $search,
				'Student.preferred_name LIKE' => $search
			);
		} else {
			unset($conditions['OR']);
		}
		
		if ($this->Session->check('Student.AdvancedSearch')) {
			$params = $this->Session->read('Student.AdvancedSearch');
			$conditions = $this->getAdvancedSearchConditionsWithSite($institutionSiteId, $params);
		}

		$this->Session->write($prefix . 'conditions', $conditions);
		$data = $this->controller->Search->search($this, $conditions);

		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		$this->setVar(compact('data', 'yearOptions', 'programmeOptions', 'statusOptions'));
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
			$programmeOptions = $this->InstitutionSiteProgramme->getSiteProgrammeOptions($institutionSiteId, $yearId, true);
			$this->fields['education_programme_id']['options'] = $programmeOptions;
			
			$submit = $this->request->data['submit'];
			if ($submit == __('Save')) {
				$studentId = $data[$this->alias]['student_id'];
				$data[$this->alias]['institution_site_programme_id'] = 0;
				
				$this->set($data[$this->alias]);
				
				if(isset($data['new'])){
					$this->validator()->remove('search');
					$this->validator()->remove('student_id');
				}
				
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
						
						$statusOptions = $this->StudentStatus->getList();
						$student_status_id = key($statusOptions);
						foreach($statusOptions AS $id => $status){
							if($status == 'Current Student'){
								$student_status_id = $id;
								break;
							}
						}
						$data[$this->alias]['student_status_id'] = $student_status_id;
						
						if(isset($data['new'])){
							$this->Session->write('InstitutionSiteStudent.addNew', $data[$this->alias]);
							return $this->redirect(array('controller' => 'Students', 'action' => 'add'));
						}else{
							if ($this->save($data)) {
								$this->Message->alert('general.add.success');
								return $this->redirect(array('action' => get_class($this)));
							} else {
								$this->Message->alert('general.add.failed');
							}
						}
					}
				} else {
					$this->Message->alert('general.add.failed');
				}
			}
		}
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$data = $this->find('all', array(
			'fields' => array(
				'Student.id', 'Student.identification_no', 'Student.first_name', 'Student.middle_name', 
				'Student.third_name', 'Student.last_name', 'EducationProgramme.name', 'StudentStatus.name'
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
	
	public function autocomplete() {
		if ($this->request->is('ajax')) {
			$this->render = false;
			$params = $this->controller->params;
			$search = $params->query['term'];
			$list = $this->Student->autocomplete($search);
			
			$data = array();
			foreach ($list as $obj) {
				$info = $obj['Student'];
				$data[] = array(
					'label' => sprintf('%s - %s %s', $info['identification_no'], $info['first_name'], $info['last_name']),
					'value' => array('student_id' => $info['id']) 
				);
			}
			return json_encode($data);
		}
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
	
	// used by InstitutionSiteStudentAbsence
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
					'Student.third_name LIKE' => $search,
					'Student.preferred_name LIKE' => $search,
					'Student.identification_no LIKE' => $search
				)
			);
		$options['order'] = array('Student.first_name', 'Student.middle_name', 'Student.last_name', 'Student.third_name', 'Student.preferred_name');
		if(!empty($limit)){
			$options['limit'] = $limit;
		}
		
		$list = $this->find('all', $options);
	
		$data = array();
		foreach ($list as $obj) {
			$student = $obj['Student'];
			$data[] = array(
				'label' => sprintf('%s - %s %s %s %s', $student['identification_no'], $student['first_name'], $student['middle_name'], $student['third_name'], $student['last_name'], $student['preferred_name']),
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
					'table' => 'field_option_values',
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
