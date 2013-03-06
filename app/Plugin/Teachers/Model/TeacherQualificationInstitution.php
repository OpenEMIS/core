<?php

class TeacherQualificationInstitution extends TeachersAppModel {
	public $useTable = "teacher_qualification_institutions";
	
	public function getLookupVariables() {
		$lookup = array(
			'Qualification Institutions' => array('model' => 'Teachers.TeacherQualificationInstitution')
		);
		return $lookup;
	}
}