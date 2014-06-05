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

class AssessmentItemResult extends AppModel {
	public function getResultsByStudent($studentId, $institutionSiteId=0) {
		$fields = array(
			'EducationSubject.code', 'EducationSubject.name', 'EducationGrade.id', 'EducationGrade.name', 'EducationProgramme.name',
			'AssessmentItemResult.marks', 'AssessmentResultType.name', 'AssessmentItemType.id', 'AssessmentItemType.name'
		);
		
		$joins = array(
			array(
				'table' => 'assessment_items',
				'alias' => 'AssessmentItem',
				'conditions' => array('AssessmentItem.id = AssessmentItemResult.assessment_item_id')
			),
			array(
				'table' => 'assessment_item_types',
				'alias' => 'AssessmentItemType',
				'conditions' => array('AssessmentItemType.id = AssessmentItem.assessment_item_type_id')
			),
			array(
				'table' => 'assessment_result_types',
				'alias' => 'AssessmentResultType',
				'conditions' => array('AssessmentResultType.id = AssessmentItemResult.assessment_result_type_id')
			),
			array(
				'table' => 'education_grades_subjects',
				'alias' => 'EducationGradeSubject',
				'conditions' => array('EducationGradeSubject.id = AssessmentItem.education_grade_subject_id')
			),
			array(
				'table' => 'education_subjects',
				'alias' => 'EducationSubject',
				'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id')
			),
			array(
				'table' => 'education_grades',
				'alias' => 'EducationGrade',
				'conditions' => array('EducationGrade.id = EducationGradeSubject.education_grade_id')
			),
			array(
				'table' => 'education_programmes',
				'alias' => 'EducationProgramme',
				'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
			)
		);
		
		$conditions = array('AssessmentItemResult.student_id' => $studentId);
		
		if($institutionSiteId==0) {
			$joins[] = array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = AssessmentItemResult.institution_site_id')
			);
			$joins[] = array(
				'table' => 'institutions',
				'alias' => 'Institution',
				'conditions' => array('Institution.id = InstitutionSite.institution_id')
			);
			$fields[] = 'InstitutionSite.name';
			$fields[] = 'Institution.name';
		} else {
			$conditions['AssessmentItemResult.institution_site_id'] = $institutionSiteId;
		}
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => $fields,
			'joins' => $joins,
			'conditions' => $conditions,
			'order' => array('EducationProgramme.order', 'EducationGrade.order', 'EducationSubject.order')
		));
		
		return $data;
	}
	
	public function groupItemResults($data) {
		$results = array();
		
		foreach($data as $obj) {
			$gradeId = $obj['EducationGrade']['id'];
			$assessmentId = $obj['AssessmentItemType']['id'];
			if(!array_key_exists($gradeId, $results)) {
				$results[$gradeId] = array(
					'name' => $obj['EducationProgramme']['name'] . ' - ' . $obj['EducationGrade']['name'], 
					'assessments' => array($assessmentId => array(
						'name' => $obj['AssessmentItemType']['name'],
						'subjects' => array()
					))
				);
			} else {
				if(!array_key_exists($assessmentId, $results[$gradeId]['assessments'])) {
					$results[$gradeId]['assessments'][$assessmentId] = array(
						'name' => $obj['AssessmentItemType']['name'],
						'subjects' => array()
					);
				}
			}
			$results[$gradeId]['assessments'][$assessmentId]['subjects'][] = array(
				'code' => $obj['EducationSubject']['code'],
				'name' => $obj['EducationSubject']['name'],
				'marks' => $obj['AssessmentItemResult']['marks'],
				'grading' => $obj['AssessmentResultType']['name']
			);
		}
		return $results;
	}
}
