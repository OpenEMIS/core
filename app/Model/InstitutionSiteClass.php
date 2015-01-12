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
		'SchoolYear',
		'InstitutionSite',
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
		'InstitutionSiteClassSubject',
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
		'school_year_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid school year'
			)
		),
		'no_of_seats' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter the number of seats'
			),
			'numeric' => array(
				'rule' => 'numeric',
				'message' => 'Please enter a numeric value'
			),
			'maxValue' => array(
                'rule'    => array('comparison', '<=', 100),
                'message' => 'Please enter a value between 0 and 100'
            ),
			'minValue' => array(
                'rule'    => array('comparison', '>=', 0),
                'message' => 'Please enter a value between 0 and 100'
            )
		)
	);
	
	public $actsAs = array(
		'ControllerAction',
		'SchoolYear'
	);
	
	public $_action = 'classes';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$controller->set('_action', $this->_action);
		$controller->set('selectedAction', $this->_action . 'View');
	}
	
	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'name', 'model' => 'SchoolYear'),
				array('field' => 'name'),
				array('field' => 'no_of_seats'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function getClassActions($controller, $id=0) {
		if($id==0) {
			$id = $controller->Session->read($this->alias.'.id');
		}
		$options = array(
			'classesView/'.$id => __('Class Details'),
			'classesStudent' => __('Students'),
			'classesStaff' => __('Staff'),
			'classesSubject' => __('Subjects')
		);
		return $options;
	}
	
	public function classes($controller, $params) {
		$controller->Navigation->addCrumb('List of Classes');
		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		$yearConditions = array(
			'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
			'InstitutionSiteProgramme.status' => 1,
			'SchoolYear.visible' => 1
		);
		$yearOptions = ClassRegistry::init('InstitutionSiteProgramme')->getYearOptions($yearConditions);
		$selectedYear = isset($params->pass[0]) ? $params->pass[0] : key($yearOptions);
		$data = $this->getListOfClasses($selectedYear, $institutionSiteId);
		
		if(empty($yearOptions)){
			$controller->Message->alert('InstitutionSite.noProgramme');
		}
		
		$controller->set(compact('yearOptions', 'selectedYear', 'data'));
	}
	
	public function classesAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add Class');
		
		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		$yearConditions = array(
			'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
			'InstitutionSiteProgramme.status' => 1,
			'SchoolYear.visible' => 1
		);
		$yearOptions = ClassRegistry::init('InstitutionSiteProgramme')->getYearOptions($yearConditions);
		if(!empty($yearOptions)) {
			$selectedYear = isset($params->pass[0]) ? $params->pass[0] : key($yearOptions);
			
			$sections = $this->InstitutionSiteSectionClass->getAvailableSectionsForNewClass($institutionSiteId, $selectedYear);
			$controller->set(compact('sections', 'selectedYear', 'yearOptions', 'institutionSiteId'));
			
			if($controller->request->is('post') || $controller->request->is('put')) {
				$data = $controller->request->data;
				if(isset($data['InstitutionSiteSectionClass'])) {
					foreach($data['InstitutionSiteSectionClass'] as $i => $obj) {
						if(empty($obj['status'])) {
							unset($data['InstitutionSiteSectionClass'][$i]);
						}
					}
				}
				$result = $this->saveAll($data);
				if ($result) {
					$controller->Message->alert('general.add.success');
					return $controller->redirect(array('action' => $this->_action, $selectedYear));
				}
			}
		} else {
			$controller->Message->alert('InstitutionSite.noProgramme');
			return $controller->redirect(array('action' => $this->_action));
		}
	}
	
	public function classesView($controller, $params) {
		$id = $controller->params['pass'][0];
		$controller->Session->write($this->alias.'.id', $id);
		$data = $this->findById($id);
		
		if (!empty($data)) {
			$className = $data[$this->alias]['name'];
			$controller->Navigation->addCrumb($className);
			$sections = $this->InstitutionSiteSectionClass->getSectionsByClass($id);
			//pr($sections);
			$controller->set(compact('data', 'sections'));
			$controller->set('actionOptions', $this->getClassActions($controller, $id));
		} else {
			$controller->Message->alert('general.notExists');
			$controller->redirect(array('action' => $this->_action));
		}
	}

	public function classesEdit($controller, $params) {
		$id = $params->pass[0];
		$data = $this->findById($id);

		if (!empty($data)) {
			if($controller->request->is('post') || $controller->request->is('put')) {
				$postData = $controller->request->data;
				//pr($postData);die;
				if ($this->saveAll($postData)) {
					$controller->Message->alert('general.edit.success');
					$controller->redirect(array('action' => $this->_action . 'View', $id));
				}
				
				$controller->request->data['SchoolYear']['name'] = $data['SchoolYear']['name'];
			} else {
				$controller->request->data = $data;
			}
			
			$sections = $this->InstitutionSiteSectionClass->getAvailableSectionsForClass($id);
			//pr($sections);
			$controller->set('sections', $sections);
			
			$name = $data[$this->alias]['name'];
			$controller->Navigation->addCrumb($name);
		} else {
			$controller->Message->alert('general.notExists');
			$controller->redirect(array('action' => $this->_action));
		}
	}

	public function classesDelete($controller, $params) {
		$id = $params->pass[0];
		$obj = $this->findById($id);
		$this->delete($id);
		$controller->Message->alert('general.delete.success');
		$controller->redirect(array('action' => $this->_action, $obj[$this->alias]['school_year_id']));
	}

	public function classesExcel($controller, $params) {
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
	
	public function getListOfClasses($yearId, $institutionSiteId) {
		$classes = $this->find('list', array(
			'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name'),
			'conditions' => array(
				'InstitutionSiteClass.school_year_id' => $yearId,
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId
			),
			'order' => array('InstitutionSiteClass.name')
		));
		
		$data = array();
		foreach($classes as $id => $name) {
			$data[$id] = array(
				'name' => $name,
				'sections' => $this->InstitutionSiteSectionClass->getSectionsByClass($id),
				'gender' => $this->InstitutionSiteClassStudent->getGenderTotalByClass($id)
			);
		}
		return $data;
	}
	
	public function getClassOptions($yearId, $institutionSiteId, $gradeId=false) {
		$options = array(
			'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name'),
			'conditions' => array(
				'InstitutionSiteClass.school_year_id' => $yearId,
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
		
	public function getClassListByInstitution($institutionSiteId, $yearId=0) {
		$options = array();
		$options['fields'] = array('InstitutionSiteClass.id', 'InstitutionSiteClass.name');
		$options['order'] = array('InstitutionSiteClass.name');
		$options['conditions'] = array('InstitutionSiteClass.institution_site_id' => $institutionSiteId);
		
		if (!empty($yearId)) {
			$options['conditions']['InstitutionSiteClass.school_year_id'] = $yearId;
		}
		
		$data = $this->find('list', $options);
		return $data;
	}
	
	public function getClassListWithYear($institutionSiteId, $schoolYearId, $assessmentId){
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name', 'SchoolYear.name'),
			'joins' => array(
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
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
				'InstitutionSiteClass.school_year_id' => $schoolYearId
			),
			'order' => array('SchoolYear.name, InstitutionSiteClass.name')
		));
		
		$result = array();
		foreach($data AS $row){
			$class = $row['InstitutionSiteClass'];
			$schoolYear = $row['SchoolYear'];
			$result[$class['id']] = $schoolYear['name'] . ' - ' . $class['name'];
		}
		
		return $result;
	}
	
	public function getClassListByInstitutionSchoolYear($institutionSiteId, $yearId){
		if(empty($yearId)){
			$conditions = array(
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId
			);
		}else{
			$conditions = array(
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId,
				'InstitutionSiteClass.school_year_id' => $yearId
			);
		}
		
		$data = $this->find('list', array(
			'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name'),
			'conditions' => $conditions,
			'order' => array('InstitutionSiteClass.name')
		));
		
		return $data;
	}
		
	public function getClassByIdSchoolYear($classId, $schoolYearId){
		$data = $this->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'InstitutionSiteClass.id' => $classId,
				'InstitutionSiteClass.school_year_id' => $schoolYearId
			)
		));
		
		return $data;
	}
}
