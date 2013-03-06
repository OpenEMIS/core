<?php

class TeacherQualificationCertificate extends TeachersAppModel {
	public $useTable = "teacher_qualification_certificates";
	public $belongsTo = array('TeacherQualificationCategory');
	
	public function getLookupVariables() {
		$parent = ClassRegistry::init('Teachers.TeacherQualificationCategory');
		$list = $parent->findList();
		$lookup = array();
		
		foreach($list as $id => $name) {
			$lookup[$name] = array(
				'model' => 'Teachers.TeacherQualificationCertificate',
				'conditions' => array('teacher_qualification_category_id' => $id)
			);
		}
		return $lookup;
	}
}