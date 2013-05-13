<?php
App::uses('AppModel', 'Model');

class AssessmentItem extends AppModel {
	public function getItem($id) {
		$data = $this->find('first', array(
			'fields' => array(
				'AssessmentItemType.id', 'AssessmentItemType.name',
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
		//pr($data);
		return $data;
	}
}
