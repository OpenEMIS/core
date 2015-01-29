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

class InstitutionSiteClass extends AppModel {
	public $belongsTo = array(
		'AcademicPeriod',
		'InstitutionSite',
		'Staff.Staff',
		'EducationSubject',
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
		'InstitutionSiteClassStaff',
		'InstitutionSiteClassStudent',
		'InstitutionSiteSectionClass'
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
				'message' => 'Please enter a valid academic period'
			)
		)
	);
	
	public $actsAs = array(
		'Excel',
		'ControllerAction2',
		'AcademicPeriod'
	);
	
	public $_action = 'InstitutionSiteClass';
	
	public function beforeAction() {
		parent::beforeAction();
		$this->setVar('_action', $this->_action);
		$this->setVar('selectedAction', $this->_action . 'View');
	}
	
	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'name', 'model' => 'AcademicPeriod'),
				array('field' => 'name'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function getClassActions($id=0) {
		if($id==0) {
			$id = $this->Session->read($this->alias.'.id');
		}
		$options = array(
			'classesView/'.$id => __('Class Details'),
			'classesStudent' => __('Students'),
			'classesStaff' => __('Staff'),
			'classesSubject' => __('Subjects')
		);
		return $options;
	}
	
	public function index($selectedAcademicPeriod=null, $selectedSection=null) {
		$this->Navigation->addCrumb('List of Classes');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$academicPeriodConditions = array(
			'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
			'InstitutionSiteProgramme.status' => 1,
			'AcademicPeriod.available' => 1,
			'AcademicPeriod.parent_id >' => 0
		);
		$academicPeriodOptions = ClassRegistry::init('InstitutionSiteProgramme')->getAcademicPeriodOptions($academicPeriodConditions);
		$selectedAcademicPeriod = isset($selectedAcademicPeriod)? $selectedAcademicPeriod: key($academicPeriodOptions);

		$InstitutionSiteSection = ClassRegistry::init('InstitutionSiteSection');
		$sectionOptions = $InstitutionSiteSection->getSectionOptions($selectedAcademicPeriod, $institutionSiteId);
		$selectedSection = isset($selectedSection) ? $selectedSection : key($sectionOptions);

		$data = $this->getListOfClasses($selectedAcademicPeriod, $institutionSiteId, $selectedSection);
		
		if(empty($academicPeriodOptions)){
			$this->Message->alert('InstitutionSite.noProgramme');
		}

		$this->setVar(compact('academicPeriodOptions', 'selectedAcademicPeriod', 'data', 'sectionOptions', 'selectedSection'));
	}
	
	public function add($selectedAcademicPeriod = null, $selectedSection = null) {
		$this->Navigation->addCrumb('Add Class');
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$academicPeriodConditions = array(
			'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
			'InstitutionSiteProgramme.status' => 1,
			'AcademicPeriod.available' => 1,
			'AcademicPeriod.parent_id >' => 0
		);
		$academicPeriodOptions = ClassRegistry::init('InstitutionSiteProgramme')->getAcademicPeriodOptions($academicPeriodConditions);
		if(!empty($academicPeriodOptions)) {
			$selectedAcademicPeriod = isset($selectedAcademicPeriod) ? $selectedAcademicPeriod : key($academicPeriodOptions);
			
			$sections = $this->InstitutionSiteSectionClass->getAvailableSectionsForNewClass($institutionSiteId, $selectedAcademicPeriod);
			
			$InstitutionSiteSection = ClassRegistry::init('InstitutionSiteSection');
			$sectionOptions = $InstitutionSiteSection->getSectionOptions($selectedAcademicPeriod, $institutionSiteId);
			$selectedSection = isset($selectedSection) ? $selectedSection : key($sectionOptions);
			
			$yearObj = ClassRegistry::init('AcademicPeriod')->findById($selectedAcademicPeriod);
			$startDate = $yearObj['AcademicPeriod']['start_date'];
			$endDate = $yearObj['AcademicPeriod']['end_date'];
		
			$InstitutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');
			$staffOptions = $InstitutionSiteStaff->getInstitutionSiteStaffOptions($institutionSiteId, $startDate, $endDate);

			$gradeData = $InstitutionSiteSection->getGradeOptions($selectedSection);

			$GradeSubject = ClassRegistry::init('EducationGradeSubject');
			$subjectData = $GradeSubject->find('list', array(
				'fields' => array('EducationSubject.id', 'EducationSubject.name'),
				'contain' => array('EducationSubject'),
				'conditions' => array(
					'EducationGradeSubject.education_grade_id' => array_keys($gradeData)
				),
				'order' => array('EducationSubject.order')
			));
			
			$this->setVar(compact('sections', 'selectedAcademicPeriod', 'academicPeriodOptions', 'institutionSiteId', 'sectionOptions', 'selectedSection', 'staffOptions', 'subjectData'));
			
			if($this->controller->request->is('post') || $this->controller->request->is('put')) {
				$data = $this->controller->request->data;
				$tData = array();
				if (array_key_exists('InstitutionSiteClass', $data)) {
					foreach ($data['InstitutionSiteClass'] as $key => $value) {
						if ($value['status'] <= 0) {
							unset($data['InstitutionSiteClass'][$key]);
						} else {
							array_push($tData, $value);
						}
					}
				}
				$data = $tData;
				$result = $this->saveAll($data);

				if ($result) {
					$this->Message->alert('general.add.success');
					return $this->redirect(array('action' => $this->alias, 'index',$selectedAcademicPeriod));
				} else {
					pr($data);
					pr($this->validationErrors);
				}
			}
		} else {
			$this->Message->alert('InstitutionSite.noProgramme');
			return $controller->redirect(array('action' => $this->alias));
		}
	}
	
	public function view($institutionSiteClassId = null) {
		$id = $institutionSiteClassId;
		$this->Session->write($this->alias.'.id', $id);
		$data = $this->findById($id);
		
		if (!empty($data)) {
			$className = $data[$this->alias]['name'];
			$this->Navigation->addCrumb($className);
			$sections = $this->InstitutionSiteSectionClass->getSectionsByClass($id);
			
			$staffData = array();
			$studentsData = array();
			
			$this->setVar(compact('data', 'sections', 'staffData', 'studentsData'));
			$this->setVar('actionOptions', $this->getClassActions($id));
		} else {
			$this->Message->alert('general.notExists');
			$controller->redirect(array('action' => $this->_action));
		}
	}

	public function edit($institutionSiteClassId = null) {
		$id = $institutionSiteClassId;
		$data = $this->findById($id);

		if (!empty($data)) {
			if($this->controller->request->is('post') || $this->controller->request->is('put')) {
				$postData = $this->controller->request->data;
				//pr($postData);die;
				if ($this->saveAll($postData)) {
					$this->Message->alert('general.edit.success');
					$controller->redirect(array('action' => $this->_action . 'View', $id));
				}
				
				$this->controller->request->data['AcademicPeriod']['name'] = $data['AcademicPeriod']['name'];
			} else {
				$this->controller->request->data = $data;
			}
			
			$sections = $this->InstitutionSiteSectionClass->getAvailableSectionsForClass($id);
			//pr($sections);
			
			$staffData = array();
			$studentsData = array();
			$this->setVar(compact('staffData', 'studentsData'));
			
			$this->setVar('sections', $sections);
			
			$name = $data[$this->alias]['name'];
			$this->Navigation->addCrumb($name);
		} else {
			$this->Message->alert('general.notExists');
			$controller->redirect(array('action' => $this->_action));
		}
	}

	public function classesDelete($controller, $params) {
		$id = $params->pass[0];
		$obj = $this->findById($id);
		$this->delete($id);
		$this->Message->alert('general.delete.success');
		$controller->redirect(array('action' => $this->_action, $obj[$this->alias]['academic_period_id']));
	}

	public function excel($controller, $params) {
		$this->excel();
	}
	
	public function getClass($classId, $institutionSiteId=0) {
		$conditions = array('InstitutionSiteClass.id' => $classId);
		
		if($institutionSiteId > 0) {
			$conditions['InstitutionSiteClass.institution_site_id'] = $institutionSiteId;
		}
		
		$obj = $this->find('first', array('conditions' => $conditions));
		return $obj;
	}
	
	public function getListOfClasses($academicPeriodId, $institutionSiteId, $institutionSiteSectionId) {
		$classes = $this->find('all', array(
			'recursive' => -1,
			'contain' => array(
				'Staff',
				'EducationSubject' => array('name')
			),
			'conditions' => array(
				'InstitutionSiteClass.academic_period_id' => $academicPeriodId,
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId,
				'InstitutionSiteClass.institution_site_section_id' => $institutionSiteSectionId
			),
			'order' => array('InstitutionSiteClass.name')
		));
		$data = array();
		foreach ($classes as $key => $value) {
			$currId = $value['InstitutionSiteClass']['id'];
			$data[$currId] = array(
				'name' => $value['InstitutionSiteClass']['name'],
				'sections' => $this->InstitutionSiteSectionClass->getSectionsByClass($currId),
				'gender' => $this->InstitutionSiteClassStudent->getGenderTotalByClass($currId)
			);
			$data[$currId]['staffName'] = ModelHelper::getName($value['Staff']);
			$data[$currId]['educationSubjectName'] = (array_key_exists('name', $value['EducationSubject']))? $value['EducationSubject']['name']: '';
		}
		return $data;
	}
	
	public function getClassOptions($academicPeriodId, $institutionSiteId, $gradeId=false) {
		$options = array(
			'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name'),
			'conditions' => array(
				'InstitutionSiteClass.academic_period_id' => $academicPeriodId,
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId
			),
			'order' => array('InstitutionSiteClass.name')
		);
		
		if($gradeId!==false) {
			$options['joins'] = array(
				array(
					'table' => 'institution_site_section_grades',
					'alias' => 'InstitutionSiteSectionGrade',
					'conditions' => array(
						'InstitutionSiteSectionGrade.institution_site_section_id = InstitutionSiteClass.institution_site_section_id',
						'InstitutionSiteSectionGrade.education_grade_id = ' . $gradeId,
						'InstitutionSiteSectionGrade.status = 1'
					)
				)
			);
			$options['group'] = array('InstitutionSiteClass.id');
		}
		
		$data = $this->find('list', $options);
		return $data;
	}
		
	public function getClassListByInstitution($institutionSiteId, $academicPeriodId=0) {
		$options = array();
		$options['fields'] = array('InstitutionSiteClass.id', 'InstitutionSiteClass.name');
		$options['order'] = array('InstitutionSiteClass.name');
		$options['conditions'] = array('InstitutionSiteClass.institution_site_id' => $institutionSiteId);
		
		if (!empty($academicPeriodId)) {
			$options['conditions']['InstitutionSiteClass.academic_period_id'] = $academicPeriodId;
		}
		
		$data = $this->find('list', $options);
		return $data;
	}
	
	public function getClassListWithAcademicPeriod($institutionSiteId, $academicPeriodId, $assessmentId){
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name', 'AcademicPeriod.name'),
			'joins' => array(
				array(
					'table' => 'academic_periods',
					'alias' => 'AcademicPeriod',
					'conditions' => array('InstitutionSiteClass.academic_period_id = AcademicPeriod.id')
				),
				array(
					'table' => 'institution_site_section_classes',
					'alias' => 'InstitutionSiteSectionClass',
					'conditions' => array(
						'InstitutionSiteSectionClass.institution_site_class_id = InstitutionSiteClass.id',
						'InstitutionSiteSectionClass.status = 1'
					)
				),
				array(
					'table' => 'institution_site_section_grades',
					'alias' => 'InstitutionSiteSectionGrade',
					'conditions' => array(
						'InstitutionSiteSectionGrade.institution_site_section_id = InstitutionSiteSectionClass.institution_site_section_id'
					)
				),
				array(
					'table' => 'assessment_item_types',
					'alias' => 'AssessmentItemType',
					'conditions' => array(
						'AssessmentItemType.education_grade_id = InstitutionSiteSectionGrade.education_grade_id',
						'AssessmentItemType.id' => $assessmentId
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId,
				'InstitutionSiteClass.academic_period_id' => $academicPeriodId
			),
			'order' => array('AcademicPeriod.name, InstitutionSiteClass.name')
		));
		
		$result = array();
		foreach($data AS $row){
			$class = $row['InstitutionSiteClass'];
			$academicPeriod = $row['AcademicPeriod'];
			$result[$class['id']] = $academicPeriod['name'] . ' - ' . $class['name'];
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
}
