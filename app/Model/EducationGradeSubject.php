<?php
App::uses('AppModel', 'Model');

class EducationGradeSubject extends AppModel {
	public $useTable = 'education_grades_subjects';
	public $belongsTo = array('EducationGrade', 'EducationSubject');
	
	public function findSubjectsByGrades($gradeIds) {
		$list = $this->find('all', array(
				'fields' => array('EducationGradeSubject.id', 'EducationGradeSubject.education_grade_id', 'EducationGradeSubject.education_subject_id', 'EducationSubject.name'),
				'conditions' => array('EducationGradeSubject.education_grade_id' => $gradeIds),
				'order' => array('EducationSubject.name')
			)
		);
		
		$list  = $this->formatArray($list);
		
		return $list;
	}
	
	public function groupSubjectsByGrade($subjectList) {
		$list = array(0 => array());
		foreach($subjectList as $subject) {
			$gradeId = $subject['education_grade_id'];
			$subjectId = $subject['education_subject_id'];
			
			$found = false;
			foreach($list[0] as $id => $item) {
				if(intval($subjectId) == intval($item['education_subject_id'])) {
					$found = true;
					break;
				}
			}
			if(!$found) {
				$list[0][$subject['id']] = array(
					'education_grade_subject_id' => $subject['id'],
					'education_subject_id' => $subjectId,
					'education_subject_name' => $subject['name']
				);
			}
			$list[$gradeId][$subject['id']] = array(
				'education_grade_subject_id' => $subject['id'],
				'education_subject_id' => $subjectId,
				'education_subject_name' => $subject['name']
			);
		}
		return $list;
	}
}
