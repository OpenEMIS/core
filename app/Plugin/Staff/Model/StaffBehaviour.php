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

class StaffBehaviour extends StaffAppModel {
	public $useTable = 'staff_behaviours';
	
	public $validate = array(
		'title' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter a valid title'
			)
		)
	);
	
	public function getBehaviourData($staffId){
		$list = $this->find('all',array(
			 	'recursive' => -1,
				'joins' => array(
						array(
							'table' => 'staff_behaviour_categories',
							'alias' => 'StaffBehaviourCategory',
							'type' => 'INNER',
							'conditions' => array(
								'StaffBehaviourCategory.id = StaffBehaviour.staff_behaviour_category_id'
							)
						),
						array(
							'table' => 'institution_sites',
							'alias' => 'InstitutionSite',
							'type' => 'INNER',
							'conditions' => array(
								'InstitutionSite.id = StaffBehaviour.institution_site_id'
							)
						)
					),
                'fields' =>array('StaffBehaviour.id','StaffBehaviour.title','StaffBehaviour.date_of_behaviour',
								 'StaffBehaviourCategory.name', 'InstitutionSite.name', 'InstitutionSite.id'),
                'conditions'=>array('StaffBehaviour.staff_id' => $staffId)));
		return $list;
	}
}
