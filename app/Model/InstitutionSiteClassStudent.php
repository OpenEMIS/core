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
		'Excel' => array('header' => array('Student' => array('identification_no', 'first_name', 'last_name'))),
		'ControllerAction'
	);
	
	public $belongsTo = array(
		'Students.Student',
		'Students.StudentCategory',
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
					'DISTINCT Student.identification_no',
					'Student.first_name', 'Student.last_name'
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
				'order' => array('Student.first_name ASC')
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
					'Student.id', 'Student.first_name', 'Student.middle_name', 'Student.last_name', 'Student.identification_no',
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
	public function getListOfClassByStudent($studentId, $institutionSiteId = 0) {
		$fields = array('SchoolYear.name', 'EducationCycle.name', 'EducationProgramme.name', 'EducationGrade.name', 'InstitutionSiteClass.name', 'InstitutionSiteSection.name');
		
		$joins = array(
			array(
				'table' => 'institution_site_section_students',
				'alias' => 'InstitutionSiteSectionStudent',
				'conditions' => array(
					'InstitutionSiteClassStudent.student_id = InstitutionSiteSectionStudent.student_id',
					'InstitutionSiteClassStudent.institution_site_section_id = InstitutionSiteSectionStudent.institution_site_section_id',
					'InstitutionSiteSectionStudent.status = 1'
				)
			),
			array(
				'table' => 'institution_site_sections',
				'alias' => 'InstitutionSiteSection',
				'conditions' => array(
					'InstitutionSiteSectionStudent.institution_site_section_id = InstitutionSiteSection.id'
				)
			),
			array(
				'table' => 'institution_site_classes',
				'alias' => 'InstitutionSiteClass',
				'conditions' => array('InstitutionSiteClass.id = InstitutionSiteClassStudent.institution_site_class_id')
			),
			array(
				'table' => 'education_grades',
				'alias' => 'EducationGrade',
				'conditions' => array('InstitutionSiteSectionStudent.education_grade_id = EducationGrade.id')
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
			),
			array(
				'table' => 'school_years',
				'alias' => 'SchoolYear',
				'conditions' => array('SchoolYear.id = InstitutionSiteClass.school_year_id')
			)
		);
		$conditions = array($this->alias . '.student_id' => $studentId, $this->alias . '.status' => 1);

		if ($institutionSiteId == 0) {
			$fields[] = 'InstitutionSite.name';
			$joins[] = array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = InstitutionSiteClass.institution_site_id')
			);
		} else {
			$conditions['InstitutionSiteClass.institution_site_id'] = $institutionSiteId;
		}
		$this->unbindModel(array('belongsTo' => array('EducationGrade', 'InstitutionSiteClass')));
		$data = $this->find('all', array(
			'fields' => $fields,
			'joins' => $joins,
			'conditions' => $conditions,
			'order' => array('SchoolYear.start_year DESC', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		$this->bindModel(array('belongsTo' => array('EducationGrade', 'InstitutionSiteClass')));
		return $data;
	}
	
	// used by InstitutionSiteClass.classes
	public function getGenderTotalByClass($classId) {
		$joins = array(
			array(
				'table' => 'institution_site_section_students',
				'alias' => 'InstitutionSiteSectionStudent',
				'conditions' => array(
					'InstitutionSiteSectionStudent.student_id = InstitutionSiteClassStudent.student_id',
					'InstitutionSiteSectionStudent.institution_site_section_id = InstitutionSiteClassStudent.institution_site_section_id',
					'InstitutionSiteSectionStudent.status' => 1
				)
			),
			array('table' => 'students', 'alias' => 'Student')
		);
		
		$conditions = array(
			'InstitutionSiteClassStudent.institution_site_class_id = ' . $classId,
			'InstitutionSiteClassStudent.status = 1' 
		);

		$gender = array('M' => 0, 'F' => 0);
		$studentConditions = array('Student.id = InstitutionSiteClassStudent.student_id');
		
		foreach ($gender as $i => $val) {
			$studentConditions[1] = sprintf("Student.gender = '%s'", $i);
			$joins[1]['conditions'] = $studentConditions;
			$gender[$i] = $this->find('count', array(
				'recursive' => -1, 
				'joins' => $joins, 
				'conditions' => $conditions,
				'group' => array('Student.id')
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
				'Student.identification_no',
				'Student.first_name',
				'Student.middle_name',
				'Student.last_name',
				'Student.preferred_name'
			);
		
		if($showGrade){
			$this->unbindModel(array('belongsTo' => array('Students.StudentCategory','InstitutionSiteClass')));
			$options['fields'][] = 'EducationGrade.name';
		}
		else{
			$this->unbindModel(array('belongsTo' => array('Students.StudentCategory','InstitutionSiteClass','EducationGrade')));
		}
		
		$data = $this->find('all', $options);
		
		/*$conditions = array(
			'InstitutionSiteClassStudent.institution_site_class_id' => $classId
		);

		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT Student.id',
				'Student.identification_no',
				'Student.first_name',
				'Student.middle_name',
				'Student.last_name',
				'Student.preferred_name'
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
					'Student.first_name LIKE' => $search,
					'Student.last_name LIKE' => $search,
					'Student.middle_name LIKE' => $search,
					'Student.preferred_name LIKE' => $search,
					'Student.identification_no LIKE' => $search
				)
			),
			'order' => array('Student.first_name', 'Student.middle_name', 'Student.last_name', 'Student.preferred_name')
		));

		$data = array();
		foreach ($list as $obj) {
			$student = $obj['Student'];
			$data[] = array(
				'label' => sprintf('%s - %s %s %s %s', $student['identification_no'], $student['first_name'], $student['middle_name'], $student['last_name'], $student['preferred_name']),
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
			'Student.id', 'Student.identification_no', 'Student.first_name', 'Student.middle_name', 'Student.last_name',
			'AssessmentItemResult.id', 'AssessmentItemResult.marks', 'AssessmentItemResult.assessment_result_type_id',
			'AssessmentResultType.name', 'InstitutionSiteClass.school_year_id',
			'AssessmentItem.min', 'AssessmentItem.max'
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
					'AssessmentItemResult.school_year_id = InstitutionSiteClass.school_year_id',
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

		$options['order'] = array('Student.first_name', 'Student.middle_name', 'Student.last_name');
		$options['conditions'] = array('InstitutionSiteClassStudent.status = 1');

		$data = $this->find('all', $options);

		return $data;
	}
	
}
