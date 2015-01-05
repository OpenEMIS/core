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

class InstitutionSiteSectionStudent extends AppModel {
	public $actsAs = array(
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'Students.Student',
		'Students.StudentCategory',
		'InstitutionSiteSection',
		'EducationGrade'
	);
	
	public function beforeAction() {
		parent::beforeAction();
		$id = $this->Session->read('InstitutionSiteSection.id');
		
		if($this->InstitutionSiteSection->exists($id)) {
			$header = $this->InstitutionSiteSection->field('name', array('id' => $id));
			$this->Navigation->addCrumb($header);
			$this->setVar('header', $header);
			$this->setVar('selectedAction', $this->alias . '/index');
			$currentSectionId = $this->Session->read('InstitutionSiteSection.id');
			$this->setVar('actionOptions', $this->InstitutionSiteSection->getSectionActions($currentSectionId));
		} else {
			if($action != 'getStudentAssessmentResults'){
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => $this->alias, 'index'));
			}
		}
	}
	
	public function index($selectedGrade=0) {
		$id = $this->Session->read('InstitutionSiteSection.id');
		$studentActionOptions = ClassRegistry::init('InstitutionSiteSectionGrade')->getGradeOptions($id, true);
		
		if(!empty($studentActionOptions)){
			if ($selectedGrade != 0) {
				if (!array_key_exists($selectedGrade, $studentActionOptions)) {
					$selectedGrade = key($studentActionOptions);
				}
			} else {
				$selectedGrade = key($studentActionOptions);
			}
		}
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT Student.identification_no',
				'Student.first_name', 'Student.last_name', 'StudentCategory.name'
			),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('InstitutionSiteSectionStudent.student_id = Student.id')
				),
				array(
					'table' => 'field_option_values',
					'alias' => 'StudentCategory',
					'conditions' => array('InstitutionSiteSectionStudent.student_category_id = StudentCategory.id')
				)
			),
			'conditions' => array(
				'InstitutionSiteSectionStudent.institution_site_section_id' => $id,
				'InstitutionSiteSectionStudent.education_grade_id' => $selectedGrade,
				'InstitutionSiteSectionStudent.status' => 1
			),
			'order' => array('Student.first_name ASC')
		));
		
		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		
		if (empty($studentActionOptions)) {
			$this->Message->alert('InstitutionSiteSection.noGrades');
		}
		
		$this->setVar(compact('data', 'studentActionOptions', 'selectedGrade'));
	}
	
	public function edit($selectedGrade=0) {
		$id = $this->Session->read('InstitutionSiteSection.id');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$studentActionOptions = ClassRegistry::init('InstitutionSiteSectionGrade')->getGradeOptions($id, true);
		
		if(!empty($studentActionOptions)){
			if ($selectedGrade != 0) {
				if (!array_key_exists($selectedGrade, $studentActionOptions)) {
					$selectedGrade = key($studentActionOptions);
				}
			} else {
				$selectedGrade = key($studentActionOptions);
			}
		}
		
		if($this->request->is('get')) {
			$categoryOptions = $this->StudentCategory->getList(1);
			$data = $this->Student->find('all', array(
				'recursive' => 0,
				'fields' => array(
					'Student.id', 'Student.first_name', 'Student.middle_name', 'Student.last_name', 'Student.identification_no',
					'InstitutionSiteSectionStudent.id', 'InstitutionSiteSectionStudent.student_category_id', 'InstitutionSiteSectionStudent.status', 'InstitutionSiteSection.id'
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
						'table' => 'institution_site_sections',
						'alias' => 'InstitutionSiteSection',
						'conditions' => array(
							'InstitutionSiteSection.institution_site_id = InstitutionSiteProgramme.institution_site_id',
							'InstitutionSiteSection.id' => $id,
						)
					),
					array(
						'table' => 'institution_site_section_grades',
						'alias' => 'InstitutionSiteSectionGrade',
						'conditions' => array(
							'InstitutionSiteSectionGrade.institution_site_section_id = InstitutionSiteSection.id',
							'InstitutionSiteSectionGrade.education_grade_id = EducationGrade.id',
							'InstitutionSiteSectionGrade.education_grade_id' => $selectedGrade
						)
					),
					array(
						'table' => 'school_years',
						'alias' => 'SchoolYear',
						'conditions' => array('SchoolYear.id = InstitutionSiteSection.school_year_id')
					),
					array(
						'table' => 'institution_site_section_students',
						'alias' => $this->alias,
						'type' => 'LEFT',
						'conditions' => array(
							$this->alias . '.student_id = InstitutionSiteStudent.student_id',
							$this->alias . '.institution_site_section_id = InstitutionSiteSection.id',
							$this->alias . '.education_grade_id' => $selectedGrade
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
				$this->Message->alert('general.noData');
			}
			
			if(empty($studentActionOptions)) {
				$this->Message->alert('InstitutionSiteSection.noGrades');
			}
			
			$this->setVar(compact('data', 'categoryOptions', 'studentActionOptions', 'selectedGrade'));
		} else {
			$data = $this->request->data;
			$selectedGrade = null;
			//pr($data);die;
 			if(isset($data[$this->alias])) {
				foreach($data[$this->alias] as $i => $obj) {
					$selectedGrade = $obj['education_grade_id'];
					if(empty($obj['id']) && $obj['status'] == 0) {
						unset($data[$this->alias][$i]);
					}
				}
				if(!empty($data[$this->alias])) {
					$this->saveAll($data[$this->alias]);
				}
			}
			$this->Message->alert('general.edit.success');
			return $this->redirect(array('action' => $this->alias, 'index'));
		}
	}
	
	// used by StudentController.classes
	public function getListOfClassByStudent($studentId, $institutionSiteId = 0) {
		$fields = array('SchoolYear.name', 'EducationCycle.name', 'EducationProgramme.name', 'EducationGrade.name', 'InstitutionSiteClass.name');
		
		$joins = array(
			array(
				'table' => 'institution_site_classes',
				'alias' => 'InstitutionSiteClass',
				'conditions' => array('InstitutionSiteClass.id = InstitutionSiteClassStudent.institution_site_class_id')
			),
			array(
				'table' => 'education_grades',
				'alias' => 'EducationGrade',
				'conditions' => array('EducationGrade.id = InstitutionSiteClassStudent.education_grade_id')
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
	public function getGenderTotalBySection($sectionId) {
		$joins = array(
			array(
				'table' => 'institution_site_section_grades',
				'alias' => 'InstitutionSiteSectionGrade',
				'conditions' => array(
					'InstitutionSiteSectionGrade.education_grade_id = InstitutionSiteSectionStudent.education_grade_id',
					'InstitutionSiteSectionGrade.institution_site_section_id = InstitutionSiteSectionStudent.institution_site_section_id',
					'InstitutionSiteSectionGrade.institution_site_section_id = ' . $sectionId
				)
			),
			array(
				'table' => 'students', 
				'alias' => 'Student'
			)
		);

		$gender = array('M' => 0, 'F' => 0);
		$studentConditions = array('Student.id = InstitutionSiteSectionStudent.student_id');
		
		foreach ($gender as $i => $val) {
			$studentConditions[1] = sprintf("Student.gender = '%s'", $i);
			$joins[1]['conditions'] = $studentConditions;
			$gender[$i] = $this->find('count', array(
				'recursive' => -1, 
				'joins' => $joins,
				'group' => array('Student.id'),
				'conditions' => array(
					'InstitutionSiteSectionStudent.status = 1'
				)
			));
		}
		return $gender;
	}
	
	public function getStudentsBySection($sectionId, $showGrade = false) {
		$options['conditions'] = array(
			'InstitutionSiteSectionStudent.institution_site_section_id' => $sectionId,
			'InstitutionSiteSectionStudent.status = 1'
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
			$this->unbindModel(array('belongsTo' => array('Students.StudentCategory','InstitutionSiteSection')));
			$options['fields'][] = 'EducationGrade.name';
		}
		else{
			$this->unbindModel(array('belongsTo' => array('Students.StudentCategory','InstitutionSiteSection','EducationGrade')));
		}
		
		$data = $this->find('all', $options);
		
		/*$conditions = array(
			'InstitutionSiteClassStudent.institution_site_class_id' => $sectionId
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
	
	// used by InstitutionSiteStudent
	public function getRecordIdsByStudentIdAndSiteId($studentId, $InstitutionSiteId) {
		$data = $this->find('list', array(
			'fields' => array('InstitutionSiteClassStudent.id'),
			'joins' => array(
				array(
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array(
						'InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClassStudent.institution_site_class_id'
					)
				),
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteClass.id = InstitutionSiteClassGrade.institution_site_class_id',
						'InstitutionSiteClass.institution_site_id = ' . $InstitutionSiteId
					)
				)
			),
			'conditions' => array('InstitutionSiteClassStudent.student_id = ' . $studentId)
		));
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
	
	public function getStudentAssessmentResults($sectionId, $itemId, $assessmentId = null) {
		$options['recursive'] = -1;
		
		$options['fields'] = array(
			'Student.id', 'Student.identification_no', 'Student.first_name', 'Student.middle_name', 'Student.last_name',
			'AssessmentItemResult.id', 'AssessmentItemResult.marks', 'AssessmentItemResult.assessment_result_type_id',
			'AssessmentResultType.name', 'InstitutionSiteSection.school_year_id',
			'AssessmentItem.min', 'AssessmentItem.max'
		);

		$options_joins = array(
			array(
				'table' => 'students',
				'alias' => 'Student',
				'conditions' => array('Student.id = InstitutionSiteSectionStudent.student_id')
			),
			array(
				'table' => 'institution_site_sections',
				'alias' => 'InstitutionSiteSection',
				'conditions' => array(
					'InstitutionSiteSection.id = InstitutionSiteSectionStudent.institution_site_section_id',
					'InstitutionSiteSectionStudent.institution_site_section_id' => $sectionId
				)
			),
			array(
				'table' => 'assessment_item_results',
				'alias' => 'AssessmentItemResult',
				'type' => 'LEFT',
				'conditions' => array(
					'AssessmentItemResult.student_id = Student.id',
					'AssessmentItemResult.institution_site_id = InstitutionSiteSection.institution_site_id',
					'AssessmentItemResult.school_year_id = InstitutionSiteSection.school_year_id',
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

		$data = $this->find('all', $options);

		return $data;
	}
	
	public function getSectionSutdents($sectionId, $startDate, $endDate){
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
					'conditions' => array(
						'InstitutionSiteSectionStudent.student_id = Student.id'
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
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'InstitutionSiteSectionStudent.education_grade_id = EducationGrade.id',
					)
				),
				array(
					'table' => 'institution_site_students',
					'alias' => 'InstitutionSiteStudent',
					'conditions' => array(
						'InstitutionSiteSectionStudent.student_id = InstitutionSiteStudent.student_id',
						'InstitutionSiteSection.institution_site_id = InstitutionSiteStudent.institution_site_id',
						'EducationGrade.education_programme_id = InstitutionSiteStudent.education_programme_id',
						'OR' => array(
							array(
								'InstitutionSiteStudent.start_date <= "' . $startDate . '"',
								'InstitutionSiteStudent.end_date >= "' . $startDate . '"'
							),
							array(
								'InstitutionSiteStudent.start_date <= "' . $endDate . '"',
								'InstitutionSiteStudent.end_date >= "' . $endDate . '"'
							),
							array(
								'InstitutionSiteStudent.start_date >= "' . $startDate . '"',
								'InstitutionSiteStudent.end_date <= "' . $endDate . '"'
							)
						)
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteSectionStudent.institution_site_section_id' => $sectionId
			)
		));
		
		return $data;
	}
	
}
