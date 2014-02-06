<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

class TeacherPositionTitle extends TeachersAppModel {

	public $hasMany = array('Training.TrainingCourseTargetPopulation');
	public function getLookupVariables() {
		$lookup = array(
			'Categories' => array('model' => 'Teachers.TeacherPositionTitle')
		);
		return $lookup;
	}


	public function autocomplete($search, $index) {
		$search = sprintf('%%%s%%', $search);
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT TeacherPositionTitle.name', 'TeacherPositionTitle.id'),
			'conditions' => array(
				'TeacherPositionTitle.name LIKE' => $search,
				'TeacherPositionTitle.visible' => 1
			),
			'order' => array('TeacherPositionTitle.order')
		));

		
		$data = array();
		
		foreach($list as $obj) {
			$teacherPositionTitleId = $obj['TeacherPositionTitle']['id'];
			$teacherPositionTitleName = $obj['TeacherPositionTitle']['name'];
			
			$data[] = array(
				'label' => trim(sprintf('%s', $teacherPositionTitleName)),
				'value' => array('teacher-position-title-id-'.$index => $teacherPositionTitleId, 'teacher-position-title-name-'.$index => $teacherPositionTitleName)
			);
		}

		return $data;
	}
}
