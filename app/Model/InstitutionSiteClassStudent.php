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

class InstitutionSiteClassStudent extends AppModel {
	public $actsAs = array(
		'Excel' => array('header' => array('Student' => array('openemis_no', 'first_name', 'last_name'))),
		'ControllerAction'
	);
	
	public $belongsTo = array(
		'Students.Student',
		'InstitutionSiteClass',
		'EducationGrade'
	);
	
	public $_action = 'classesStudent';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$id = $controller->Session->read('InstitutionSiteClass.id');
		
		if($this->InstitutionSiteClass->exists($id)) {
			$header = $this->InstitutionSiteClass->field('name', array('id' => $id));
			$controller->Navigation->addCrumb($header);
			$controller->set('header', $header);
			$controller->set('_action', $this->_action);
			$controller->set('selectedAction', $this->_action);
			$controller->set('actionOptions', $this->InstitutionSiteClass->getClassActions($controller));
		} else {
			if($action != 'getStudentAssessmentResults'){
				$controller->Message->alert('general.notExists');
				return $controller->redirect(array('action' => $this->InstitutionSiteClass->_action));
			}
		}
	}
	
	public function classesStudent($controller, $params) {
		$id = $controller->Session->read('InstitutionSiteClass.id');
		$studentActionOptions = ClassRegistry::init('InstitutionSiteSectionClass')->getSectionOptions($id, true);
		if(!empty($studentActionOptions)) {
			$selectedSection = isset($params->pass[0]) ? $params->pass[0] : key($studentActionOptions);
			$data = $this->find('all', array(
				'recursive' => -1,
				'fields' => array(
					'DISTINCT openemis_no',
					'SecurityUser.first_name', 'SecurityUser.last_name', 'SecurityUser.middle_name', 'SecurityUser.third_name'
				),
				'joins' => array(
					array(
						'table' => 'students',
						'alias' => 'Student',
						'conditions' => array('InstitutionSiteClassStudent.student_id = Student.id')
					),
					array(
						'table' => 'institution_site_section_students',
						'alias' => 'InstitutionSiteSectionStudent',
						'conditions' => array(
							'InstitutionSiteSectionStudent.student_id = InstitutionSiteClassStudent.student_id',
							'InstitutionSiteSectionStudent.institution_site_section_id = InstitutionSiteClassStudent.institution_site_section_id',
							'InstitutionSiteSectionStudent.status' => 1
						)
					)
				),
				'conditions' => array(
					'InstitutionSiteClassStudent.institution_site_class_id' => $id,
					'InstitutionSiteClassStudent.institution_site_section_id' => $selectedSection,
					'InstitutionSiteClassStudent.status' => 1
				),
				'order' => array('SecurityUser.first_name ASC')
			));
			if(empty($data)) {
				$controller->Message->alert('general.noData');
			}
			$controller->set(compact('data', 'studentActionOptions', 'selectedSection'));
		} else {
			$controller->Message->alert('InstitutionSiteClass.noSections');
		}
	}
	
	public function classesStudentEdit($controller, $params) {
		$id = $controller->Session->read('InstitutionSiteClass.id');
		$classId = $id;
		$studentActionOptions = ClassRegistry::init('InstitutionSiteSectionClass')->getSectionOptions($id, true);

		if ($controller->request->is('get')) {
			$selectedSection = isset($params->pass[0]) ? $params->pass[0] : key($studentActionOptions);
			$data = $this->Student->find('all', array(
				'recursive' => 0,
				'fields' => array(
					'Student.id', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name', 'SecurityUser.openemis_no',
					'InstitutionSiteClassStudent.id', 'InstitutionSiteClassStudent.institution_site_section_id', 'InstitutionSiteClassStudent.status'
				),
				'joins' => array(
					array(
						'table' => 'institution_site_section_students',
						'alias' => 'InstitutionSiteSectionStudent',
						'conditions' => array(
							'InstitutionSiteSectionStudent.student_id = Student.id',
							'InstitutionSiteSectionStudent.institution_site_section_id' => $selectedSection,
							'InstitutionSiteSectionStudent.status = 1'
						)
					),
					array(
						'table' => 'institution_site_class_students',
						'alias' => $this->alias,
						'type' => 'LEFT',
						'conditions' => array(
							$this->alias . '.student_id = InstitutionSiteSectionStudent.student_id',
							$this->alias . '.institution_site_class_id = ' . $id,
							$this->alias . '.institution_site_section_id = InstitutionSiteSectionStudent.institution_site_section_id'
						)
					)
				),
				'group' => array('Student.id'),
				'order' => array($this->alias . '.status DESC')
			));

			if (empty($data)) {
				$controller->Message->alert('general.noData');
			}
			
			if (empty($studentActionOptions)) {
				$controller->Message->alert('InstitutionSiteClass.noSections');
			}

			$controller->set(compact('data', 'studentActionOptions', 'selectedSection', 'classId'));
		} else {
			$data = $controller->request->data;
			$selectedSection = null;
			if (isset($data[$this->alias])) {
				foreach ($data[$this->alias] as $i => $obj) {
					$selectedSection = $obj['institution_site_section_id'];
					if (empty($obj['id']) && $obj['status'] == 0) {
						unset($data[$this->alias][$i]);
					}
				}
				if (!empty($data[$this->alias])) {
					$this->saveAll($data[$this->alias]);
				}
			}
			$controller->Message->alert('general.edit.success');
			return $controller->redirect(array('action' => $this->_action, $selectedSection));
		}
	}
	
	// used by StudentController.classes
	public function getListOfClassByStudent($studentId) {
		$fields = array("$this->alias.*", 'AcademicPeriod.name', 'InstitutionSite.name', 'InstitutionSiteSection.*', 'InstitutionSiteClass.*', 'EducationSubject.name');
		
		$joins = array(
			array(
				'table' => 'institution_site_sections',
				'alias' => 'InstitutionSiteSection',
				'conditions' => array(
					'InstitutionSiteSection.id = InstitutionSiteClassStudent.institution_site_section_id'
				)
			),
			array(
				'table' => 'institution_site_classes',
				'alias' => 'InstitutionSiteClass',
				'conditions' => array('InstitutionSiteClass.id = InstitutionSiteClassStudent.institution_site_class_id')
			),
			array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = InstitutionSiteClass.institution_site_id')
			),
			array(
					'table' => 'education_subjects',
					'alias' => 'EducationSubject',
					'conditions' => array(
						"EducationSubject.id = InstitutionSiteClass.education_subject_id"
					)
			),
			array(
				'table' => 'academic_periods',
				'alias' => 'AcademicPeriod',
				'conditions' => array('AcademicPeriod.id = InstitutionSiteClass.academic_period_id')
			)
		);
		$conditions = array($this->alias . '.student_id' => $studentId, $this->alias . '.status' => 1);

		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => $fields,
			'joins' => $joins,
			'conditions' => $conditions,
			'order' => array('AcademicPeriod.order')
		));
		
		return $data;
	}
	
	// used by InstitutionSiteClass.classes
	public function getGenderTotalByClass($classId) {
		$joins = array(
			array('table' => 'students', 'alias' => 'Student')
		);
		
		$conditions = array(
			'InstitutionSiteClassStudent.institution_site_class_id = ' . $classId,
			'InstitutionSiteClassStudent.status = 1' 
		);

		$gender = array('M' => 0, 'F' => 0);
		$studentConditions = array('Student.id = InstitutionSiteClassStudent.student_id');
		
		foreach ($gender as $i => $val) {
			$studentConditions[1] = sprintf("SecurityUser.gender = '%s'", $i);
			$joins[0]['conditions'] = $studentConditions;
			$gender[$i] = $this->find('count', array(
				'recursive' => -1, 
				'joins' => $joins, 
				'conditions' => $conditions
			));
		}
		return $gender;
	}
	
	public function getStudentsByClass($classId, $showGrade = false) {
		$options['conditions'] = array(
			'InstitutionSiteClassStudent.institution_site_class_id' => $classId,
			'InstitutionSiteClassStudent.status = 1'
		);
		
		//$options['recursive'] =-1;
		$options['fields'] = array(
				'DISTINCT Student.id',
				'SecurityUser.openemis_no',
				'SecurityUser.first_name',
				'SecurityUser.middle_name',
				'SecurityUser.third_name',
				'SecurityUser.last_name',
				'SecurityUser.preferred_name'
			);
		
		if($showGrade){
			$this->unbindModel(array('belongsTo' => array('InstitutionSiteClass')));
			$options['fields'][] = 'EducationGrade.name';
		}
		else{
			$this->unbindModel(array('belongsTo' => array('InstitutionSiteClass','EducationGrade')));
		}
		
		$data = $this->find('all', $options);
		
		/*$conditions = array(
			'InstitutionSiteClassStudent.institution_site_class_id' => $classId
		);

		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT Student.id',
				'SecurityUser.openemis_no',
				'SecurityUser.first_name',
				'SecurityUser.middle_name',
				'SecurityUser.last_name',
				'SecurityUser.preferred_name'
			),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('InstitutionSiteClassStudent.student_id = Student.id')
				)
			),
			'conditions' => $conditions
		));
*/
		return $data;
	}
	
	public function getAutoCompleteList($search, $classId) {
		$search = sprintf('%%%s%%', $search);

		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT Student.id', 'Student.*'),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('InstitutionSiteClassStudent.student_id = Student.id')
				)
			),
			'conditions' => array(
				'InstitutionSiteClassStudent.institution_site_class_id' => $classId,
				'OR' => array(
					'SecurityUser.first_name LIKE' => $search,
					'SecurityUser.last_name LIKE' => $search,
					'SecurityUser.middle_name LIKE' => $search,
					'SecurityUser.third_name LIKE' => $search,
					'SecurityUser.preferred_name LIKE' => $search,
					'openemis_no LIKE' => $search
				)
			),
			'order' => array('SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name', 'SecurityUser.preferred_name')
		));

		$data = array();
		foreach ($list as $obj) {
			$student = $obj['Student'];
			$data[] = array(
				'label' => ModelHelper::getName($student, array('openEmisId'=>true)),
				'value' => $student['id']
			);
		}
		return $data;
	}

	public function isStudentInClass($institutionSiteId, $classId, $studentId) {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT InstitutionSiteClassStudent.id'),
			'joins' => array(
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteClassStudent.institution_site_class_id = InstitutionSiteClass.id',
						'InstitutionSiteClass.institution_site_id' => $institutionSiteId
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteClassStudent.student_id' => $studentId,
				'InstitutionSiteClassStudent.institution_site_class_id' => $classId
			)
		));

		if (count($data) > 0) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getStudentAssessmentResults($classId, $itemId, $assessmentId = null) {
		$options['recursive'] = -1;
		
		$options['fields'] = array(
			'DISTINCT Student.id', 'SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.last_name',
			'AssessmentItemResult.id', 'AssessmentItemResult.marks', 'AssessmentItemResult.assessment_result_type_id',
			'AssessmentResultType.name', 'InstitutionSiteClass.academic_period_id',
			'AssessmentItem.min', 'AssessmentItem.max', 'AssessmentResultType.name'
		);

		$options_joins = array(
			array(
				'table' => 'students',
				'alias' => 'Student',
				'conditions' => array('Student.id = InstitutionSiteClassStudent.student_id')
			),
			array(
				'table' => 'institution_site_classes',
				'alias' => 'InstitutionSiteClass',
				'conditions' => array(
					'InstitutionSiteClass.id = InstitutionSiteClassStudent.institution_site_class_id',
					'InstitutionSiteClassStudent.institution_site_class_id' => $classId
				)
			),
			array(
				'table' => 'assessment_item_results',
				'alias' => 'AssessmentItemResult',
				'type' => 'LEFT',
				'conditions' => array(
					'AssessmentItemResult.student_id = Student.id',
					'AssessmentItemResult.institution_site_id = InstitutionSiteClass.institution_site_id',
					'AssessmentItemResult.academic_period_id = InstitutionSiteClass.academic_period_id',
					'AssessmentItemResult.assessment_item_id = ' . $itemId
				)
			),
			array(
				'table' => 'assessment_items',
				'alias' => 'AssessmentItem',
				'type' => 'LEFT',
				'conditions' => array('AssessmentItem.id = AssessmentItemResult.assessment_item_id')
			),
			array(
				'table' => 'field_option_values',
				'alias' => 'AssessmentResultType',
				'type' => 'LEFT',
				'conditions' => array('AssessmentResultType.id = AssessmentItemResult.assessment_result_type_id')
			)
		);

		if (!empty($assessmentId)) {
			$join_to_assessment_item_types = array(
				array(
					'table' => 'institution_site_section_students',
					'alias' => 'InstitutionSiteSectionStudent',
					'conditions' => array(
						'InstitutionSiteSectionStudent.student_id = InstitutionSiteClassStudent.student_id',
						'InstitutionSiteSectionStudent.institution_site_section_id = InstitutionSiteClassStudent.institution_site_section_id',
						'InstitutionSiteSectionStudent.status = 1'
					)
				),
				array(
					'table' => 'assessment_item_types',
					'alias' => 'AssessmentItemType',
					'conditions' => array(
						'AssessmentItemType.education_grade_id = InstitutionSiteSectionStudent.education_grade_id',
						'AssessmentItemType.id = ' . $assessmentId
					)
				)
			);

			$options['joins'] = array_merge($options_joins, $join_to_assessment_item_types);
		} else {
			$options['joins'] = $options_joins;
		}

		$options['order'] = array('SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name');
		$options['conditions'] = array('InstitutionSiteClassStudent.status = 1');

		$data = $this->find('all', $options);

		return $data;
	}
	
	public function getStudentsByClassAssessment($classId, $assessmentId) {
		$options['recursive'] = -1;
		
		$options['fields'] = array(
			'DISTINCT Student.id', 'SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.last_name'
		);

		$options['joins'] = array(
			array(
				'table' => 'students',
				'alias' => 'Student',
				'conditions' => array('Student.id = InstitutionSiteClassStudent.student_id')
			),
			array(
				'table' => 'institution_site_section_students',
				'alias' => 'InstitutionSiteSectionStudent',
				'conditions' => array(
					'InstitutionSiteSectionStudent.student_id = InstitutionSiteClassStudent.student_id',
					'InstitutionSiteSectionStudent.institution_site_section_id = InstitutionSiteClassStudent.institution_site_section_id',
					'InstitutionSiteSectionStudent.status = 1'
				)
			),
			array(
				'table' => 'assessment_item_types',
				'alias' => 'AssessmentItemType',
				'conditions' => array(
					'AssessmentItemType.education_grade_id = InstitutionSiteSectionStudent.education_grade_id',
					'AssessmentItemType.id = ' . $assessmentId
				)
			)
		);
		
		$options['order'] = array('SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.last_name');

		$options['conditions'] = array(
			'InstitutionSiteClassStudent.status = 1',
			'InstitutionSiteClassStudent.institution_site_class_id' => $classId
		);

		$data = $this->find('all', $options);

		return $data;
	}
	
}
