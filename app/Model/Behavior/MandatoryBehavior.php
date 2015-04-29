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

class MandatoryBehavior extends ModelBehavior {
	public $currentModel;
	public $validationModels = array();

	public function setMandatoryModel(Model $model, $modelName) {
		// needed for student and staff
		$this->currentModel = $modelName;

		$ConfigItem = ClassRegistry::init('ConfigItem');
		$this->validationModels = array();
		switch ($this->currentModel) {
			case 'Staff':
				$this->validationModels = array(
					'StaffIdentity' => array(
						'fieldName' => array('number')
					), 
					'StaffNationality' => array(
						'fieldName' => array(),
					), 
					'StaffContact' => array(
						'fieldName' => array('value')
					), 
					'StaffSpecialNeed' => array(
						'fieldName' => array('comment')
					)
				);
				break;
		}

		foreach ($this->validationModels as $key => $value) {
			$mandatoryField = $ConfigItem->getOptionValue($key);
			$model->controller->set(Inflector::variable('config'.$key), $mandatoryField);
		}
	}

	public function beforeValidate(Model $model, $options = array()) {
		$ConfigItem = ClassRegistry::init('ConfigItem');
		
		foreach ($this->validationModels as $key => $value) {
			$mandatoryField = $ConfigItem->getOptionValue($key);
			if (array_key_exists('fieldName', $value)) {
				foreach ($this->validationModels[$key]['fieldName'] as $fieldKey => $fieldName) {
					if (array_key_exists($key, $model->controller->request->data)) {
						foreach ($model->controller->request->data[$key] as $ikey => $ivalue) {
							$model->controller->request->data[$key][$ikey]['_mandatory'] = $mandatoryField;
						}
					}
					switch ($mandatoryField) {
						case 'Non-Mandatory':
							if (empty($model->data[$key][$fieldName])) {
								unset($model->data[$key]);
							}
							if ($model->{$key}->validator()->offsetExists($fieldName)) {
								$model->{$key}->validator()->remove($fieldName);	
							}
							break;

						case 'Mandatory':
							if (!$model->{$key}->validator()->offsetExists($fieldName)) {
								$model->{$key}->validator()->add($fieldName, 'required', array(
									'rule' => 'notEmpty',
									'message' => 'Please enter a valid '.$fieldName.'.'
								));
							}
							break;

						case 'Excluded':
							# code...
							break;
						
						default:
							# code...
							break;
					}
				}
			}
		}
		return parent::beforeValidate($model, $options);
	}


	
	// public function beforeSave(Model $model, $options = array()) {
	// 	$format = 'H:i:s';
	// 	$fields = $this->settings[$model->alias];
	// 	foreach($fields as $field => $attr) {
	// 		if(isset($model->data[$model->alias][$field]) && !empty($model->data[$model->alias][$field])) {
	// 			$value = $model->data[$model->alias][$field];
	// 			$model->data[$model->alias][$field] = date($format, strtotime($value));
				
	// 		}
	// 	}
	// 	return parent::beforeSave($model, $options);
	// }
	
	// public function afterFind(Model $model, $results, $primary = false) {
	// 	$fields = $this->settings[$model->alias];
	// 	foreach($results as $i => $result) {
	// 		foreach($fields as $field => $attr) {
	// 			$format = isset($attr['format']) ? $attr['format'] : 'H:i:s a';
	// 			if(isset($result[$model->alias][$field]) && !empty($result[$model->alias][$field]) && ($result[$model->alias][$field] !== '0000-00-00')) {
	// 				$value = $result[$model->alias][$field];
	// 				$results[$i][$model->alias][$field] = date($format, strtotime($value));
	// 			}else{
	// 				$results[$i][$model->alias][$field] = '';
	// 			}
	// 		}
	// 	}
	// 	return $results;
	// }
}