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

class InfrastructureCustomField extends InfrastructureAppModel {
	public $actsAs = array(
		'Containable'
	);
	
	public $belongsTo = array(
		'InfrastructureLevel',
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
	
	public $hasMany = array(
		'Infrastructure.InfrastructureCustomFieldOption'
	);
	
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a field name'
			)
		)
	);
	
	public function getCustomFields($id) {
		$this->contain(array('InfrastructureCustomFieldOption'));
		$result = $this->find('all', array(
			'conditions' => array(
				'InfrastructureCustomField.infrastructure_level_id' => $id,
				'InfrastructureCustomField.visible' => 1
			),
			'order' => array(
				'InfrastructureCustomField.order',
				'InfrastructureCustomField.name'
			)
		));

		return $result;
	}
	
}
