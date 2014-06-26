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

class BankBranch extends AppModel {
	public $actsAs = array('FieldOption');
	public $belongsTo = array(
		'Bank',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id'
		)
	);
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Name'
			)
		),
		'code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Code'
			)
		)
	);
	
	public function getSubOptions() {
		return $this->Bank->findList();
	}
	
	public function getOptionFields() {
		$bankOptions = $this->getSubOptions();
		$codeField = array('field' => 'code');
		$bankField = array('field' => $this->getConditionId(), 'type' => 'select', 'options' => $bankOptions);
		$this->removeOptionFields(array('international_code', 'national_code'));
		$this->addOptionField($codeField, 'after', 'name'); // add code after name
		$this->addOptionField($bankField, 'before', 'name'); // add bank before name
		$fields = $this->Behaviors->dispatchMethod($this, 'getOptionFields');
		return $fields;
	}
	
	public function getConditionId() {
		return 'bank_id';
	}
}
?>