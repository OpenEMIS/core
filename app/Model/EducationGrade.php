<?php
App::uses('AppModel', 'Model');

class EducationGrade extends AppModel {
	public $hasMany = array('EducationGradeSubject');
	public $belongsTo = array('EducationProgramme');
	
	public function findListAsSubgroups() {
		return $this->findList(true);
	}
}
