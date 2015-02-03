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

class InstitutionSiteInfrastructureCustomValue extends AppModel {
	public $useTable = 'institution_site_infrastructure_custom_values';

	public $belongsTo = array(
		'InstitutionSiteInfrastructure',
		'InstitutionSite',
		'Infrastructure.InfrastructureCustomField',
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
	
	public function beforeValidate($options=array()) {
		$modelValue = $this->alias;
		$this->validator()->remove('text_value');
		$this->validator()->remove('textarea_value');
		$this->validator()->remove('int_value');

		switch($this->data[$modelValue]['type']) {
			case 2: //Text
				$fieldName = 'text_value';
				break;
			case 5:	//Textarea
				$fieldName = 'textarea_value';
				break;
			case 6:	//Number
				$fieldName = 'int_value';
				break;
		}

		if(isset($this->data[$modelValue]['is_mandatory']) && $this->data[$modelValue]['is_mandatory'] == 1) {
			$this->validator()->add($fieldName, 'required', array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a value'
			));
		}

		if(isset($this->data[$modelValue]['is_unique']) && $this->data[$modelValue]['is_unique'] == 1) {
			$this->validator()->add($fieldName, 'unique', array(
				'rule' => array('checkUnique', array('institution_site_id', 'infrastructure_custom_field_id', $fieldName), false),
				'message' => 'Please enter a unique value'
			));
		}
	}

	public function prepareDataBeforeSave($requestData) {
		//pr($requestData);die;
		$modelValue = 'InstitutionSiteInfrastructureCustomValue';

		$institutionSiteId = CakeSession::read('InstitutionSite.id');

		$result['InstitutionSiteInfrastructure'] = $requestData['InstitutionSiteInfrastructure'];
		$result['InstitutionSiteInfrastructure']['institution_site_id'] = $institutionSiteId;
		
		//pr($result);die;
		$arrFields = array(
			'textbox' => 'text_value',
			'dropdown' => 'int_value',
			//'checkbox' => 'int_value',	//Separate out checbox to handle put back data if save failed
			'textarea' => 'textarea_value',
			'number' => 'int_value'
		);

		$index = 0;
		foreach ($arrFields as $fieldVal => $fieldName) {
			if (!isset($requestData[$modelValue][$fieldVal])){
				continue;
			}
            
            foreach ($requestData[$modelValue][$fieldVal] as $key => $obj) {
            	$index = $key > $index ? $key : $index;
				$result[$modelValue][$key]['institution_site_id'] = $institutionSiteId;
            	$result[$modelValue][$key]['infrastructure_custom_field_id'] = $key;
            	$result[$modelValue][$key]['type'] = $obj['type'];
				$result[$modelValue][$key]['is_mandatory'] = $obj['is_mandatory'];
				$result[$modelValue][$key]['is_unique'] = $obj['is_unique'];
            	$result[$modelValue][$key][$fieldName] = $obj['value'];
        	}
		}

		return $result;
	}
	
}
