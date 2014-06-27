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

class StudentCustomFieldOption extends StudentsAppModel {
	public $actsAs = array('FieldOption');
	public $belongsTo = array(
		'Students.StudentCustomField'
	);
	
	public $validate = array(
		'student_custom_field_id' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a custom field'
			)
		)
	);
	
	public function getSubOptions() {
		$conditions = array('StudentCustomField.type' => array(3, 4));
		$data = $this->StudentCustomField->findList(array('conditions' => $conditions));
		return $data;
	}
	
	public function getOptionFields() {
		$options = $this->getSubOptions();
		$value = array('field' => 'value', 'type' => 'text');
		$field = array('field' => $this->getConditionId(), 'type' => 'select', 'options' => $options);
		$this->removeOptionFields(array('name', 'international_code', 'national_code'));
		$this->addOptionField($field, 'after', 'id');
		$this->addOptionField($value, 'after', $this->getConditionId());
		$fields = $this->Behaviors->dispatchMethod($this, 'getOptionFields');
		return $fields;
	}
	
	public function getConditionId() {
		return 'student_custom_field_id';
	}
}
