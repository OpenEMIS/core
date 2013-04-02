<?php
App::uses('AppModel', 'Model');

class EducationGrade extends AppModel {
	public $hasMany = array('EducationGradeSubject');
	public $belongsTo = array('EducationProgramme');
	
	public function findListAsSubgroups() {
		return $this->findList(true);
	}
	
	public function getGradeOptions($programmeId, $exclude=array(), $onlyVisible=false) {
		$conditions = array('EducationGrade.education_programme_id' => $programmeId);
		
		if(!empty($exclude)) {
			$conditions['EducationGrade.id NOT'] = $exclude;
		}
		
		if($onlyVisible) {
			$conditions['EducationGrade.visible'] = 1;
		}
		
		$options = array(
			'recursive' => -1,
			'fields' => array('EducationGrade.id', 'EducationGrade.name'),
			'conditions' => $conditions,
			'order' => array('EducationGrade.order')
		);
		$data = $this->find('list', $options);
		return $data;
	}
}
