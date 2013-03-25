<?php
App::uses('AppModel', 'Model');

class InstitutionSiteClassGrade extends AppModel {
	
	// used by InstitutionSite classes
	public function getGradesByClass($classId) {
		$data = $this->find('all', array(
			'fields' => array('InstitutionSiteClassGrade.id', 'EducationCycle.name', 'EducationProgramme.name', 'EducationGrade.name'),
			'joins' => array(
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
				)
			),
			'conditions' => array('InstitutionSiteClassGrade.institution_site_class_id' => $classId),
			'order' => array('EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		$list = array();
		foreach($data as $obj) {
			$id = $obj['InstitutionSiteClassGrade']['id'];
			$cycleName = $obj['EducationCycle']['name'];
			$programmeName = $obj['EducationProgramme']['name'];
			$gradeName = $obj['EducationGrade']['name'];
			$list[$id] = sprintf('%s - %s - %s', $cycleName, $programmeName, $gradeName);
		}
		return $list;
	}
	
	public function getStudentIdsByProgramme($gradeId) {
		$this->formatResult = true;
		$obj = $this->find('first', array(
			'fields' => array(
				'InstitutionSiteClassGrade.education_grade_id', 
				'InstitutionSiteClassGrade.institution_site_class_id',
				'EducationGrade.education_programme_id'
			),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionSiteClassGrade.education_grade_id')
				)
			),
			'conditions' => array('InstitutionSiteClassGrade.id' => $gradeId)
		));
		
		$classId = $obj['institution_site_class_id'];
		$programmeId = $obj['education_programme_id'];
		
		$data = $this->find('list', array(
			'fields' => array('InstitutionSiteClassGradeStudent.student_id', 'InstitutionSiteClassGradeStudent.id'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.id = InstitutionSiteClassGrade.education_grade_id',
						'EducationGrade.education_programme_id = ' . $programmeId
					)
				),
				array(
					'table' => 'institution_site_class_grade_students',
					'alias' => 'InstitutionSiteClassGradeStudent',
					'conditions' => array('InstitutionSiteClassGradeStudent.institution_site_class_grade_id = InstitutionSiteClassGrade.id')
				)
			),
			'conditions' => array(
				'InstitutionSiteClassGrade.institution_site_class_id' => $classId
			)
		));
		return $data;
	}
}