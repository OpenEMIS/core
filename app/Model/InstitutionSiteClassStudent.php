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
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $belongsTo = array(
		'Students.Student',
		'Students.StudentCategory',
		'InstitutionSiteClass',
		'EducationGrade'
	);
	
	public $_action = 'classesStudent';
	
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
					'name' => 'Class'
				),
				'EducationGrade' => array(
					'name' => 'Grade'
				),
				'AssessmentItemType' => array(
					'name' => 'Assessment'
				),
				'Student' => array(
					'identification_no' => 'Student OpenEMIS ID',
					'first_name' => '',
					'middle_name' => '',
					'last_name' => '',
					'preferred_name' => ''
				),
				'EducationSubject' => array(
					'Name' => 'Subject Name',
					'code' => 'Subject Code'
				),
				'AssessmentItemResult' => array(
					'marks' => 'Marks'
				),
				'AssessmentResultType' => array(
					'name' => 'Grading'
				)
			),
			'fileName' => 'Report_Student_Result'
		)
	);
	
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
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => $this->InstitutionSiteClass->_action));
		}
	}
	
	public function classesStudent($controller, $params) {
		$id = $controller->Session->read('InstitutionSiteClass.id');
		$studentActionOptions = ClassRegistry::init('InstitutionSiteClassGrade')->getGradeOptions($id, true);
		if(!empty($studentActionOptions)) {
			$selectedGrade = isset($params->pass[0]) ? $params->pass[0] : key($studentActionOptions);
			$data = $this->find('all', array(
				'conditions' => array(
					'institution_site_class_id' => $id,
					'education_grade_id' => $selectedGrade,
					'status' => 1
				),
				'order' => array('Student.first_name ASC')
			));
			if(empty($data)) {
				$controller->Message->alert('general.noData');
			}
			$controller->set(compact('data', 'studentActionOptions', 'selectedGrade'));
		} else {
			$controller->Message->alert('general.noData');
		}
	}
	
	public function classesStudentEdit($controller, $params) {
		$id = $controller->Session->read('InstitutionSiteClass.id');
		$selectedGrade = isset($params->pass[0]) ? $params->pass[0] : 0;
		$studentActionOptions = ClassRegistry::init('InstitutionSiteClassGrade')->getGradeOptions($id, true);
		if($controller->request->is('get')) {
			$categoryOptions = $this->StudentCategory->findList(true);
			$data = $this->Student->find('all', array(
				'recursive' => 0,
				'fields' => array(
					'Student.id', 'Student.first_name', 'Student.middle_name', 'Student.last_name', 'Student.identification_no',
					'InstitutionSiteClassStudent.id', 'InstitutionSiteClassStudent.student_category_id', 'InstitutionSiteClassStudent.status', 'InstitutionSiteClass.id'
				),
				'joins' => array(
					array(
						'table' => 'institution_site_students',
						'alias' => 'InstitutionSiteStudent',
						'conditions' => array('InstitutionSiteStudent.student_id = Student.id')
					),
					array(
						'table' => 'institution_site_programmes',
						'alias' => 'InstitutionSiteProgramme',
						'conditions' => array('InstitutionSiteProgramme.id = InstitutionSiteStudent.institution_site_programme_id')
					),
					array(
						'table' => 'education_grades',
						'alias' => 'EducationGrade',
						'conditions' => array('EducationGrade.education_programme_id = InstitutionSiteProgramme.education_programme_id')
					),
					array(
						'table' => 'institution_site_classes',
						'alias' => 'InstitutionSiteClass',
						'conditions' => array(
							'InstitutionSiteClass.institution_site_id = InstitutionSiteProgramme.institution_site_id',
							'InstitutionSiteClass.id = ' . $id
						)
					),
					array(
						'table' => 'institution_site_class_grades',
						'alias' => 'InstitutionSiteClassGrade',
						'conditions' => array(
							'InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id',
							'InstitutionSiteClassGrade.education_grade_id = EducationGrade.id'
						)
					),
					array(
						'table' => 'school_years',
						'alias' => 'SchoolYear',
						'conditions' => array('SchoolYear.id = InstitutionSiteClass.school_year_id')
					),
					array(
						'table' => 'institution_site_class_students',
						'alias' => $this->alias,
						'type' => 'LEFT',
						'conditions' => array(
							$this->alias . '.student_id = InstitutionSiteStudent.student_id',
							$this->alias . '.institution_site_class_id = InstitutionSiteClass.id'
						)
					)
				),
				'conditions' => array( // the class school year must be within the staff start and end date
					'OR' => array(
						'InstitutionSiteStudent.end_date IS NULL',
						'AND' => array(
							'InstitutionSiteStudent.start_year >= ' => 'SchoolYear.start_year',
							'InstitutionSiteStudent.end_year >= ' => 'SchoolYear.start_year'
						)
					)
				),
				'group' => array('Student.id'),
				'order' => array($this->alias.'.status DESC')
			));
			if(empty($data)) {
				$controller->Message->alert('general.noData');
			}
			$controller->set(compact('data', 'categoryOptions', 'studentActionOptions', 'selectedGrade'));
		} else {
			$data = $controller->request->data;
			if(isset($data[$this->alias])) {
				foreach($data[$this->alias] as $i => $obj) {
					if(empty($obj['id']) && $obj['status'] == 0) {
						unset($data[$this->alias][$i]);
					}
				}
				if(!empty($data[$this->alias])) {
					$this->saveAll($data[$this->alias]);
				}
			}
			$controller->Message->alert('general.edit.success');
			return $controller->redirect(array('action' => $this->_action));
		}
	}
	
	// used by InstitutionSiteClass.classes
	public function getGenderTotalByClass($classId) {
		$joins = array(
			array(
				'table' => 'institution_site_class_grades',
				'alias' => 'InstitutionSiteClassGrade',
				'conditions' => array(
					'InstitutionSiteClassGrade.education_grade_id = InstitutionSiteClassStudent.education_grade_id',
					'InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClassStudent.institution_site_class_id',
					'InstitutionSiteClassGrade.institution_site_class_id = ' . $classId
				)
			),
			array('table' => 'students', 'alias' => 'Student')
		);

		$gender = array('M' => 0, 'F' => 0);
		$studentConditions = array('Student.id = InstitutionSiteClassStudent.student_id');
		
		foreach ($gender as $i => $val) {
			$studentConditions[1] = sprintf("Student.gender = '%s'", $i);
			$joins[1]['conditions'] = $studentConditions;
			$gender[$i] = $this->find('count', array('recursive' => -1, 'joins' => $joins));
		}
		return $gender;
	}
	
	public function reportsGetHeader($args) {
		//$institutionSiteId = $args[0];
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
			$options['order'] = array('SchoolYear.name', 'InstitutionSiteClass.name', 'EducationGrade.name', 'AssessmentItemType.name', 'EducationSubject.name', 'Student.identification_no');
			$options['conditions'] = array();

			$options['joins'] = array(
				array(
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array('InstitutionSiteClassStudent.education_grade_id = InstitutionSiteClassGrade.education_grade_id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'InstitutionSiteClassGrade.education_grade_id = EducationGrade.id'
					)
				),
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteClassStudent.institution_site_class_id = InstitutionSiteClass.id',
						'InstitutionSiteClass.institution_site_id = ' . $institutionSiteId
					)
				),
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
				),
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('InstitutionSiteClassStudent.student_id = Student.id')
				),
				array(
					'table' => 'assessment_item_types',
					'alias' => 'AssessmentItemType',
					'conditions' => array('InstitutionSiteClassGrade.education_grade_id = AssessmentItemType.education_grade_id')
				),
				array(
					'table' => 'assessment_items',
					'alias' => 'AssessmentItem',
					'conditions' => array('AssessmentItem.assessment_item_type_id = AssessmentItemType.id')
				),
				array(
					'table' => 'education_grades_subjects',
					'alias' => 'EducationGradeSubject',
					'conditions' => array('AssessmentItem.education_grade_subject_id = EducationGradeSubject.id')
				),
				array(
					'table' => 'education_subjects',
					'alias' => 'EducationSubject',
					'conditions' => array('EducationGradeSubject.education_subject_id = EducationSubject.id')
				),
				array(
					'table' => 'assessment_item_results',
					'alias' => 'AssessmentItemResult',
					'type' => 'LEFT',
					'conditions' => array(
						'AssessmentItemResult.student_id = Student.id',
						'AssessmentItemResult.institution_site_id = InstitutionSiteClass.institution_site_id',
						'AssessmentItemResult.school_year_id = InstitutionSiteClass.school_year_id',
						'AssessmentItemResult.assessment_item_id = AssessmentItem.id'
					)
				),
				array(
					'table' => 'assessment_result_types',
					'alias' => 'AssessmentResultType',
					'type' => 'LEFT',
					'conditions' => array('AssessmentResultType.id = AssessmentItemResult.assessment_result_type_id')
				)
			);

			$data = $this->find('all', $options);

			return $data;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->reportMapping[$index]['fileName'];
	}
}
