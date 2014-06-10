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
		'InstitutionSiteClassGrade',
		'InstitutionSiteClassStaff',
		'InstitutionSiteClassStudent',
		'InstitutionSiteClassSubject'
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
			)
		),
		'no_of_shifts' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter the number of shifts'
			),
			'numeric' => array(
				'rule' => 'numeric',
				'message' => 'Please select a numeric value'
			)
		)
	);
	
	public $actsAs = array(
		'CascadeDelete' => array(
			'cascade' => array(
				'InstitutionSiteClassGrade',
				'InstitutionSiteClassStaff'
			)
		),
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $reportMapping = array(
		1 => array(
			'fields' => array(
				'InstitutionSite' => array(
					'name' => 'Institution'
				),
				'SchoolYear' => array(
					'name' => 'School Year'
				),
				'InstitutionSiteClass' => array(
					'name' => 'Class Name',
					'no_of_seats' => 'Seats',
					'no_of_shifts' => 'Shift'
				)
			),
			'fileName' => 'Report_Class_List'
		),
		2 => array(
			'fields' => array(
				'SchoolYear' => array(
					'name' => 'School Year'
				),
				'InstitutionSiteClass' => array(
					'name' => 'Class Name'
				),
				'EducationGrade' => array(
					'name' => 'Grade'
				),
				'Student' => array(
					'identification_no' => 'OpenEMIS ID',
					'first_name' => 'First Name',
					'middle_name' => 'Middle Name',
					'last_name' => 'Last Name'
				),
				'StudentCategory' => array(
					'name' => 'Category'
				)
			),
			'fileName' => 'Report_Details_Classes_Students'
		)
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
				array('field' => 'no_of_shifts'),
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
		$yearOptions = $this->SchoolYear->getYearList();
		$selectedYear = isset($params->pass[0]) ? $params->pass[0] : key($yearOptions);
		$data = $this->getListOfClasses($selectedYear, $controller->institutionSiteId);
		
		$controller->set(compact('yearOptions', 'selectedYear', 'data'));
	}
	
	public function classesAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add Class');
		$shiftMax = intval(ClassRegistry::init('ConfigItem')->getValue('no_of_shifts'));
		$shiftOptions = array();
		if($shiftMax > 1){
			for($i=1; $i <= $shiftMax; $i++){
				$shiftOptions[$i] = $i;
			}
		}else{
			$shiftOptions[1] = 1;
		}
		$institutionSiteId = $controller->Session->read('InstitutionSiteId');
		$yearOptions = $this->SchoolYear->getAvailableYears();
		if(!empty($yearOptions)) {
			$selectedYear = isset($params->pass[0]) ? $params->pass[0] : key($yearOptions);
			$grades = $this->InstitutionSiteClassGrade->getAvailableGradesForNewClass($institutionSiteId, $selectedYear);
			$controller->set(compact('grades', 'selectedYear', 'yearOptions', 'shiftOptions', 'institutionSiteId'));
			
			if($controller->request->is('post') || $controller->request->is('put')) {
				$data = $controller->request->data;
				if(isset($data['InstitutionSiteClassGrade'])) {
					foreach($data['InstitutionSiteClassGrade'] as $i => $obj) {
						if(empty($obj['status'])) {
							unset($data['InstitutionSiteClassGrade'][$i]);
						}
					}
				}
				$result = $this->saveAll($data);
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => $this->_action, $selectedYear));
			}
		} else {
			$controller->Message->alert('SchoolYear.noAvailableYear');
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
			$grades = $this->InstitutionSiteClassGrade->getGradesByClass($id);
			$controller->set(compact('data', 'grades'));
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
				$this->saveAll($controller->request->data);
				$controller->Message->alert('general.edit.success');
				$controller->redirect(array('action' => $this->_action . 'View', $id));
			} else {
				$controller->request->data = $data;
				$grades = $this->InstitutionSiteClassGrade->getAvailableGradesForClass($id);
				$controller->set('grades', $grades);
			}
			
			$name = $data[$this->alias]['name'];
			$controller->Navigation->addCrumb($name);
			
			$shiftMax = intval(ClassRegistry::init('ConfigItem')->getValue('no_of_shifts'));
			$shiftOptions = array();
			if($shiftMax > 1){
				for($i=1; $i <= $shiftMax; $i++){
					$shiftOptions[$i] = $i;
				}
			}else{
				$shiftOptions[1] = 1;
			}
			$controller->set(compact('shiftOptions'));
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
				'grades' => $this->InstitutionSiteClassGrade->getGradesByClass($id),
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
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array(
						'InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id',
						'InstitutionSiteClassGrade.education_grade_id = ' . $gradeId
					)
				)
			);
			$options['group'] = array('InstitutionSiteClass.id');
		}
		$data = $this->find('list', $options);
		return $data;
	}
		
	public function getClassListByInstitution($institutionSiteId){
		$data = $this->find('list', array(
			'fields' => array('InstitutionSiteClass.id', 'InstitutionSiteClass.name'),
			'conditions' => array(
				'InstitutionSiteClass.institution_site_id' => $institutionSiteId
			),
			'order' => array('InstitutionSiteClass.name')
		));
		
		return $data;
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
	
	public function reportsGetHeader($args) {
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
			$options['order'] = array('SchoolYear.name', 'InstitutionSiteClass.name');
			$options['conditions'] = array('InstitutionSiteClass.institution_site_id' => $institutionSiteId);

			$options['joins'] = array(
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array(
						'InstitutionSiteClass.institution_site_id = InstitutionSite.id'
					)
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
				)
			);

			$data = $this->find('all', $options);

			return $data;
		} else if ($index == 2) {
			$options = array();
			$options['recursive'] = -1;
			$options['fields'] = $this->getCSVFields($this->reportMapping[$index]['fields']);
			$options['order'] = array('SchoolYear.name', 'InstitutionSiteClass.name', 'Student.first_name');
			$options['conditions'] = array('InstitutionSiteClass.institution_site_id' => $institutionSiteId);

			$options['joins'] = array(
				array(
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array('InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id')
				),
				array(
					'table' => 'institution_site_class_grade_students',
					'alias' => 'InstitutionSiteClassGradeStudent',
					'conditions' => array('InstitutionSiteClassGradeStudent.institution_site_class_grade_id = InstitutionSiteClassGrade.id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionSiteClassGrade.education_grade_id')
				),
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('Student.id = InstitutionSiteClassGradeStudent.student_id')
				),
				array(
					'table' => 'student_categories',
					'alias' => 'StudentCategory',
					'conditions' => array('StudentCategory.id = InstitutionSiteClassGradeStudent.student_category_id')
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array('SchoolYear.id = InstitutionSiteClass.school_year_id')
				)
			);

			$data = $this->find('all', $options);

			return $data;
		}
	}

	public function reportsGetFileName($args) {
		$index = $args[1];
		return $this->reportMapping[$index]['fileName'];
	}
}
