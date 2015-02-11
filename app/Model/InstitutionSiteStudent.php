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
		'Excel' => array('header' => array('Student' => array('identification_no', 'first_name', 'middle_name', 'third_name', 'last_name'))),
		'Search',
		'ControllerAction2',
		'DatePicker' => array('start_date', 'end_date'),
		'Year' => array('start_date' => 'start_year', 'end_date' => 'end_year')
	);
	
	public $belongsTo = array(
		'Students.Student',
		'Students.StudentStatus',
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

	/* Excel Behaviour */
	public function excelGetConditions() {
		$id = CakeSession::read('InstitutionSite.id');
		$conditions = array('InstitutionSite.id' => $id);
		return $conditions;
	}
	/* End Excel Behaviour */
	
	public function beforeAction() {
		parent::beforeAction();
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$this->fields['institution'] = array(
			'type' => 'disabled',
			'value' => $this->Session->read('InstitutionSite.data.InstitutionSite.name'),
			'visible' => true
		);
		$this->setFieldOrder('institution', 0);
		
		$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		
		$academicPeriodOptions = $AcademicPeriod->getAcademicPeriodList();
		$this->fields['year'] = array(
			'type' => 'select',
			'options' => $academicPeriodOptions,
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
				$academicPeriodId = key($academicPeriodOptions);
				$academicPeriodObj = $AcademicPeriod->findById($academicPeriodId);
				$startDate = $academicPeriodObj['AcademicPeriod']['start_date'];
				$endDate = $academicPeriodObj['AcademicPeriod']['end_date'];
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
		
				$programmeOptions = $this->InstitutionSiteProgramme->getSiteProgrammeOptions($institutionSiteId, $academicPeriodId, true);
				$this->fields['education_programme_id']['options'] = $programmeOptions;
			}
		}
	}
	
	public function index() {
		$this->Navigation->addCrumb('List of Students');
		$params = $this->controller->params;

		$prefix = 'InstitutionSiteStudent.search.';

		$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		$yearOptions = $AcademicPeriod->getAcademicPeriodListValues('id');

		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$programmeOptions = $this->InstitutionSiteProgramme->getProgrammeOptions($institutionSiteId);
		$statusOptions = $this->StudentStatus->getList(array('listOnly' => true));
		$conditions = array();

		if ($this->Session->check($prefix . 'conditions')) {
			$conditions = $this->Session->read($prefix . 'conditions');
		}
		$conditions['InstitutionSiteStudent.institution_site_id'] = $institutionSiteId;

		$IdentityType = ClassRegistry::init('IdentityType');
		$defaultIdentity = $IdentityType->find('first', array(
			'contain' => array('FieldOption'),
			'conditions' => array('FieldOption.code' => $IdentityType->alias),
			'order' => array('IdentityType.default DESC')
		));
		$conditions['defaultIdentity'] = $defaultIdentity['IdentityType']['id'];
		$conditions['Student.id <>'] = '';

		if ($this->request->is('post')) {
			$searchField = Sanitize::escape(trim($this->request->data[$this->alias]['search']));
			$selectedAcademicPeriod = $this->request->data[$this->alias]['academic_period_id'];
			$selectedProgramme = $this->request->data[$this->alias]['education_programme_id'];
			$selectedStatus = $this->request->data[$this->alias]['student_status_id'];

			if (strlen($selectedAcademicPeriod) != '') {
				// if the year falls between the start and end date
				$yearObj = $AcademicPeriod->findById($selectedAcademicPeriod);
				$startDate = date('Y-m-d', strtotime($yearObj['AcademicPeriod']['start_date']));
				$endDate = date('Y-m-d', strtotime($yearObj['AcademicPeriod']['end_date']));
				$conditions['InstitutionSiteStudent.start_date <='] = $endDate;
				$conditions['InstitutionSiteStudent.end_date >='] = $startDate;
				$this->Session->write($prefix . 'yearId', $selectedAcademicPeriod);
			} else {
				unset($conditions['InstitutionSiteStudent.start_date <=']);
				unset($conditions['InstitutionSiteStudent.end_date >=']);
				$this->Session->delete($prefix . 'yearId');
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
			if ($this->Session->check($prefix . 'yearId')) {
				$this->request->data[$this->alias]['academic_period_id'] = $this->Session->read($prefix . 'yearId');
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
		$defaultIdentity = $defaultIdentity['IdentityType'];
		$this->setVar(compact('data', 'yearOptions', 'programmeOptions', 'statusOptions', 'defaultIdentity'));
	}
	
	public function add() {
		$this->Navigation->addCrumb('Add existing Student');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$academicPeriodId = $data[$this->alias]['year'];
			
			$academicPeriodObj = $AcademicPeriod->findById($academicPeriodId);
			$startDate = $academicPeriodObj['AcademicPeriod']['start_date'];
			$endDate = $academicPeriodObj['AcademicPeriod']['end_date'];
			$date = new DateTime($startDate);
			$date->add(new DateInterval('P1D')); // plus 1 day
			
			$this->fields['start_date']['attr'] = array(
				'startDate' => (isset($data['InstitutionSiteStudent']['start_date']) ? $data['InstitutionSiteStudent']['start_date'] : $startDate),
				'endDate' => $endDate,
				'data-date' => (isset($data['InstitutionSiteStudent']['start_date']) ? $data['InstitutionSiteStudent']['start_date'] : $startDate)
			);
			$this->fields['end_date']['attr'] = array(
				'startDate' => (isset($data['InstitutionSiteStudent']['end_date']) ? $data['InstitutionSiteStudent']['end_date'] : $date->format('d-m-Y')),
				'data-date' => (isset($data['InstitutionSiteStudent']['end_date']) ? $data['InstitutionSiteStudent']['end_date'] : $date->format('d-m-Y'))
			);
			$programmeOptions = $this->InstitutionSiteProgramme->getSiteProgrammeOptions($institutionSiteId, $academicPeriodId, true);
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
							'academic_period_id' => $academicPeriodId
						));
						$data[$this->alias]['institution_site_programme_id'] = $programmeId;
						
						$studentStatusId = $this->StudentStatus->getDefaultValue();
						$data[$this->alias]['student_status_id'] = $studentStatusId;
						
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
		$identityConditions[] = 'StudentIdentity.student_id = InstitutionSiteStudent.student_id';
		if(isset($conditions['defaultIdentity'])&&strlen($conditions['defaultIdentity']>0)) {
			$identityConditions[] = 'StudentIdentity.identity_type_id = '.$conditions['defaultIdentity'];
		}
		$joins[] = array(
			'table' => 'student_identities',
			'alias' => 'StudentIdentity',
			'type' => 'LEFT',
			'conditions' => $identityConditions,
		);

		/*
		*	Default identity is a required condition for extracting row on StudentIdentity only.
		*	Must be unset to avoid mysql unknown column error when querying InstitutionSiteStudent table.
		*	
		*	Any other parameter that can be used other than $conditions?
		*/
		unset($conditions['defaultIdentity']);
				
		/*
		*	Sorting would not work on National ID column.
		*	The script below is to enforce sorting on that column.
		*/
		if (isset($extra['sort']) && isset($extra['direction'])) {
			$order = array($extra['sort'] => $extra['direction']);
		}
		/**/

		$data = $this->find('all', array(
			'fields' => array(
				'Student.id', 'Student.identification_no', 'Student.first_name', 'Student.middle_name', 
				'Student.third_name', 'Student.last_name', 'EducationProgramme.name', 'StudentStatus.name',
				'StudentIdentity.number'
			),
			'joins' => $joins,
			'conditions' => $conditions,
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'group' => array('Student.id', 'EducationProgramme.id'),
			'order' => $order
		));
		$data = $this->attachSectionInfo($data);
		
		return $data;
	}
	 
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		/*
		*	Default identity is a required condition for extracting row on StudentIdentity only.
		*	Must be unset to avoid mysql unknown column error when querying InstitutionSiteStudent table.
		*/
		unset($conditions['defaultIdentity']);
		$count = $this->find('count', array('conditions' => $conditions, 'group' => array('Student.id'),));
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
					'label' => ModelHelper::getName($info, array('openEmisId'=>true)),
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
					'Student.middle_name LIKE' => $search,
					'Student.third_name LIKE' => $search,
					'Student.last_name LIKE' => $search,
					'Student.preferred_name LIKE' => $search,
					'Student.identification_no LIKE' => $search
				)
			);
		$options['order'] = array('Student.first_name', 'Student.middle_name', 'Student.third_name', 'Student.last_name', 'Student.preferred_name');
		if(!empty($limit)){
			$options['limit'] = $limit;
		}
		
		$list = $this->find('all', $options);
	
		$data = array();
		foreach ($list as $obj) {
			$student = $obj['Student'];
			$data[] = array(
				'label' => ModelHelper::getName($student, array('openEmisId'=>true, 'preferred'=>true)),
				'value' => $student['id']
			);
		}
		return $data;
	}

	// used by InstitutionSiteSection.edit
	public function getStudentOptions($institutionSiteId, $periodId) {
		$periodModel = ClassRegistry::init('AcademicPeriod');

		$periodObj = $periodModel->findById($periodId);
		$periodStartDate = $periodModel->getDate($periodObj['AcademicPeriod'], 'start_date');

		$alias = $this->alias;
		$options = array(
			'contain' => array('Student'),
			'conditions' => array(
				"$alias.institution_site_id" => $institutionSiteId,
				"$alias.start_date <= " => $periodStartDate,
				"$alias.end_date >= " => $periodStartDate,
				'Student.id != ' => null
			)
		);

		$list = $this->find('all', $options);
		$data = array();
		foreach ($list as $obj) {
			$studentObj = $obj['Student'];
			$data[$studentObj['id']] = ModelHelper::getName($studentObj, array('openEmisId' => true));
		}
		return $data;
	}
}

