<?php

class TeacherCategory extends TeachersAppModel {
	public function getLookupVariables() {
		$lookup = array(
			'Categories' => array('model' => 'Teachers.TeacherCategory')
		);
		return $lookup;
	}
}
