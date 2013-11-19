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

class StudentBehaviour extends StudentsAppModel {
	public $useTable = 'student_behaviours';
	
	public $validate = array(
		'title' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter a valid title'
			)
		)
	);
	
	public function getBehaviourData($studentId){
		$list = $this->find('all',array(
			 	'recursive' => -1,
				'joins' => array(
					array(
						'table' => 'student_behaviour_categories',
						'alias' => 'StudentBehaviourCategory',
						'type' => 'INNER',
						'conditions' => array(
							'StudentBehaviourCategory.id = StudentBehaviour.student_behaviour_category_id'
						)
					),
					array(
						'table' => 'institution_sites',
						'alias' => 'InstitutionSite',
						'type' => 'INNER',
						'conditions' => array(
							'InstitutionSite.id = StudentBehaviour.institution_site_id'
						)
					)
				),
                'fields' =>array('StudentBehaviour.id','StudentBehaviour.title','StudentBehaviour.date_of_behaviour',
								 'StudentBehaviourCategory.name', 'InstitutionSite.name', 'InstitutionSite.id'),
                'conditions'=>array('StudentBehaviour.student_id' => $studentId)));
		return $list;
	}
}
