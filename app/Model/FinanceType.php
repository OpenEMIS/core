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

App::uses('AppModel', 'Model');

class FinanceType extends AppModel {
	public $actsAs = array('FieldOption');
	public $hasMany = array('FinanceCategory');
	public $belongsTo = array(
		'FinanceNature',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id',
			'type' => 'LEFT'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id',
			'type' => 'LEFT'
		)
	);
	
	public function getSubOptions() {
		return $this->FinanceNature->findList();
	}
	
	public function getOptionFields() {
		$options = $this->getSubOptions();
		$field = array('field' => $this->getConditionId(), 'type' => 'select', 'options' => $options);
		$this->addOptionField($field, 'after', 'name');
		$fields = $this->Behaviors->dispatchMethod($this, 'getOptionFields');
		return $fields;
	}
	
	public function getConditionId() {
		return 'finance_nature_id';
	}
}
