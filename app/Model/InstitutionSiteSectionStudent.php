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
		'HighChart' => array(
			'number_of_students_by_grade' => array(
				'_function' => 'getNumberOfStudentsByGrade',
				'chart' => array('type' => 'column', 'borderWidth' => 1),
				'xAxis' => array('title' => array('text' => 'Education Grades')),
				'yAxis' => array('title' => array('text' => 'Total'))
			)
		)
	);

	public $belongsTo = array(
		'Students.Student',
		'Students.StudentCategory',
		'InstitutionSiteSection',
		'EducationGrade'
	);

	// used by InstitutionSiteClass.edit
	public function getStudentOptions($sectionId) {
		$alias = $this->alias;
		$options = array(
			'contain' => array(
				'Student' => array(
					'SecurityUser'
				)
			),
			'conditions' => array(
				"$alias.institution_site_section_id" => $sectionId,
				"$alias.status" => 1
			)
		);

		$list = $this->find('all', $options);
		$data = array();
		foreach ($list as $obj) {
			$studentObj = $obj['Student'];
			$data[$studentObj['id']] = ModelHelper::getName($studentObj['SecurityUser'], array('openEmisId' => true));
		}
		return $data;
	}
	
	// used by StudentController.classes
	public function getListOfClassByStudent($studentId, $institutionSiteId = 0) {
		$fields = array('AcademicPeriod.name', 'EducationCycle.name', 'EducationProgramme.name', 'EducationGrade.name', 'InstitutionSiteClass.name');
		
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
				'table' => 'academic_periods',
				'alias' => 'AcademicPeriod',
				'conditions' => array('AcademicPeriod.id = InstitutionSiteClass.academic_period_id')
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
			'order' => array('AcademicPeriod.start_year DESC', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		$this->bindModel(array('belongsTo' => array('EducationGrade', 'InstitutionSiteClass')));
		return $data;
	}
	
	// used by InstitutionSiteClass.classes
	public function getGenderTotalBySection($sectionId) {
		$joins = array(
			array(
				'table' => 'students',
				'alias' => 'Student'
			)
		);

		$gender = array('M' => 0, 'F' => 0);
		$studentConditions = array('Student.id = InstitutionSiteSectionStudent.student_id');

		$data = $this->find(
			'all',
			array(
				'recursive' => -1,
				'fields' => array('SecurityUser.gender_id', 'Gender.name', 'COUNT(SecurityUser.gender_id) as counter'),
				'joins' => array(
					array(
						'table' => 'students',
						'alias' => 'Student',
						'conditions' => array('InstitutionSiteSectionStudent.student_id = Student.id')
					),
					array(
						'table' => 'security_users',
						'alias' => 'SecurityUser',
						'conditions' => array('Student.security_user_id = SecurityUser.id')
					),
					array(
						'table' => 'genders',
						'alias' => 'Gender',
						'conditions' => array('SecurityUser.gender_id = Gender.id')
					)
				),
				'conditions' => array(
					'InstitutionSiteSectionStudent.institution_site_section_id' => $sectionId
				),
				'group' => array('SecurityUser.gender_id')
			)
		);

		foreach ($data as $key => $value) {
			if ($value['Gender']['name'] == 'Female') {
				$gender['F'] = $value[0]['counter'];
			} else {
				$gender['M'] = $value[0]['counter'];
			}
		}
		return $gender;
	}

	public function getStudentsBySection($sectionId){
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT SecurityUser.openemis_no',
				'Student.id', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name', 
				'Gender.name', 'SecurityUser.date_of_birth',
				'StudentCategory.name'
			),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('InstitutionSiteSectionStudent.student_id = Student.id')
				),
				array(
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => array('Student.security_user_id = SecurityUser.id')
				),
				array(
					'table' => 'genders',
					'alias' => 'Gender',
					'conditions' => array('SecurityUser.gender_id = Gender.id')
				),
				array(
					'table' => 'field_option_values',
					'alias' => 'StudentCategory',
					'conditions' => array('InstitutionSiteSectionStudent.student_category_id = StudentCategory.id')
				)
			),
			'conditions' => array(
				'InstitutionSiteSectionStudent.institution_site_section_id' => $sectionId,
				'InstitutionSiteSectionStudent.status' => 1
			),
			'order' => array('SecurityUser.first_name ASC')
		));
		
		return $data;
	}
	
	public function getStudentsBySectionWithGrades($sectionId, $showGrade = false) {
		// $options['conditions'] = array(
		// 	'InstitutionSiteSectionStudent.institution_site_section_id' => $sectionId,
		// 	'InstitutionSiteSectionStudent.status = 1'
		// );
		
		// //$options['recursive'] =-1;
		// $options['fields'] = array(
		// 		'DISTINCT Student.id',
		// 		'SecurityUser.openemis_no',
		// 		'SecurityUser.first_name',
		// 		'SecurityUser.middle_name',
		// 		'SecurityUser.third_name',
		// 		'SecurityUser.last_name',
		// 		'SecurityUser.preferred_name'
		// 	);
		
		// if($showGrade){
		// 	$this->unbindModel(array('belongsTo' => array('Students.StudentCategory','InstitutionSiteSection')));
		// 	$options['fields'][] = 'EducationGrade.name';
		// }
		// else{
		// 	$this->unbindModel(array('belongsTo' => array('Students.StudentCategory','InstitutionSiteSection','EducationGrade')));
		// }
		
		// $data = $this->find('all', $options);

		$data = $this->find('all', 
			array(
				'recursive' => -1,
				'contain' => array(
					'Student' => array(
						'SecurityUser' => array('id', 'openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name')
					), 
					'EducationGrade' => array('name')
				),
				'conditions' => array(
					'InstitutionSiteSectionStudent.institution_site_section_id' => $sectionId,
					'InstitutionSiteSectionStudent.status = 1'
				)
			)
		);
		
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
			'Student.id', 'SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name',
			'AssessmentItemResult.id', 'AssessmentItemResult.marks', 'AssessmentItemResult.assessment_result_type_id',
			'AssessmentResultType.name', 'InstitutionSiteSection.academic_period_id',
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
					'AssessmentItemResult.academic_period_id = InstitutionSiteSection.academic_period_id',
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

		$options['order'] = array('SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name');

		$data = $this->find('all', $options);

		return $data;
	}
	
	public function getSectionStudents($sectionId, $startDate, $endDate){
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT Student.id',
				'SecurityUser.openemis_no',
				'SecurityUser.first_name',
				'SecurityUser.middle_name',
				'SecurityUser.third_name',
				'SecurityUser.last_name',
				'SecurityUser.preferred_name'
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
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => array(
						'Student.security_user_id = SecurityUser.id'
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
						// 'InstitutionSiteSection.institution_site_id = InstitutionSiteStudent.institution_site_id',
						// 'EducationGrade.education_programme_id = InstitutionSiteStudent.education_programme_id',
						// 'OR' => array(
						// 	array(
						// 		'InstitutionSiteStudent.start_date <= "' . $startDate . '"',
						// 		'InstitutionSiteStudent.end_date >= "' . $startDate . '"'
						// 	),
						// 	array(
						// 		'InstitutionSiteStudent.start_date <= "' . $endDate . '"',
						// 		'InstitutionSiteStudent.end_date >= "' . $endDate . '"'
						// 	),
						// 	array(
						// 		'InstitutionSiteStudent.start_date >= "' . $startDate . '"',
						// 		'InstitutionSiteStudent.end_date <= "' . $endDate . '"'
						// 	)
						// )
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteSectionStudent.institution_site_section_id' => $sectionId
			)
		));
		return $data;
	}
	
	public function getNumberOfStudentsByGrade($params=array()) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : array();
		$_conditions = array();
		foreach ($conditions as $key => $value) {
			$_conditions['InstitutionSiteSection.'.$key] = $value;
		}

		$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		$currentYearId = $AcademicPeriod->getCurrent();
		$currentYear = $AcademicPeriod->field('name', array('AcademicPeriod.id' => $currentYearId));

		$studentsByGradeConditions = array(
			'InstitutionSiteSectionStudent.status' => 1,
			'InstitutionSiteSection.academic_period_id' => $currentYearId,
			'EducationGrade.id IS NOT NULL',
			'Gender.name IS NOT NULL'
		);
		$studentsByGradeConditions = array_merge($studentsByGradeConditions, $_conditions);
		$studentByGrades = $this->find('all', array(
			'fields' => array(
				'InstitutionSiteSection.institution_site_id', 'EducationGrade.id', 'EducationGrade.name', 'Gender.name', 'COUNT(InstitutionSiteSectionStudent.id) AS total'
			),
			'contain' => array(),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'InstitutionSiteSectionStudent.education_grade_id = EducationGrade.id'
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
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array(
						'InstitutionSiteSectionStudent.student_id = Student.id'
					)
				),
				array(
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => array(
						'Student.security_user_id = SecurityUser.id'
					)
				),
				array(
					'table' => 'genders',
					'alias' => 'Gender',
					'conditions' => array(
						'SecurityUser.gender_id = Gender.id'
					)
				)
			),
			'conditions' => $studentsByGradeConditions,
			'group' => array(
				'InstitutionSiteSection.institution_site_id', 'EducationGrade.id', 'Gender.name'
			),
			'order' => array(
				'EducationGrade.order'
			)
		));

		$grades = array();
		
		$genderOptions = ClassRegistry::init('Gender')->getList();
		$dataSet = array();
		foreach ($genderOptions as $key => $value) {
			$dataSet[$value] = array('name' => __($value), 'data' => array());
		}

		foreach ($studentByGrades as $key => $studentByGrade) {
			$gradeId = $studentByGrade['EducationGrade']['id'];
			$gradeName = $studentByGrade['EducationGrade']['name'];
			$gradeGender = $studentByGrade['Gender']['name'];
			$gradeTotal = $studentByGrade[0]['total'];

			$grades[$gradeId] = $gradeName;

			foreach ($dataSet as $dkey => $dvalue) {
				if (!array_key_exists($gradeId, $dataSet[$dkey]['data'])) {
					$dataSet[$dkey]['data'][$gradeId] = 0;
				}
			}
			$dataSet[$gradeGender]['data'][$gradeId] = $gradeTotal;
		}

		$params['options']['subtitle'] = array('text' => 'For Year '. $currentYear);
		$params['options']['xAxis']['categories'] = array_values($grades);
		$params['dataSet'] = $dataSet;

		return $params;
	}

	public function autoInsertSectionStudent($data) {
		$studentId = $data['student_id'];
		$selectedGradeId = $data['education_grade_id'];
		$selectedSectionId = $data['institution_site_section_id'];
		$selectedStudentCategoryId = $data['student_category_id'];

		if($selectedSectionId != 0) {
			$institutionSiteSectionStudentId = $this->field('id', array(
				'InstitutionSiteSectionStudent.student_id' => $studentId,
				'InstitutionSiteSectionStudent.education_grade_id' => $selectedGradeId,
				'InstitutionSiteSectionStudent.institution_site_section_id' => $selectedSectionId
			));
			if($institutionSiteSectionStudentId) {
				$autoInsertData['InstitutionSiteSectionStudent']['id'] = $institutionSiteSectionStudentId;	
			}

			$autoInsertData['InstitutionSiteSectionStudent']['student_id'] = $studentId;
			$autoInsertData['InstitutionSiteSectionStudent']['education_grade_id'] = $selectedGradeId;
			$autoInsertData['InstitutionSiteSectionStudent']['institution_site_section_id'] = $selectedSectionId;
			$autoInsertData['InstitutionSiteSectionStudent']['student_category_id'] = $selectedStudentCategoryId;
			$autoInsertData['InstitutionSiteSectionStudent']['status'] = 1;

			$this->save($autoInsertData);
		}
	}
}
