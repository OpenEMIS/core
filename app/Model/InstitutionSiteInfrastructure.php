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

class InstitutionSiteInfrastructure extends AppModel {
	public $belongsTo = array(
		'InstitutionSite',
		'Infrastructure.InfrastructureCategory',
		'Infrastructure.InfrastructureType',
		'Infrastructure.InfrastructureOwnership',
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
	
	public $validate = array(
		'code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Code'
			)
		),
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Name'
			)
		),
		'infrastructure_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Type'
			)
		),
		'infrastructure_ownership_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Ownership'
			)
		)
	);
	
	public $actsAs = array(
		'ControllerAction2'
	);
	
	public function beforeAction() {
		parent::beforeAction();
	}
	
	public function index($categoryId=0) {
		$this->Navigation->addCrumb('Infrastructure');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$categoryOptions = $this->InfrastructureCategory->getCategoryOptions();
		
		if(!empty($categoryOptions)){
			if ($categoryId != 0) {
				if (!array_key_exists($categoryId, $categoryOptions)) {
					$categoryId = key($categoryOptions);
				}
			} else {
				$categoryId = key($categoryOptions);
			}
		}
		
		$parentCategory = $this->InfrastructureCategory->getParentCategory($categoryId);
		
		if(empty($categoryOptions)){
			$this->Message->alert('InstitutionSiteInfrastructure.noCategory');
		}
		
		$data = $this->getInfrastructureData($categoryId, $institutionSiteId);
		
		$this->setVar(compact('categoryOptions', 'categoryId', 'data', 'parentCategory'));
	}
	
	public function getInfrastructureData($categoryId, $institutionSiteId){
		$parentCategory = $this->InfrastructureCategory->getParentCategory($categoryId);
		if(!empty($parentCategory)){
			$fields = array('InstitutionSiteInfrastructure.*', 'InfrastructureType.*', 'Parent.name');
			
			$joins = array(
				array(
					'table' => 'institution_site_infrastructures',
					'alias' => 'Parent',
					'conditions' => array(
						'Parent.id = InstitutionSiteInfrastructure.parent_id'
					)
				)
			);
		}else{
			$fields = array('InstitutionSiteInfrastructure.*', 'InfrastructureType.*');
			$joins = array();
		}
		
		$data = $this->find('all', array(
			'fields' => $fields,
			'joins' => $joins,
			'conditions' => array(
				'InstitutionSiteInfrastructure.infrastructure_category_id' => $categoryId,
				'InstitutionSiteInfrastructure.institution_site_id' => $institutionSiteId
			)
		));
		
		return $data;
	}
	
	public function infrastructureOptionsByCategory($categoryId, $institutionSiteId){
		$data = $this->find('list', array(
			'conditions' => array(
				'InstitutionSiteInfrastructure.infrastructure_category_id' => $categoryId,
				'InstitutionSiteInfrastructure.institution_site_id' => $institutionSiteId
			)
		));
		
		return $data;
	}
	
	public function add($categoryId=0) {
		$this->Navigation->addCrumb('Add Infrastructure');
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$category = $this->InfrastructureCategory->findById($categoryId);
		
		if(empty($category)){
			return $this->redirect(array('action' => 'InstitutionSiteInfrastructure', 'index'));
		}
		
		$parentCategory = $this->InfrastructureCategory->getParentCategory($categoryId);
		if(!empty($parentCategory)){
			$parentInfraOptions = $this->infrastructureOptionsByCategory($parentCategory['InfrastructureCategory']['id'], $institutionSiteId);
		}else{
			$parentInfraOptions = array();
		}
		
		$typeOptions = $this->InfrastructureType->getTypeOptionsByCategory($categoryId);
		$yearOptions = $this->controller->DateTime->yearOptionsByConfig();
		$currentYear = Date('Y');
		$ownershipOptions = $this->InfrastructureOwnership->getList();
		
		$this->setVar(compact('categoryId', 'category', 'parentCategory', 'parentInfraOptions', 'typeOptions', 'yearOptions', 'currentYear', 'ownershipOptions'));
		
		if($this->request->is(array('post', 'put'))) {
			$postData = $this->request->data['InstitutionSiteInfrastructure'];
			$postData['institution_site_id'] = $institutionSiteId;
			$postData['infrastructure_category_id'] = $categoryId;
			
			if ($this->saveAll($postData)) {
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => 'InstitutionSiteInfrastructure', 'index', $categoryId));
			}
		}
	}
	
	public function view($id=0) {
		$this->Session->write($this->alias.'.id', $id);
		$data = $this->findById($id);
		
		if (!empty($data)) {
			$sectionName = $data[$this->alias]['name'];
			$this->Navigation->addCrumb($sectionName);
			$grades = $this->InstitutionSiteSectionGrade->getGradesBySection($id);
			$selectedAction = $this->alias . '/view/' . $id;
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
				
				$this->request->data['SchoolYear']['name'] = $data['SchoolYear']['name'];
			} else {
				$this->request->data = $data;
			}
			
			$grades = $this->InstitutionSiteSectionGrade->getAvailableGradesForSection($id);
			
			$name = $data[$this->alias]['name'];
			$this->Navigation->addCrumb($name);
			
			$InstitutionSiteShiftModel = ClassRegistry::init('InstitutionSiteShift');
			$shiftOptions = $InstitutionSiteShiftModel->getShiftOptions($institutionSiteId, $data['InstitutionSiteSection']['school_year_id']);
			
			$this->setVar(compact('grades', 'shiftOptions'));
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
		$this->redirect(array('action' => $this->alias, 'index', $obj[$this->alias]['school_year_id']));
	}
	
	public function getClass($classId, $institutionSiteId=0) {
		$conditions = array('InstitutionSiteClass.id' => $classId);
		
		if($institutionSiteId > 0) {
			$conditions['InstitutionSiteClass.institution_site_id'] = $institutionSiteId;
		}
		
		$obj = $this->find('first', array('conditions' => $conditions));
		return $obj;
	}

	public function getSectionOptions($yearId, $institutionSiteId, $gradeId=false) {
		$options = array(
			'fields' => array('InstitutionSiteSection.id', 'InstitutionSiteSection.name'),
			'conditions' => array(
				'InstitutionSiteSection.school_year_id' => $yearId,
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
	
	public function getListOfSections($yearId, $institutionSiteId) {
		$classes = $this->find('list', array(
			'fields' => array('InstitutionSiteSection.id', 'InstitutionSiteSection.name'),
			'conditions' => array(
				'InstitutionSiteSection.school_year_id' => $yearId,
				'InstitutionSiteSection.institution_site_id' => $institutionSiteId
			),
			'order' => array('InstitutionSiteSection.name')
		));
		
		$data = array();
		foreach($classes as $id => $name) {
			$data[$id] = array(
				'name' => $name,
				'grades' => $this->InstitutionSiteSectionGrade->getGradesBySection($id),
				'gender' => $this->InstitutionSiteSectionStudent->getGenderTotalBySection($id)
			);
		}
		return $data;
	}
	
	public function getSectionListByInstitution($institutionSiteId, $yearId=0) {
		$options = array();
		$options['fields'] = array('InstitutionSiteSection.id', 'InstitutionSiteSection.name');
		$options['order'] = array('InstitutionSiteSection.name');
		$options['conditions'] = array('InstitutionSiteSection.institution_site_id' => $institutionSiteId);
		
		if (!empty($yearId)) {
			$options['conditions']['InstitutionSiteSection.school_year_id'] = $yearId;
		}
		
		$data = $this->find('list', $options);
		return $data;
	}
	
	public function getSectionListWithYear($institutionSiteId, $schoolYearId, $assessmentId){
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('InstitutionSiteSection.id', 'InstitutionSiteSection.name', 'SchoolYear.name'),
			'joins' => array(
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array('InstitutionSiteSection.school_year_id = SchoolYear.id')
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
				'InstitutionSiteSection.school_year_id' => $schoolYearId
			),
			'order' => array('SchoolYear.name, InstitutionSiteSection.name')
		));
		
		$result = array();
		foreach($data AS $row){
			$section = $row['InstitutionSiteSection'];
			$schoolYear = $row['SchoolYear'];
			$result[$section['id']] = $schoolYear['name'] . ' - ' . $section['name'];
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
