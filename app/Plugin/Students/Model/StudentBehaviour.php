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

class StudentBehaviour extends StudentsAppModel {
	public $useTable = 'student_behaviours';
	
	public $actsAs = array(
		'Excel' => array('header' => array('Student' => array('SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.last_name'))),
		'ControllerAction2',
		'DatePicker' => array('date_of_behaviour'),
		'TimePicker' => array('time_of_behaviour' => array('format' => 'h:i a'))
	);

	public $validate = array(
		'title' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter a valid title'
			)
		),
		'student_behaviour_category_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please select an category'
			)
		)
	);
	
	public $belongsTo = array(
		'Students.Student',
		'InstitutionSite', 
		'Students.StudentBehaviourCategory',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id',
			'type' => 'LEFT'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id',
			'type' => 'LEFT'
	));

	/* Excel Behaviour */
	public function excelGetConditions() {
		if (!empty($this->controller)) { // via ControllerAction
			$id = CakeSession::read('InstitutionSite.id');
			$conditions = array('InstitutionSite.id' => $id);
		} else {
			$id = CakeSession::read('Student.id');
			$conditions = array('Student.id' => $id);
		}
		return $conditions;
	}
	/* End Excel Behaviour */
	
	public function beforeAction() {
		parent::beforeAction();
		
		$this->InstitutionSiteClass = ClassRegistry::init('InstitutionSiteClass');
		$this->InstitutionSiteSection = ClassRegistry::init('InstitutionSiteSection');
		
		$this->fields['institution_site_id']['type'] = 'hidden';
		$this->fields['institution_site_id']['value'] = $this->Session->read('InstitutionSite.id');
		$this->fields['student_action_category_id']['type'] = 'hidden';
		$this->fields['student_action_category_id']['value'] = 0;
		$this->fields['student_behaviour_category_id']['type'] = 'select';
		$this->fields['title']['labelKey'] = 'name';
		
		if ($this->action == 'add' || $this->action == 'edit' || $this->action == 'view') {
			if ($this->Session->check($this->alias.'.studentId')) {
				$studentId = $this->Session->read($this->alias.'.studentId');
				
				$this->Student->contain('SecurityUser');
				$obj = $this->Student->findById($studentId);
				
				$this->fields['student_name']['visible'] = true;
				$this->fields['student_name']['type'] = 'disabled';
				$this->fields['student_name']['value'] = ModelHelper::getName($obj['SecurityUser']);
				$this->fields['student_name']['order'] = 0;
				$this->setFieldOrder('student_name', 0);
				
				$this->fields['student_id']['type'] = 'hidden';
				$this->fields['student_id']['value'] = $studentId;
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => get_class($this), 'show'));
			}
		}
		$categoryOptions = array();
		if ($this->action = 'add' || $this->action = 'edit') {
			$categoryOptions = $this->StudentBehaviourCategory->getList();
		} else {
			$categoryOptions = $this->StudentBehaviourCategory->getList();
		}

		$this->fields['student_behaviour_category_id']['options'] = $categoryOptions;
		$this->setFieldOrder('student_behaviour_category_id', 1);
		$this->setFieldOrder('date_of_behaviour', 2);
		$this->setFieldOrder('time_of_behaviour', 3);
		
		$this->Navigation->addCrumb('Behaviour - Students');
	}
	
