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

class StudentCustomField extends StudentsAppModel {
	public $actsAs = array(
		'FieldOption',
		'ControllerAction',
		'CustomField' => array('module' => 'Student')
	);
	
	public $hasMany = array(
		'Students.StudentCustomFieldOption',
		'Students.StudentCustomValue'
	);
	
	public function beforeAction($controller, $params) {
		parent::beforeAction($controller, $params);
		$controller->Navigation->addCrumb('More');
		$controller->set('header', __('More'));
	}
	
	public function getOptionFields() {
		$fieldTypeOptions = $this->getCustomFieldTypes();
		$fieldType = array('field' => 'type', 'type' => 'select', 'options' => $fieldTypeOptions, 'display' => true);
		$this->removeOptionFields(array('international_code', 'national_code'));
		$this->addOptionField($fieldType, 'after', 'name');
		$fields = $this->Behaviors->dispatchMethod($this, 'getOptionFields');
		return $fields;
	}
}
