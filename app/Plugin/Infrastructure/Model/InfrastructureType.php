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

class InfrastructureType extends InfrastructureAppModel {
	public $actsAs = array('Reorder' => array('parentKey' =>'infrastructure_level_id'));
	
	public $belongsTo = array(
		'ModifiedUser' => array(
			'fields' => array('first_name', 'last_name'),
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'fields' => array('first_name', 'last_name'),
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);
	
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Name',
				'on' => 'create'
			)
		)
	);
	
	public function getTypeOptionsByLevel($levelId=0){
		$data = $this->find('list', array(
			'conditions' => array(
				'InfrastructureType.visible' => 1,
				'InfrastructureType.infrastructure_level_id' => $levelId
			),
			'order' => array('InfrastructureType.order')
		));
		
		return $data;
	}
	
}