//	public function show($selectedAcademicPeriod=0, $selectedClass=0) {
//		$institutionSiteId = $this->Session->read('InstitutionSite.id');
//		$academicPeriodOptions = $this->InstitutionSiteClass->getAcademicPeriodOptions(array('InstitutionSiteClass.institution_site_id' => $institutionSiteId));
//		if (!empty($academicPeriodOptions)) {
//			if (empty($selectedAcademicPeriod) || (!empty($selectedAcademicPeriod) && !array_key_exists($selectedAcademicPeriod, $academicPeriodOptions))) {
//				$selectedAcademicPeriod = key($academicPeriodOptions);
//			}
//		}
//		$classOptions = $this->InstitutionSiteClass->getClassListByInstitution($institutionSiteId, $selectedAcademicPeriod);
//		if (!empty($classOptions)) {
//			if (empty($selectedClass) || (!empty($selectedClass) && !array_key_exists($selectedClass, $classOptions))) {
//				$selectedClass = key($classOptions);
//			}
//		}
//		$data = $this->InstitutionSiteClass->InstitutionSiteClassStudent->getStudentsByClass($selectedClass, true);
//		
//		if (empty($data)) {
//			$this->Message->alert('general.noData');
//		}
//		$this->Session->write($this->alias.'.selectedAcademicPeriod', $selectedAcademicPeriod);
//		$this->Session->write($this->alias.'.selectedClass', $selectedClass);
//		$this->setVar(compact('data', 'academicPeriodOptions', 'classOptions', 'selectedAcademicPeriod', 'selectedClass'));
//	}
	
	public function show($selectedAcademicPeriod=0, $selectedSection=0) {
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$academicPeriodOptions = $this->InstitutionSiteSection->getAcademicPeriodOptions(array('InstitutionSiteSection.institution_site_id' => $institutionSiteId));
		if (!empty($academicPeriodOptions)) {
			if (empty($selectedAcademicPeriod) || (!empty($selectedAcademicPeriod) && !array_key_exists($selectedAcademicPeriod, $academicPeriodOptions))) {
				$selectedAcademicPeriod = key($academicPeriodOptions);
			}
		}
		$sectionOptions = $this->InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $selectedAcademicPeriod);
		if (!empty($sectionOptions)) {
			if (empty($selectedSection) || (!empty($selectedSection) && !array_key_exists($selectedSection, $sectionOptions))) {
				$selectedSection = key($sectionOptions);
			}
		}
		$data = $this->InstitutionSiteSection->InstitutionSiteSectionStudent->getStudentsBySectionWithGrades($selectedSection, true);
		
		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		$this->Session->write($this->alias.'.selectedAcademicPeriod', $selectedAcademicPeriod);
		$this->Session->write($this->alias.'.selectedSection', $selectedSection);
		$this->setVar(compact('data', 'academicPeriodOptions', 'sectionOptions', 'selectedAcademicPeriod', 'selectedSection'));
	}
	
	public function index($studentId = 0) {
		if ($this->controller->name == 'InstitutionSites') {
			$institutionSiteId = $this->Session->read('InstitutionSite.id');
			
			if (empty($studentId)) {
				if ($this->Session->check($this->alias.'.studentId')) {
					$studentId = $this->Session->read($this->alias.'.studentId');
				} else {
					return $this->redirect(array('action' => get_class($this), 'show'));
				}
			}
			
			if ($this->Student->exists($studentId)) {
				$this->Session->write($this->alias.'.studentId', $studentId);
				$this->contain(array(
					'StudentBehaviourCategory' => array('fields' => array('StudentBehaviourCategory.name'))
				));
				$this->Student->contain();
				$student = $this->Student->findById($studentId);
				$data = $this->findAllByStudentIdAndInstitutionSiteId($studentId, $institutionSiteId, array(), array('StudentBehaviour.date_of_behaviour'));
				
				$selectedAcademicPeriod = $this->Session->read($this->alias.'.selectedAcademicPeriod');
				$selectedClass = $this->Session->read($this->alias.'.selectedClass');
				$this->setVar(compact('data', 'student', 'selectedAcademicPeriod', 'selectedClass'));
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => get_class($this), 'show'));
			}
		} else {
			$studentId = $this->Session->read('Student.id');
			
			$this->contain(array(
				'InstitutionSite' => array('fields' => array('InstitutionSite.name')), 
				'StudentBehaviourCategory' => array('fields' => array('StudentBehaviourCategory.name'))
			));
			$data = $this->findAllByStudentId($studentId, array(), array('StudentBehaviour.date_of_behaviour'));

			if(empty($data)){
				$this->Message->alert('general.noData');
			}

			$this->setVar(compact('data'));
		}
	}
}
