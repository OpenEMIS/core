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

class TeacherBehaviour extends TeachersAppModel {
	public $useTable = 'teacher_behaviours';
	
	public $validate = array(
		'title' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter a valid title'
			)
		)
	);
	
	public function getBehaviourData($teacherId){
		$list = $this->find('all',array(
		 	'recursive' => -1,
			'joins' => array(
					array(
						'table' => 'teacher_behaviour_categories',
						'alias' => 'TeacherBehaviourCategory',
						'type' => 'INNER',
						'conditions' => array(
							'TeacherBehaviourCategory.id = TeacherBehaviour.teacher_behaviour_category_id'
						)
					)
				),
            'fields' =>array('TeacherBehaviour.id','TeacherBehaviour.title','TeacherBehaviour.date_of_behaviour',
							 'TeacherBehaviourCategory.name'),
            'conditions'=>array('TeacherBehaviour.teacher_id' => $teacherId)));
		return $list;
	}
}
