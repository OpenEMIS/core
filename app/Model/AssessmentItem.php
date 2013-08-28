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

class AssessmentItem extends AppModel {
	public function getItem($id) {
		$data = $this->find('first', array(
			'fields' => array(
				'AssessmentItemType.id', 'AssessmentItemType.name', 'AssessmentItemType.visible',
				'AssessmentItem.id', 'AssessmentItem.min', 'AssessmentItem.max',
				'EducationGradeSubject.education_grade_id',
				'EducationSubject.code', 'EducationSubject.name'
			),
			'joins' => array(
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
					'table' => 'assessment_item_types',
					'alias' => 'AssessmentItemType',
					'conditions' => array('AssessmentItemType.id = AssessmentItem.assessment_item_type_id')
				)
			),
			'conditions' => array('AssessmentItem.id' => $id)
		));
		return $data;
	}
	
	public function getItemList($assessmentId) {
		$data = $this->find('list', array(
			'fields' => array('AssessmentItem.id', 'EducationSubject.name'),
			'joins' => array(
				array(
					'table' => 'education_grades_subjects',
					'alias' => 'EducationGradeSubject',
					'conditions' => array('EducationGradeSubject.id = AssessmentItem.education_grade_subject_id')
				),
				array(
					'table' => 'education_subjects',
					'alias' => 'EducationSubject',
					'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id')
				)
			),
			'conditions' => array('assessment_item_type_id' => $assessmentId),
			'order' => array('EducationSubject.order')
		));
		return $data;
	}
}
