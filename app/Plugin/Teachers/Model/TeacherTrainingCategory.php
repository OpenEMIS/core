<?php
class TeacherTrainingCategory extends TeachersAppModel {
	public $useTable = "teacher_training_categories";
	
	public function getLookupVariables() {
		$lookup = array(
			'Training Categories' => array('model' => 'Teachers.TeacherTrainingCategory')
		);
		return $lookup;
	}
}