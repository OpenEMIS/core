<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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
				'Student.id', 'Student.identification_no', 'Student.first_name', 'Student.last_name', 'Student.gender', 
				'Student.telephone', 'InstitutionSiteClassGradeStudent.institution_site_class_grade_id'
			),
			'joins' => array(
				array(
					'table' => 'students',
					'alias' => 'Student',
					'conditions' => array('Student.id = InstitutionSiteClassGradeStudent.student_id')
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
			$list[$gradeId][] = $obj['Student'];
		}
		return $list;
	}
	
	public function getStudentAssessmentResults($yearId, $institutionSiteId, $classId, $gradeId, $itemId) {
		$data = $this->find('all', array(
			'fields' => array(
				'Student.id', 'Student.identification_no', 'Student.first_name', 'Student.last_name',
				'AssessmentItemResult.id', 'AssessmentItemResult.marks'
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