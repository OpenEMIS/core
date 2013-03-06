<?php

class TeacherQualificationCategory extends TeachersAppModel {
	public $useTable = "teacher_qualification_categories";
	public $hasMany = array('TeacherQualificationCertificate');
	
	public function getLookupVariables() {
		$lookup = array(
			'Qualification Categories' => array('model' => 'Teachers.TeacherQualificationCategory')
		);
		return $lookup;
	}
}