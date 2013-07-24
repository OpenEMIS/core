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

class InstitutionSiteClassGradeStudent extends AppModel {
	
	// used by InstitutionSite classes
	public function getStudentsByGrade($gradeIds) {
		$data = $this->find('all', array(
			'fields' => array(
				'Student.id', 'Student.identification_no', 'Student.first_name', 'Student.last_name',
				'Student.telephone', 'InstitutionSiteClassGradeStudent.institution_site_class_grade_id',
				'StudentCategory.name'
			),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('Student.id = InstitutionSiteClassGradeStudent.student_id')
				),
				array(
					'table' => 'student_categories',
					'alias' => 'StudentCategory',
					'conditions' => array('StudentCategory.id = InstitutionSiteClassGradeStudent.student_category_id')
				)
			),
			'conditions' => array('InstitutionSiteClassGradeStudent.institution_site_class_grade_id' => $gradeIds),
			'order' => array('Student.first_name')
		));
		
		$list = array();
		foreach($data as $obj) {
			$gradeId = $obj['InstitutionSiteClassGradeStudent']['institution_site_class_grade_id'];
			if(!isset($list[$gradeId])) {
				$list[$gradeId] = array();
			}
			$list[$gradeId][] = array_merge($obj['Student'], array('category' => $obj['StudentCategory']['name']));
		}
		return $list;
	}
	
	public function getListOfClassByStudent($studentId, $institutionSiteId=0) {
		$fields = array('SchoolYear.name', 'EducationCycle.name', 'EducationProgramme.name', 'EducationGrade.name', 'InstitutionSiteClass.name');
		
		$joins = array(
			array(
				'table' => 'institution_site_class_grades',
				'alias' => 'InstitutionSiteClassGrade',
				'conditions' => array('InstitutionSiteClassGrade.id = InstitutionSiteClassGradeStudent.institution_site_class_grade_id')
			),
			array(
				'table' => 'institution_site_classes',
				'alias' => 'InstitutionSiteClass',
				'conditions' => array('InstitutionSiteClass.id = InstitutionSiteClassGrade.institution_site_class_id')
			),
			array(
				'table' => 'education_grades',
				'alias' => 'EducationGrade',
				'conditions' => array('EducationGrade.id = InstitutionSiteClassGrade.education_grade_id')
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
		
		$conditions = array('InstitutionSiteClassGradeStudent.student_id' => $studentId);
		
		if($institutionSiteId == 0) {
			$fields[] = 'Institution.name';
			$fields[] = 'InstitutionSite.name';
			$joins[] = array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = InstitutionSiteClass.institution_site_id')
			);
			$joins[] = array(
				'table' => 'institutions',
				'alias' => 'Institution',
				'conditions' => array('Institution.id = InstitutionSite.institution_id')
			);
		} else {
			$conditions['InstitutionSiteClass.institution_site_id'] = $institutionSiteId;
		}
		
		$data = $this->find('all', array(
			'fields' => $fields,
			'joins' => $joins,
			'conditions' => $conditions,
			'order' => array('SchoolYear.start_year DESC', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		return $data;
	}
	
	public function getStudentAssessmentResults($yearId, $institutionSiteId, $classId, $gradeId, $itemId) {
		$data = $this->find('all', array(
			'fields' => array(
				'Student.id', 'Student.identification_no', 'Student.first_name', 'Student.last_name',
				'AssessmentItemResult.id', 'AssessmentItemResult.marks', 'AssessmentItemResult.assessment_result_type_id',
				'AssessmentResultType.name'
			),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('Student.id = InstitutionSiteClassGradeStudent.student_id')
				),
				array(
					'table' => 'assessment_item_results',
					'alias' => 'AssessmentItemResult',
					'type' => 'LEFT',
					'conditions' => array(
						'AssessmentItemResult.student_id = Student.id',
						'AssessmentItemResult.institution_site_id = ' . $institutionSiteId,
						'AssessmentItemResult.school_year_id = ' . $yearId,
						'AssessmentItemResult.assessment_item_id = ' . $itemId
					)
				),
				array(
					'table' => 'assessment_result_types',
					'alias' => 'AssessmentResultType',
					'type' => 'LEFT',
					'conditions' => array('AssessmentResultType.id = AssessmentItemResult.assessment_result_type_id')
				),
				array(
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array(
						'InstitutionSiteClassGrade.institution_site_class_id = ' . $classId,
						'InstitutionSiteClassGrade.education_grade_id = ' . $gradeId,
						'InstitutionSiteClassGrade.id = InstitutionSiteClassGradeStudent.institution_site_class_grade_id'
					)
				)
			),
			'order' => array('Student.first_name')
		));
		return $data;
	}
	
	public function getGenderTotalByClass($classId) {
		$joins = array(
			array(
				'table' => 'institution_site_class_grades',
				'alias' => 'InstitutionSiteClassGrade',
				'conditions' => array(
					'InstitutionSiteClassGrade.id = InstitutionSiteClassGradeStudent.institution_site_class_grade_id',
					'InstitutionSiteClassGrade.institution_site_class_id = ' . $classId
				)
			),
			array('table' => 'students', 'alias' => 'Student')
		);
		
		$gender = array('M' => 0, 'F' => 0);
		$studentConditions = array('Student.id = InstitutionSiteClassGradeStudent.student_id');
		
		foreach($gender as $i => $val) {
			$studentConditions[1] = sprintf("Student.gender = '%s'", $i);
			$joins[1]['conditions'] = $studentConditions;
			$gender[$i] = $this->find('count', array('joins' => $joins));
		}
		return $gender;
	}
}