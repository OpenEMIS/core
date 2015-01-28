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
				'message' => 'Please enter a valid school academic period'
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
			'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
			'InstitutionSiteProgramme.status' => 1
		);

		$periodOptions = ClassRegistry::init('InstitutionSiteProgramme')->getAcademicPeriodOptions($conditions);

		$selectedPeriod = $this->checkIdInOptions($selectedPeriod, $periodOptions);
		
		if (empty($periodOptions)) {
			$this->Message->alert('InstitutionSite.noProgramme');
		}
		
		$data = $this->getListOfSections($selectedPeriod, $institutionSiteId);
		
		$gradeOptionsData = $this->InstitutionSiteSectionGrade->getInstitutionGradeOptions($institutionSiteId, $selectedPeriod);
		$gradeOptions = $this->controller->Option->prependLabel($gradeOptionsData, 'InstitutionSiteSection.all_grades_select');
		
		$this->setVar(compact('data', 'periodOptions', 'selectedPeriod', 'gradeOptions', 'selectedGradeId'));
	}
	
	public function singleGradeAdd($selectedAcademicPeriod=0, $selectedGradeId=0) {
		$this->Navigation->addCrumb('Add Section');
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		
		$academicPeriodOptions = ClassRegistry::init('InstitutionSiteProgramme')->getInstitutionAcademicPeriodOptions($institutionSiteId);
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
		$startDate = $academicPeriodObj['AcademicPeriod']['start_date'];
		$endDate = $academicPeriodObj['AcademicPeriod']['end_date'];
		$InstitutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');
		$staffOptions = $InstitutionSiteStaff->getInstitutionSiteStaffOptions($institutionSiteId, $startDate, $endDate);

		$numberOfSectionsOptions = $this->numberOfSectionsOptions();
		$numberOfSections = 1;
		
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
		
		$this->setVar(compact('currentTab', 'gradeOptions', 'numberOfSections', 'staffOptions', 'numberOfSections', 'numberOfSectionsOptions'));
		$this->setVar(compact('selectedAcademicPeriod', 'academicPeriodOptions', 'shiftOptions', 'institutionSiteId', 'selectedGradeId', 'grade'));
	}
	
	public function multiGradesAdd($selectedAcademicPeriod=0) {
		$this->Navigation->addCrumb('Add Section');
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$academicPeriodOptions = ClassRegistry::init('InstitutionSiteProgramme')->getInstitutionAcademicPeriodOptions($institutionSiteId);
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

		$currentTab = 'Multiple Grades';
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
			}
		}
		
		$this->setVar(compact('currentTab', 'gradeOptions', 'staffOptions', 'gradeChecklist'));
		$this->setVar(compact('grades', 'selectedAcademicPeriod', 'academicPeriodOptions', 'shiftOptions', 'institutionSiteId'));
	}
	
	public function view($id=0) {
		$this->Session->write($this->alias.'.id', $id);
		$data = $this->findById($id);
		
		if (!empty($data)) {
			$sectionName = $data[$this->alias]['name'];
			$this->Navigation->addCrumb($sectionName);
			$grades = $this->InstitutionSiteSectionGrade->getGradesBySection($id);
			$selectedAction = $this->alias . '/view/' . $id;
			
			$studentsData = $this->InstitutionSiteSectionStudent->getSutdentsBySection($id);
			$this->setVar(compact('studentsData'));
			
			$this->setVar(compact('data', 'grades', 'selectedAction'));
			$this->setVar('actionOptions', $this->getSectionActions());
		} else {
			$this->Message->alert('general.notExists');
			$this->redirect(array('action' => $this->_action));
		}
	}

	public function edit($id=0) {
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$data = $this->findById($id);

		if (!empty($data)) {
			if($this->request->is('post') || $this->request->is('put')) {
				$postData = $this->request->data;
				//pr($postData);die;
				if ($this->saveAll($postData)) {
					$this->Message->alert('general.edit.success');
					$this->redirect(array('action' => $this->alias, 'view', $id));
				}
				
				$this->request->data['AcademicPeriod']['name'] = $data['AcademicPeriod']['name'];
			} else {
				$this->request->data = $data;
			}
			
			$grades = $this->InstitutionSiteSectionGrade->getGradesBySection($id);
			$studentsData = $this->InstitutionSiteSectionStudent->getSectionStudentsData($id);
			
			$yearObj = ClassRegistry::init('SchoolYear')->findById($data['SchoolYear']['id']);
			$startDate = $yearObj['SchoolYear']['start_date'];
			$endDate = $yearObj['SchoolYear']['end_date'];
		
			$InstitutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');
			$staffOptions = $InstitutionSiteStaff->getInstitutionSiteStaffOptions($institutionSiteId, $startDate, $endDate);
			
			$StudentCategory = ClassRegistry::init('Students.StudentCategory');
			$categoryOptions = $StudentCategory->getList();
			
			$name = $data[$this->alias]['name'];
			$this->Navigation->addCrumb($name);
			
			$InstitutionSiteShiftModel = ClassRegistry::init('InstitutionSiteShift');
			$shiftOptions = $InstitutionSiteShiftModel->getShiftOptions($institutionSiteId, $data['InstitutionSiteSection']['academic_period_id']);
			
			$this->setVar(compact('grades', 'shiftOptions', 'studentsData', 'staffOptions', 'categoryOptions'));
		} else {
			$this->Message->alert('general.notExists');
			$this->redirect(array('action' => $this->alias));
		}
	}

	public function remove() {
		$this->autoRender = false;
		$id = $this->Session->read($this->alias.'.id');
		$obj = $this->findById($id);

		$this->delete($id);
		$this->Message->alert('general.delete.success');
		$this->redirect(array('action' => $this->alias, 'index', $obj[$this->alias]['academic_period_id']));
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
		}
		
		$data = $this->find('list', $options);
		return $data;
	}
	
	public function getListOfSections($periodId, $institutionSiteId) {
		$data = $this->find('all', array(
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
		));
		
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
		$total = 5;
		$options = array();
		for($i=1; $i<=$total; $i++){
			$options[$i] = $i;
		}
		
		return $options;
	}
}
