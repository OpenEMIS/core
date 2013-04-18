<?php
App::uses('AppModel', 'Model');

class EducationSubject extends AppModel {
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name for the Subject.'
			),
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'This subject is already exists in the system.'
			)
		)
	);
	
	public $hasMany = array('EducationGradeSubject');
	
	// Used by InstitutionSiteController.classesAddTeacherRow
	public function getSubjectByClassId($classId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('EducationSubject.id', 'EducationSubject.code', 'EducationSubject.name'),
			'joins' => array(
				array(
					'table' => 'education_grades_subjects',
					'alias' => 'EducationGradeSubject',
					'conditions' => array('EducationGradeSubject.education_subject_id = EducationSubject.id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = EducationGradeSubject.education_grade_id')
				),
				array(
					'table' => 'institution_site_class_grades',
					'alias' => 'InstitutionSiteClassGrade',
					'conditions' => array(
						'InstitutionSiteClassGrade.education_grade_id = EducationGrade.id',
						'InstitutionSiteClassGrade.institution_site_class_id = ' . $classId
					)
				)
			),
			'group' => array('EducationSubject.id'),
			'conditions' => array('EducationSubject.visible' => 1),
			'order' => array('EducationSubject.order')
		));
		return $data;
	}
}
