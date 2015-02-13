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

class StaffCustomField extends StaffAppModel {
	public $actsAs = array(
		'CustomField' => array('module' => 'Staff'),
		'FieldOption',
		'ControllerAction',
		'Excel'
	);
	
	public $belongsTo = array(
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
	
	public $hasMany = array(
		'StaffCustomFieldOption',
		'Staff.StaffCustomValue'
	);
	
	public function beforeAction($controller, $params) {
		parent::beforeAction($controller, $params);
		$controller->Navigation->addCrumb('More');
		$controller->set('header', __('More'));
	}
	
	public function excelCustomFieldFindOptions($options) {
		$options['conditions'][$this->alias . '.type'] = array(2, 3, 4, 5);
		return $options;
	}
	
	public function getOptionFields($controller) {
		parent::getOptionFields($controller);
		
		$this->fields['type']['type'] = 'select';
		$this->fields['type']['options'] = $this->getCustomFieldTypes();
		$this->fields['type']['visible'] = array('index' => true, 'view' => true, 'edit' => true);
		$this->fields['type']['attr'] = array('onchange' => "$('#reload').click()");
		if(!empty($controller) && $controller->action == 'edit'){
			$this->fields['type']['attr']['disabled'] = 'disabled';
		}
		
		$this->fields['options'] = array(
			'type' => 'element',
			'element' => '../FieldOption/CustomField/options',
			'visible' => true
		);
		$this->setFieldOrder('options', 5);

		return $this->fields;
	}
}
