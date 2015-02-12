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

class InstitutionSiteSection extends AppModel {
	public $belongsTo = array(
		'AcademicPeriod',
		'InstitutionSite',
		'InstitutionSiteShift',
		'EducationGrade',
		'Staff.Staff',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id'
		)
	);
	public $hasMany = array(
		'InstitutionSiteSectionClass',
		'InstitutionSiteSectionStudent',
		'InstitutionSiteSectionGrade'
	);
	
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid name'
			)
		),
		'academic_period_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid school academic period',
				'on' => 'create'
			)
		),
		'institution_site_shift_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid shift'
			)
		)
	);
	
	public $actsAs = array(
		'Excel',
		'ControllerAction2',
		'AcademicPeriod'
	);
	
	public function beforeAction() {
		parent::beforeAction();
	}
	
	public function index($selectedPeriod=0, $selectedGradeId=0) {
		$this->Navigation->addCrumb('List of Sections');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$conditions = array(
			'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId
		);

		$periodOptions = ClassRegistry::init('InstitutionSiteProgramme')->getAcademicPeriodOptions($conditions);
		$selectedPeriod = $this->checkIdInOptions($selectedPeriod, $periodOptions);
		
		if (empty($periodOptions)) {
			$this->Message->alert('InstitutionSite.noProgramme');
		}
		
		$data = $this->getListOfSections($selectedPeriod, $institutionSiteId, $selectedGradeId);
		
		$gradeOptionsData = $this->InstitutionSiteSectionGrade->getInstitutionGradeOptions($institutionSiteId, $selectedPeriod);
		$gradeOptions = $this->controller->Option->prependLabel($gradeOptionsData, 'InstitutionSiteSection.all_grades_select');
		
		$this->setVar(compact('data', 'periodOptions', 'selectedPeriod', 'gradeOptions', 'selectedGradeId'));
	}
	
	public function singleGradeAdd($selectedAcademicPeriod=0, $selectedGradeId=0) {
		$this->Navigation->addCrumb('Add Section');
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$conditions = array(
			'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId
		);
		$academicPeriodOptions = ClassRegistry::init('InstitutionSiteProgramme')->getAcademicPeriodOptions($conditions);

		if(empty($academicPeriodOptions)) {
			$this->Message->alert('InstitutionSite.noProgramme');
			return $this->redirect(array('action' => 'InstitutionSiteSection', 'index'));
		}else{
			if ($selectedAcademicPeriod != 0) {
				if (!array_key_exists($selectedAcademicPeriod, $academicPeriodOptions)) {
					$selectedAcademicPeriod = key($academicPeriodOptions);
				}
			} else {
				$selectedAcademicPeriod = key($academicPeriodOptions);
			}
		}
		
		$currentTab = 'Single Grade';
		$InstitutionSiteShiftModel = ClassRegistry::init('InstitutionSiteShift');
		$InstitutionSiteShiftModel->createInstitutionDefaultShift($institutionSiteId, $selectedAcademicPeriod);
		$gradeOptions = $this->InstitutionSiteSectionGrade->getInstitutionGradeOptions($institutionSiteId, $selectedAcademicPeriod);
		if ($selectedGradeId != 0) {
			if (!array_key_exists($selectedGradeId, $gradeOptions)) {
				$selectedGradeId = key($gradeOptions);
			}
		} else {
			$selectedGradeId = key($gradeOptions);
		}

		$shiftOptions = $InstitutionSiteShiftModel->getShiftOptions($institutionSiteId, $selectedAcademicPeriod);

		$academicPeriodObj = ClassRegistry::init('AcademicPeriod')->findById($selectedAcademicPeriod);
		$startDate = date('Y-m-d', strtotime($academicPeriodObj['AcademicPeriod']['start_date']));
		$endDate = date('Y-m-d', strtotime($academicPeriodObj['AcademicPeriod']['end_date']));
		$InstitutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');
		$staffOptions = $InstitutionSiteStaff->getInstitutionSiteStaffOptions($institutionSiteId, $startDate, $endDate);

		$numberOfSectionsOptions = $this->numberOfSectionsOptions();
		$numberOfSections = 1;
		$startingSectionNumber = $this->getNewSectionNumber($institutionSiteId, $selectedGradeId);
		
		if($this->controller->request->is(array('post', 'put'))) {
			$postData = $this->request->data;
			if ($postData['submit'] == 'reload') {
				$numberOfSections = $postData['InstitutionSiteSection']['number_of_sections'];
				$selectedGradeId = $postData['InstitutionSiteSection']['education_grade_id'];
			}else{
				$commonData = $postData['InstitutionSiteSection'];
				$sectionsData = $postData['InstitutionSections'];
				foreach($sectionsData as $key => $row){
					$sectionsData[$key]['education_grade_id'] = $commonData['education_grade_id'];
					$sectionsData[$key]['institution_site_shift_id'] = $commonData['institution_site_shift_id'];
					$sectionsData[$key]['institution_site_id'] = $commonData['institution_site_id'];
					$sectionsData[$key]['academic_period_id'] = $commonData['academic_period_id'];
				}
				$result = $this->saveAll($sectionsData);
				if ($result) {
					$this->Message->alert('general.add.success');
					return $this->redirect(array('action' => 'InstitutionSiteSection', 'index', $selectedAcademicPeriod, $selectedGradeId));
				}else{
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.add.failed');
				}
			}
		}
		
		$grade = ClassRegistry::init('EducationGrade')->findById($selectedGradeId);
		
		$this->setVar(compact('currentTab', 'gradeOptions', 'numberOfSections', 'staffOptions', 'numberOfSections', 'numberOfSectionsOptions', 'startingSectionNumber'));
		$this->setVar(compact('selectedAcademicPeriod', 'academicPeriodOptions', 'shiftOptions', 'institutionSiteId', 'selectedGradeId', 'grade'));
	}
	
	public function getNewSectionNumber($institutionSiteId, $gradeId){
		$data = $this->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'InstitutionSiteSection.institution_site_id' => $institutionSiteId,
				'InstitutionSiteSection.education_grade_id' => $gradeId
			),
			'order' => array('InstitutionSiteSection.section_number DESC')
		));
		
		$number = 1;
		if(!empty($data)){
			$number = $data['InstitutionSiteSection']['section_number'] + 1;
		}
		
		return $number;
	}
	
	public function multiGradesAdd($selectedAcademicPeriod=0) {
		$this->Navigation->addCrumb('Add Section');
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$conditions = array(
			'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId
		);
		$academicPeriodOptions = ClassRegistry::init('InstitutionSiteProgramme')->getAcademicPeriodOptions($conditions);
		if(empty($academicPeriodOptions)) {
			$this->Message->alert('InstitutionSite.noProgramme');
			return $this->redirect(array('action' => 'InstitutionSiteSection', 'index'));
		}else{
			if ($selectedAcademicPeriod != 0) {
				if (!array_key_exists($selectedAcademicPeriod, $academicPeriodOptions)) {
					$selectedAcademicPeriod = key($academicPeriodOptions);
				}
			} else {
				$selectedAcademicPeriod = key($academicPeriodOptions);
			}
		}

		$currentTab = 'Multi Grades';
		$grades = $this->InstitutionSiteSectionGrade->getAvailableGradesForNewSection($institutionSiteId, $selectedAcademicPeriod);
		$InstitutionSiteShiftModel = ClassRegistry::init('InstitutionSiteShift');
		$InstitutionSiteShiftModel->createInstitutionDefaultShift($institutionSiteId, $selectedAcademicPeriod);
		$shiftOptions = $InstitutionSiteShiftModel->getShiftOptions($institutionSiteId, $selectedAcademicPeriod);

		$academicPeriodObj = ClassRegistry::init('AcademicPeriod')->findById($selectedAcademicPeriod);
		$startDate = $academicPeriodObj['AcademicPeriod']['start_date'];
		$endDate = $academicPeriodObj['AcademicPeriod']['end_date'];
		$InstitutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');
		$staffOptions = $InstitutionSiteStaff->getInstitutionSiteStaffOptions($institutionSiteId, $startDate, $endDate);

		if($this->request->is('post') || $this->request->is('put')) {
			$data = $this->request->data;
			$result = $this->saveAll($data);
			if ($result) {
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => 'InstitutionSiteSection', 'index', $selectedAcademicPeriod));
			}else{
				$this->log($this->validationErrors, 'debug');
				$this->Message->alert('general.add.failed');
			}
		}
		
		$this->setVar(compact('currentTab', 'gradeOptions', 'staffOptions', 'gradeChecklist'));
		$this->setVar(compact('grades', 'selectedAcademicPeriod', 'academicPeriodOptions', 'shiftOptions', 'institutionSiteId'));
	}
	
	public function view($id=0) {
		if ($this->exists($id)) {
			$this->contain(array(
					'ModifiedUser', 'CreatedUser', 'AcademicPeriod', 
					'InstitutionSiteShift', 'EducationGrade', 'Staff', 
					'InstitutionSiteSectionStudent' => array(
						'Student' => array(
							'fields' => array(
								'Student.identification_no', 'Student.first_name', 'Student.middle_name', 
								'Student.third_name', 'Student.last_name', 'Student.gender', 'Student.date_of_birth'
							)
						)
					)
				)
			);
			$data = $this->findById($id);
			$this->Session->write($this->alias.'.id', $id);

			$sectionName = $data[$this->alias]['name'];
			$this->Navigation->addCrumb($sectionName);
			$grades = $this->InstitutionSiteSectionGrade->getGradesBySection($id);
			$categoryOptions = $this->InstitutionSiteSectionStudent->StudentCategory->getList(array('listOnly' => true));
			
			$this->setVar(compact('data', 'grades', 'categoryOptions'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => get_class($this)));
		}
	}

	public function edit($id=0) {
		$this->render = 'auto';
		if ($this->exists($id)) {
			$institutionSiteId = $this->Session->read('InstitutionSite.id');
			$contain = array(
				'AcademicPeriod', 
				'Staff', 
				'InstitutionSiteSectionStudent' => array(
					'Student' => array(
						'fields' => array(
							'Student.identification_no', 'Student.first_name', 'Student.middle_name', 
							'Student.third_name', 'Student.last_name', 'Student.gender', 'Student.date_of_birth'
						)
					)
				)
			);
			$this->contain($contain);
			$data = $this->findById($id);
			$contentHeader = $data[$this->alias]['name'];
			$this->Navigation->addCrumb($contentHeader);

			$periodId = $data['AcademicPeriod']['id'];
			$periodStartDate = $this->AcademicPeriod->getDate($data['AcademicPeriod'], 'start_date');
            $periodEndDate = $this->AcademicPeriod->getDate($data['AcademicPeriod'], 'end_date');

			// fields setup
			$this->fields['institution_site_id']['type'] = 'hidden';
			$this->fields['academic_period_id']['type'] = 'disabled';
			$this->fields['academic_period_id']['value'] = $data['AcademicPeriod']['name'];

			$staffOptions = ClassRegistry::init('InstitutionSiteStaff')->getInstitutionSiteStaffOptions($institutionSiteId, $periodStartDate, $periodEndDate);
			$this->fields['staff_id']['type'] = 'select';
			$this->fields['staff_id']['options'] = $staffOptions;

			$shiftOptions = $this->InstitutionSiteShift->getShiftOptions($institutionSiteId, $periodId);
			$this->fields['institution_site_shift_id']['type'] = 'select';
			$this->fields['institution_site_shift_id']['options'] = $shiftOptions;

			$this->fields['education_grade_id']['type'] = 'hidden'; // hide this field temporary

			$this->fields['students'] = array(
				'type' => 'element',
				'element' => '../InstitutionSites/InstitutionSiteSection/students',
				'override' => true,
				'visible' => true
			);
			// end fields setup

			$categoryOptions = $this->InstitutionSiteSectionStudent->StudentCategory->getListOnly();

			$InstitutionSiteStudent = ClassRegistry::init('InstitutionSiteStudent');
			$studentOptions = $InstitutionSiteStudent->getStudentOptions($institutionSiteId, $periodId);
			$studentOptions = $this->attachSectionInfo($id, $studentOptions, $institutionSiteId, $periodId);
			$studentOptions = $this->controller->Option->prependLabel($studentOptions, $this->alias . '.add_student');
			
			if($this->request->is('post') || $this->request->is('put')) {
				$postData = $this->request->data;

				if ($postData['submit'] == 'add') {
					if (isset($postData[$this->alias]['student_id']) && !empty($postData[$this->alias]['student_id'])) {
						$studentId = $postData[$this->alias]['student_id'];

						$InstitutionSiteStudent->Student->recursive = -1;
						$studentObj = $InstitutionSiteStudent->Student->findById($studentId, $contain['InstitutionSiteSectionStudent']['Student']['fields']);
						
						$newRow = array(
							'student_id' => $studentId,
							'student_category_id' => key($categoryOptions),
							'status' => 1,
							'Student' => $studentObj['Student']
						);

						// search if the new student was previously added before
						foreach ($data['InstitutionSiteSectionStudent'] as $row) {
							if ($row['student_id'] == $studentId) {
								$newRow['id'] = $row['id'];
							}
						}

						$this->request->data['InstitutionSiteSectionStudent'][] = $newRow;

						unset($this->request->data[$this->alias]['student_id']);
						unset($this->request->data['submit']);
					}
				} else if ($postData['submit'] = 'Save') {
					$this->InstitutionSiteSectionStudent->updateAll(
						array('InstitutionSiteSectionStudent.status' => 0),
						array('InstitutionSiteSectionStudent.institution_site_section_id' => $id)
					);
					if ($this->saveAll($postData)) {
						$this->Message->alert('general.edit.success');
						$this->redirect(array('action' => $this->alias, 'view', $id));
					}
				}
			} else {
				$this->request->data = $data;
			}

			// removing existing students from StudentOptions
			foreach ($this->request->data['InstitutionSiteSectionStudent'] as $row) {
				if ($row['status'] == 1 && array_key_exists($row['student_id'], $studentOptions)) {
					unset($studentOptions[$row['student_id']]);
				}
			}

			$this->setVar(compact('categoryOptions', 'studentOptions', 'contentHeader'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => $this->alias));
		}
	}

	public function attachSectionInfo($id, $studentOptions, $institutionSiteId, $periodId){
		$this->contain(array(
			'InstitutionSiteSectionStudent' => array(
				'conditions' => array(
					'InstitutionSiteSectionStudent.student_id' => array_keys($studentOptions),
					'InstitutionSiteSectionStudent.status' => 1
				)
			)
		));
		$sectionsWithStudents = $this->findAllByInstitutionSiteIdAndAcademicPeriodId($institutionSiteId, $periodId);
		foreach($sectionsWithStudents as $sws){
			if($sws['InstitutionSiteSection']['id'] != $id) {
				$studentOptions[$sws['InstitutionSiteSection']['name']] = array();
				foreach($sws['InstitutionSiteSectionStudent'] as $student){
					$studentOptions[$sws['InstitutionSiteSection']['name']][] = array(
						'name' => $studentOptions[$student['student_id']],
						'value' => $student['student_id'],
						'disabled' => true
					);
					unset($studentOptions[$student['student_id']]);
				}
			}
		}
		return $studentOptions;		
	}

	public function remove() {
		$this->autoRender = false;
		$id = $this->Session->read($this->alias.'.id');
		$obj = $this->findById($id);

		$this->delete($id);
		$this->Message->alert('general.delete.success');
		$this->redirect(array('action' => $this->alias, 'index', $obj[$this->alias]['academic_period_id']));
	}

	public function getGradeOptions($sectionId) {
		$this->contain(array(
			'EducationGrade', 
			'InstitutionSiteSectionGrade' => array(
				'EducationGrade' => array('fields' => array('EducationGrade.id', 'EducationGrade.name'))
			)
		));
		$data = $this->findById($sectionId);

		$list = array();
		if (!empty($data['EducationGrade']['id'])) {
			$list[$data['EducationGrade']['id']] = $data['EducationGrade']['name'];
		} else {
			foreach ($data['InstitutionSiteSectionGrade'] as $obj) {
				$id = $obj['EducationGrade']['id'];
				$name = $obj['EducationGrade']['name'];
				$list[$id] = $name;
			}
		}
		return $list;
	}
	
	public function getClass($classId, $institutionSiteId=0) {
		$conditions = array('InstitutionSiteClass.id' => $classId);
		
		if($institutionSiteId > 0) {
			$conditions['InstitutionSiteClass.institution_site_id'] = $institutionSiteId;
		}
		
		$obj = $this->find('first', array('conditions' => $conditions));
		return $obj;
	}

	public function getSectionOptions($academicPeriodId, $institutionSiteId, $gradeId=false) {
		$options = array(
			'fields' => array('InstitutionSiteSection.id', 'InstitutionSiteSection.name'),
			'conditions' => array(
				'InstitutionSiteSection.academic_period_id' => $academicPeriodId,
				'InstitutionSiteSection.institution_site_id' => $institutionSiteId
			),
			'order' => array('InstitutionSiteSection.name')
		);

		if($gradeId!==false) {
			$multiGrade = $this->field('education_grade_id', array(
				'InstitutionSiteSection.academic_period_id' => $academicPeriodId,
				'InstitutionSiteSection.institution_site_id' => $institutionSiteId,
				'InstitutionSiteSection.education_grade_id' => $gradeId
			));

			if(is_null($multiGrade)) {
				$options['joins'] = array(
					array(
						'table' => 'institution_site_section_grades',
						'alias' => 'InstitutionSiteSectionGrade',
						'conditions' => array(
							'InstitutionSiteSectionGrade.institution_site_section_id = InstitutionSiteSection.id',
							'InstitutionSiteSectionGrade.education_grade_id = ' . $gradeId,
							'InstitutionSiteSectionGrade.status = 1'
						)
					)
				);
				$options['group'] = array('InstitutionSiteSection.id');
			} else {
				$options['conditions']['InstitutionSiteSection.education_grade_id'] = $gradeId;
			}
		}

		$data = $this->find('list', $options);
		return $data;
	}
	
	public function getListOfSections($periodId, $institutionSiteId, $gradeId=0) {
		$options = array(
			'fields' => array(
				'InstitutionSiteSection.id', 'InstitutionSiteSection.name',
				'Staff.first_name', 'Staff.middle_name', 'Staff.third_name', 'Staff.last_name',
				'EducationGrade.name'
			),
			'contain' => array('Staff', 'EducationGrade'),
			'conditions' => array(
				'InstitutionSiteSection.academic_period_id' => $periodId,
				'InstitutionSiteSection.institution_site_id' => $institutionSiteId
			),
			'order' => array('InstitutionSiteSection.name')
		);

		if (!empty($gradeId)) {
			$options['conditions']['InstitutionSiteSection.education_grade_id'] = $gradeId;

			// need to include multi grade search
		}

		$data = $this->find('all', $options);
		
		foreach($data as $i => $obj) {
			$id = $obj[$this->alias]['id'];
			$data[$i][$this->alias]['classes'] = $this->InstitutionSiteSectionClass->getClassCount($id);
			$data[$i][$this->alias]['gender'] = $this->InstitutionSiteSectionStudent->getGenderTotalBySection($id);
		}
		return $data;
	}
	
	public function getSectionListByInstitution($institutionSiteId, $academicPeriodId=0) {
		$options = array();
		$options['fields'] = array('InstitutionSiteSection.id', 'InstitutionSiteSection.name');
		$options['order'] = array('InstitutionSiteSection.name');
		$options['conditions'] = array('InstitutionSiteSection.institution_site_id' => $institutionSiteId);
		
		if (!empty($academicPeriodId)) {
			$options['conditions']['InstitutionSiteSection.academic_period_id'] = $academicPeriodId;
		}
		
		$data = $this->find('list', $options);
		return $data;
	}
	
	public function getSectionListWithAcademicPeriod($institutionSiteId, $academicPeriodId, $assessmentId){
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('InstitutionSiteSection.id', 'InstitutionSiteSection.name', 'AcademicPeriod.name'),
			'joins' => array(
				array(
					'table' => 'academic_periods',
					'alias' => 'AcademicPeriod',
					'conditions' => array('InstitutionSiteSection.academic_period_id = AcademicPeriod.id')
				),
				array(
					'table' => 'institution_site_section_grades',
					'alias' => 'InstitutionSiteSectionGrade',
					'conditions' => array(
						'InstitutionSiteSection.id = InstitutionSiteSectionGrade.institution_site_section_id'
					)
				),
				array(
					'table' => 'assessment_item_types',
					'alias' => 'AssessmentItemType',
					'conditions' => array(
						'InstitutionSiteSectionGrade.education_grade_id = AssessmentItemType.education_grade_id',
						'AssessmentItemType.id' => $assessmentId
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteSection.institution_site_id' => $institutionSiteId,
				'InstitutionSiteSection.academic_period_id' => $academicPeriodId
			),
			'order' => array('AcademicPeriod.name, InstitutionSiteSection.name')
		));
		
		$result = array();
		foreach($data AS $row){
			$section = $row['InstitutionSiteSection'];
			$academicPeriod = $row['AcademicPeriod'];
			$result[$section['id']] = $academicPeriod['name'] . ' - ' . $section['name'];
		}
		
		return $result;
	}
	
	public function getClassListByInstitutionAcademicPeriod($institutionSiteId, $academicPeriodId){
		if(empty($academicPeriodId)){
			$conditions = array(
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId
			);
		}else{
			$conditions = array(
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId,
				'InstitutionSiteClass.academic_period_id' => $academicPeriodId
			);
		}
		
		$data = $this->find('list', array(
			'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name'),
			'conditions' => $conditions,
			'order' => array('InstitutionSiteClass.name')
		));
		
		return $data;
	}
		
	public function getClassByIdAcademicPeriod($classId, $academicPeriodId){
		$data = $this->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'InstitutionSiteClass.id' => $classId,
				'InstitutionSiteClass.academic_period_id' => $academicPeriodId
			)
		));
		
		return $data;
	}
	
	public function numberOfSectionsOptions(){
		$total = 10;
		$options = array();
		for($i=1; $i<=$total; $i++){
			$options[$i] = $i;
		}
		
		return $options;
	}
	
	public function getSingleGradeBySection($sectionId) {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('EducationGrade.id', 'EducationCycle.name', 'EducationProgramme.name', 'EducationGrade.name'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionSiteSection.education_grade_id')
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				)
			),
			'conditions' => array(
				'InstitutionSiteSection.id' => $sectionId
			),
			'order' => array('EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));

		$list = array();
		foreach($data as $obj) {
			$id = $obj['EducationGrade']['id'];
			$cycleName = $obj['EducationCycle']['name'];
			$programmeName = $obj['EducationProgramme']['name'];
			$gradeName = $obj['EducationGrade']['name'];
			$list[$id] = sprintf('%s - %s - %s', $cycleName, $programmeName, $gradeName);
		}
		return $list;
	}
}
