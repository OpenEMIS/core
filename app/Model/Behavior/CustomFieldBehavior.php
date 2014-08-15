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

class CustomFieldBehavior extends ModelBehavior {
	public function setup(Model $model, $settings = array()) {
		if (!isset($this->settings[$model->alias])) {
			$this->settings[$model->alias] = array();
		}
		$this->settings[$model->alias] = array_merge($this->settings[$model->alias], (array)$settings);
		if (!array_key_exists('module', $this->settings[$model->alias])) {
			pr('Please set a module for CustomFieldBehavior');die;
		}
	}
	
	public function additional(Model $model, $controller, $params) {
		$module = $this->settings[$model->alias]['module'];
		$valueModel = $module . 'CustomValue';
		$conditions = array();
		$key = $valueModel . '.' . Inflector::underscore($module.'Id');
		$conditions[$key] = $controller->Session->read($module . '.id');
		
		$model->unbindModel(array('hasMany' => array($valueModel)));
		$data = $model->find('all', array('conditions' => array($model->alias . '.visible' => 1), 'order' => $model->alias . '.order'));
		$valuesData = $model->{$valueModel}->find('all', array('conditions' => $conditions));
		
		$dataValues = array();
		foreach ($valuesData as $arrV) {
			$dataValues[$arrV[$model->alias]['id']][] = $arrV[$valueModel];
		}
		$controller->set(compact('data', 'dataValues'));
	}
	
	public function additionalEdit(Model $model, $controller, $params) {
		$module = $this->settings[$model->alias]['module'];
		$fieldModel = $module . 'CustomField';
		$optionModel = $module . 'CustomFieldOption';
		$valueModel = $module . 'CustomValue';
		$key = Inflector::underscore($module.'Id');
		$keyValue = $controller->Session->read($module . '.id');
		$fieldKey = Inflector::underscore($fieldModel.'Id');
		
		if ($controller->request->is('post')) { 
			$arrFields = array('textbox', 'dropdown', 'checkbox', 'textarea');
			
			// Note to Preserve the Primary Key to avoid exhausting the max PK limit
			foreach ($arrFields as $fieldVal) {
				if (!isset($controller->request->data[$valueModel][$fieldVal])) continue;
				
				foreach ($controller->request->data[$valueModel][$fieldVal] as $id => $val) {

					if ($fieldVal == "checkbox") {
						if (count($val['value'])==0) {
							$controller->Message->alert('general.error');
							$error = true;
							break;
						}
						
						$arrCustomValues = $model->{$valueModel}->find('list', array(
							'fields' => array('value'),
							'conditions' => array(
								$valueModel . '.' . $key => $keyValue, 
								$valueModel . '.' . $fieldKey => $id
							)
						));

						$tmp = array();
						if (count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
							foreach ($arrCustomValues as $pk => $intVal) {
								if (!in_array($intVal, $val['value'])) {
									//echo "not in db so remove \n";
									$model->{$valueModel}->delete($pk);
								}
							}
						$ctr = 0;
						if (count($arrCustomValues) < count($val['value'])) //if answer has greater value than db, insert
							foreach ($val['value'] as $intVal) {
								if (!in_array($intVal, $arrCustomValues)) {
									$model->{$valueModel}->create();
									$arrV['value'] = $val['value'][$ctr];
									$arrV[$fieldKey] = $id;
									$arrV[$key] = $keyValue;
									$model->{$valueModel}->save($arrV);
									unset($arrCustomValues[$ctr]);
								}
								$ctr++;
							}
					} else { // if editing reuse the Primary KEY; so just update the record
						$datafields = $model->{$valueModel}->find('first', array(
							'fields' => array('id', 'value'), 
							'conditions' => array(
								$valueModel . '.' . $key => $keyValue, 
								$valueModel . '.' . $fieldKey => $id
							)
						));
						
						if ($datafields) {
							$arrV['id'] = $datafields[$valueModel]['id'];
						} else {
							$model->{$valueModel}->create();
						}
						
						$arrV['value'] = $val['value'];
						$arrV[$fieldKey] = $id;
						$arrV[$key] = $keyValue;
						
						if ($model->{$valueModel}->save($arrV)) {
							$controller->Message->alert('general.edit.success');
						} else {
							$controller->Message->alert('general.error');
						}
					}
				}
			}
		}

		$model->bindModel(array(
			'hasMany' => array(
				$optionModel => array(
					'conditions' => array($optionModel . '.visible' => 1),
					'order' => array($optionModel . '.order' => "ASC")
				)
			)
		));
		$model->unbindModel(array('hasMany' => array($valueModel)));
		$data = $model->find('all', array('conditions' => array($fieldModel . '.visible' => 1), 'order' => $fieldModel . '.order'));
		$dataValues = $model->{$valueModel}->find('all', array('conditions' => array($valueModel . '.' . $key => $keyValue)));
		$tmp = array();
		foreach ($dataValues as $arrV) {
			$tmp[$arrV[$model->alias]['id']][] = $arrV[$valueModel];
		}
		$dataValues = $tmp;
		$controller->set('data', $data);
		$controller->set('dataValues', $tmp);
	}
}
