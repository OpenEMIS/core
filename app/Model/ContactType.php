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

class ContactType extends AppModel {
	public $actsAs = array('FieldOption');
	public $hasMany = array('StudentContact', 'StaffContact');
	public $belongsTo = array(
		'ContactOption',
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
		return $this->ContactOption->findList();
	}
	
	public function getOptionFields() {
		parent::getOptionFields();
		
		$this->fields[$this->getConditionId()]['type'] = 'select';
		$this->fields[$this->getConditionId()]['options'] = $this->getSubOptions();
		$this->setFieldOrder($this->getConditionId(), 1);

		return $this->fields;
	}
	
	public function getConditionId() {
		return 'contact_option_id';
	}

	public function getOptions($options = array()){
		$conditions = array();
		$conditions['visible'] = 1;
		if (array_key_exists('contact_option_id', $options)) {
			$conditions['contact_option_id'] = $options['contact_option_id'];
		}
		$data = $this->find('all', array(
			'recursive' => -1, 
			'conditions'=> $conditions,
			'order' => array('ContactType.order')
			)
		);
		$list = array();
		foreach($data as $obj){
			$list[$obj['ContactType']['id']] = $obj['ContactType']['name'];
		}

		return $list;
	}

}
