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
		'Excel',
		'Search',
		'ControllerAction2',
		'DatePicker' => array('start_date', 'end_date'),
		'Year' => array('start_date' => 'start_year', 'end_date' => 'end_year')
	);
	
	public $belongsTo = array(
		'Students.Student',
		'Students.StudentStatus',
		'InstitutionSiteProgramme' => array(
			'className' => 'InstitutionSiteProgramme',
			'foreignKey' => false,
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id = InstitutionSiteStudent.institution_site_id',
				'InstitutionSiteProgramme.education_programme_id = InstitutionSiteStudent.education_programme_id'
			)
		),
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
	
	public function excelGetHeader($include){
		$fields = array(
			'Student.identification_no',
			'Student.first_name',
			'Student.middle_name',
			'Student.third_name',
			'Student.last_name',
			'Student.date_of_birth',
			'StudentStatus.name',
			'InstitutionSite.name',
			'EducationProgramme.name',
			'AcademicPeriod.name',
			'InstitutionSiteSection.name',
			'EducationGrade.name',
			'StudentCategory.name'
		);
		
		$header = $this->setHeader($fields);
		return $header;
	}
	
	public function excelGetFindOptions(){
		$options = parent::excelGetFindOptions();
		$options['recursive'] = -1;
		$options['joins'] = array(
			array(
				'table' => 'students',
				'alias' => 'Student',
				'conditions' => array(
					'Student.id = InstitutionSiteStudent.student_id'
				)
			),
			array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array(
					'InstitutionSite.id = InstitutionSiteStudent.institution_site_id'
				)
			),
			array(
				'table' => 'education_programmes',
				'alias' => 'EducationProgramme',
				'conditions' => array(
					'EducationProgramme.id = InstitutionSiteStudent.education_programme_id'
				)
			),
			array(
				'table' => 'field_option_values',
				'alias' => 'StudentStatus',
				'type' => 'LEFT',
				'conditions' => array(
					'StudentStatus.id = InstitutionSiteStudent.student_status_id'
				)
			),
			array(
				'table' => 'institution_site_section_students',
				'alias' => 'InstitutionSiteSectionStudent',
				'type' => 'LEFT',
				'conditions' => array(
					'InstitutionSiteSectionStudent.student_id = InstitutionSiteStudent.student_id'
				)
			),
			array(
				'table' => 'field_option_values',
				'alias' => 'StudentCategory',
				'type' => 'LEFT',
				'conditions' => array(
					'StudentCategory.id = InstitutionSiteSectionStudent.student_category_id'
				)
			),
			array(
				'table' => 'institution_site_sections',
				'alias' => 'InstitutionSiteSection',
				'type' => 'LEFT',
				'conditions' => array(
					'InstitutionSiteSection.institution_site_id = InstitutionSiteStudent.institution_site_id',
					'InstitutionSiteSection.id = InstitutionSiteSectionStudent.institution_site_section_id'
				)
			),
			array(
				'table' => 'academic_periods',
				'alias' => 'AcademicPeriod',
				'type' => 'LEFT',
				'conditions' => array(
					'AcademicPeriod.id = InstitutionSiteSection.academic_period_id'
				)
			),
			array(
				'table' => 'education_grades',
				'alias' => 'EducationGrade',
				'type' => 'LEFT',
				'conditions' => array(
					'EducationGrade.id = InstitutionSiteSection.education_grade_id'
				)
			),
		);
		unset($options['contain']);
		$options['order'] = array('Student.identification_no', 'AcademicPeriod.order', 'InstitutionSiteSection.name');
		
		return $options;
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

		$this->fields['academic_period_id'] = array(
			'type' => 'select',
			'visible' => true,
			'attr' => array('onchange' => "$('#reload').click()")
		);
		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['student_id']['type'] = 'hidden';
		$this->fields['student_id']['attr'] = array('autocomplete' => 'student_id');
		$this->fields['student_status_id']['type'] = 'select';
		$this->fields['student_status_id']['options'] = $this->StudentStatus->getList();
		$this->fields['institution_site_id']['type'] = 'hidden';
		$this->fields['institution_site_id']['value'] = $institutionSiteId;
		$this->fields['education_programme_id'] = array(
			'type' => 'select',
			'visible' => true,
			'attr' => array('onchange' => "$('#reload').click()")
		);
		$this->fields['education_grade_id'] = array(
			'type' => 'select',
			'visible' => true,
			'attr' => array('onchange' => "$('#reload').click()")
		);
		$this->fields['institution_site_section_id']['type'] = 'select';
		$this->fields['institution_site_section_id']['visible'] = true;
		$this->fields['student_category_id']['type'] = 'select';
		$this->fields['student_category_id']['visible'] = true;
		$this->fields['student_status_id']['visible'] = false;

		$this->setFieldOrder('institution', 0);
		$this->setFieldOrder('academic_period_id', 1);
		$this->setFieldOrder('education_programme_id', 2);
		$this->setFieldOrder('education_grade_id', 3);
		$this->setFieldOrder('institution_site_section_id', 4);
		$this->setFieldOrder('student_category_id', 5);
		$this->setFieldOrder('start_date', 6);
		$this->setFieldOrder('end_date', 7);
		
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
		$academicPeriodOptions = $AcademicPeriod->getAcademicPeriodList();
		$this->fields['academic_period_id']['options'] = $academicPeriodOptions;

		$InstitutionSiteGrade = ClassRegistry::init('InstitutionSiteGrade');
		$InstitutionSiteSection = ClassRegistry::init('InstitutionSiteSection');

		$InstitutionSiteSectionStudent = ClassRegistry::init('InstitutionSiteSectionStudent');
		$categoryOptions = $InstitutionSiteSectionStudent->StudentCategory->getList();
		$this->fields['student_category_id']['options'] = $categoryOptions;
		
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$academicPeriodId = $data[$this->alias]['academic_period_id'];
			$selectedProgrammeId = $data[$this->alias]['education_programme_id'];
			$selectedGradeId = $data[$this->alias]['education_grade_id'];
			$selectedSectionId = $data[$this->alias]['institution_site_section_id'];
			$selectedStudentCategoryId = $data[$this->alias]['student_category_id'];
			$studentId = $data[$this->alias]['student_id'];
			
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

			if ($data['submit'] == 'reload') {
				$programmeOptions = $this->InstitutionSiteProgramme->getSiteProgrammeOptions($institutionSiteId, $academicPeriodId);
				$this->fields['education_programme_id']['options'] = $programmeOptions;
				$selectedProgrammeId = isset($selectedProgrammeId) && array_key_exists($selectedProgrammeId, $programmeOptions) ? $selectedProgrammeId : key($programmeOptions);

				$gradeOptions = $InstitutionSiteGrade->getGradeOptions($institutionSiteId, $academicPeriodId, $selectedProgrammeId);
				$gradeOptions = $this->controller->Option->prependLabel($gradeOptions, 'InstitutionSiteStudent.select_grade');
				$selectedGradeId = isset($selectedGradeId) && array_key_exists($selectedGradeId, $gradeOptions) ? $selectedGradeId : key($gradeOptions);
			} else {
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
							$this->alias . '.education_programme_id' => $selectedProgrammeId
						)
					));
					
					if ($count > 0) {
						$this->Message->alert('general.exists');
					} else {
						$studentStatusId = $this->StudentStatus->getDefaultValue();
						$data[$this->alias]['student_status_id'] = $studentStatusId;
						
						if(isset($data['new'])){
							$this->Session->write('InstitutionSiteStudent.addNew', $data[$this->alias]);
							return $this->redirect(array('controller' => 'Students', 'action' => 'add'));
						}else{
							if($selectedSectionId != 0) {
								$InstitutionSiteSectionStudent = ClassRegistry::init('InstitutionSiteSectionStudent');

								$institutionSiteSectionStudentId = $InstitutionSiteSectionStudent->field('id', array(
									'InstitutionSiteSectionStudent.student_id' => $studentId,
									'InstitutionSiteSectionStudent.education_grade_id' => $selectedGradeId,
									'InstitutionSiteSectionStudent.institution_site_section_id' => $selectedSectionId
								));
								if($institutionSiteSectionStudentId) {
									$autoInsertData['InstitutionSiteSectionStudent']['id'] = $institutionSiteSectionStudentId;	
								}
								$autoInsertData['InstitutionSiteSectionStudent']['student_id'] = $studentId;
								$autoInsertData['InstitutionSiteSectionStudent']['education_grade_id'] = $selectedGradeId;
								$autoInsertData['InstitutionSiteSectionStudent']['institution_site_section_id'] = $selectedSectionId;
								$autoInsertData['InstitutionSiteSectionStudent']['student_category_id'] = $selectedStudentCategoryId;
								$autoInsertData['InstitutionSiteSectionStudent']['status'] = 1;
								$InstitutionSiteSectionStudent->save($autoInsertData);
							}

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
		} else {
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

			$programmeOptions = $this->InstitutionSiteProgramme->getSiteProgrammeOptions($institutionSiteId, $academicPeriodId);
			$this->fields['education_programme_id']['options'] = $programmeOptions;
			$selectedProgrammeId = key($programmeOptions);
			
			$gradeOptions = $InstitutionSiteGrade->getGradeOptions($institutionSiteId, $academicPeriodId, $selectedProgrammeId);
			$gradeOptions = $this->controller->Option->prependLabel($gradeOptions, 'InstitutionSiteStudent.select_grade');
			$selectedGradeId = key($gradeOptions);

			$sectionOptions = $InstitutionSiteSection->getSectionOptions($academicPeriodId, $institutionSiteId, $selectedGradeId);
		}

		$this->fields['education_grade_id']['options'] = $gradeOptions;
		
		$sectionOptions = $InstitutionSiteSection->getSectionOptions($academicPeriodId, $institutionSiteId, $selectedGradeId);
		$sectionOptions = $this->controller->Option->prependLabel($sectionOptions, 'InstitutionSiteStudent.select_section');
		$this->fields['institution_site_section_id']['options'] = $sectionOptions;
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
						'InstitutionSiteProgramme.education_programme_id = InstitutionSiteStudent.education_programme_id',
						'InstitutionSiteProgramme.institution_site_id = InstitutionSiteStudent.institution_site_id'
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteStudent.student_id = ' . $studentId,
				'InstitutionSiteStudent.institution_site_id = ' . $InstitutionSiteId
			)
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
					'conditions' => array(
						'InstitutionSiteProgramme.education_programme_id = InstitutionSiteStudent.education_programme_id',
						'InstitutionSiteProgramme.institution_site_id = InstitutionSiteStudent.institution_site_id'
					)
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
	public function getStudentOptions($institutionSiteId, $periodId, $sectionId = null) {
		$periodModel = ClassRegistry::init('AcademicPeriod');

		$periodObj = $periodModel->findById($periodId);
		$periodStartDate = $periodModel->getDate($periodObj['AcademicPeriod'], 'start_date');
		$periodEndDate = $periodModel->getDate($periodObj['AcademicPeriod'], 'end_date');

		$sectionGrades = array();
		if (!is_null($sectionId)) {
			$sectionGrades = ClassRegistry::init('InstitutionSiteSection')->getGradesBySection($sectionId);
			$sectionGrades = array_keys($sectionGrades);
		}

		$alias = $this->alias;
		$options = array(
			'contain' => array(
				'Student',
				'EducationProgramme' => array(
					'fields' => array('id','name'),
					'EducationGrade' => array('id','name')
				)
			),
			'conditions' => array(
				$alias.'.institution_site_id = ' . $institutionSiteId,
				'OR' => array(
					'OR' => array(
						array(
							$alias.'.end_date IS NOT NULL',
							$alias.'.start_date <= "' . $periodStartDate . '"',
							$alias.'.end_date >= "' . $periodStartDate . '"'
						),
						array(
							$alias.'.end_date IS NOT NULL',
							$alias.'.start_date <= "' . $periodEndDate . '"',
							$alias.'.end_date >= "' . $periodEndDate . '"'
						),
						array(
							$alias.'.end_date IS NOT NULL',
							$alias.'.start_date >= "' . $periodStartDate . '"',
							$alias.'.end_date <= "' . $periodEndDate . '"'
						)
					),
					array(
						$alias.'.end_date IS NULL',
						$alias.'.start_date <= "' . $periodEndDate . '"'
					)
				)
			)
		);

		$list = $this->find('all', $options);
		$data = array();
		foreach ($list as $okey => $obj) {
			$studentGradeEligible = $obj['EducationProgramme']['EducationGrade'];
			$studentGradeKeys = array();
			foreach ($studentGradeEligible as $key => $value) {
				$studentGradeKeys[] = $value['id'];
			}

			$studentProgramEligible = false;

			foreach ($studentGradeKeys as $key => $value) {
				if (in_array($value, $sectionGrades)) {
					$studentProgramEligible = true;
				}
			}
			if ($studentProgramEligible) {
				$studentObj = $obj['Student'];
				$data[$studentObj['id']] = ModelHelper::getName($studentObj, array('openEmisId' => true));
			}
		}
		return $data;
	}
}

