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
						if ($mandatory && count($val['value'])==0) {
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
	
	public function getRender(Model $model, $controller) {
		$views = array();
		$parentId = Inflector::underscore($model->alias) . '_id';
		$modelOption = $model->alias . 'Option';
		if ($controller->action == 'view') {
			$data = $controller->viewVars['data'];
			$id = $data[$model->alias]['id'];
			$options = $model->{$modelOption}->find('all', array(
				'conditions' => array($parentId => $id),
				'order' => array("$modelOption.visible" => 'DESC', "$modelOption.order")
			));
			foreach ($options as $obj) {
				$data[$modelOption][] = $obj[$modelOption];
			}
			$controller->set('data', $data);
		} else if ($controller->action == 'edit') {
			if ($controller->request->is('get')) {
				$data = $controller->request->data;
				$id = $data[$model->alias]['id'];
				
				$options = $model->{$modelOption}->find('all', array(
					'conditions' => array($parentId => $id),
					'order' => array("$modelOption.visible" => 'DESC', "$modelOption.order")
				));
				foreach ($options as $obj) {
					$controller->request->data[$modelOption][] = $obj[$modelOption];
				}
			}
		}
		
		return $views;
	}
	
	public function postAdd(Model $model, $controller) {
		$selectedOption = $controller->params->pass[0];
		$modelOption = $model->alias . 'Option';
		if (isset($controller->request->data['submit'])) {
			$submit = $controller->request->data['submit'];
			
			switch ($submit) {
				case $modelOption:
					$obj = array('value' => '');
					if (!isset($controller->request->data[$submit])) {
						$controller->request->data[$submit] = array();
					}
					
					$obj['order'] = count($controller->request->data[$submit]);
					$controller->request->data[$submit][] = $obj;
					break;
					
				case 'Save':
					$data = $controller->request->data;
					
					$models = array($modelOption);
					// remove all records that doesn't have values
					foreach ($models as $m) {
						if (isset($data[$m])) {
							$x = $data[$m];
							foreach ($x as $i => $obj) {
								if (empty($obj['value'])) {
									unset($controller->request->data[$m][$i]);
								} else {
									$controller->request->data[$m][$i]['visible'] = 1;
								}
							}
						}
					}
					if ($model->saveAll($controller->request->data)) {
						$controller->Message->alert('general.add.success');
						return $controller->redirect(array('controller' => $controller->name, 'action' => 'view', $selectedOption, $model->getLastInsertID()));
					} else {
						$this->log($model->validationErrors, 'error');
						$controller->Message->alert('general.add.failed');
					}
					break;
				
				default:
					break;
			}
		}
		return true;
	}
	
	public function postEdit(Model $model, $controller) {
		$selectedOption = $controller->params->pass[0];
		$modelOption = $model->alias . 'Option';
		if (isset($controller->request->data['submit'])) {
			$submit = $controller->request->data['submit'];
			
			switch ($submit) {
				case $modelOption:
					$obj = array('value' => '', 'visible' => 1);
					if (!isset($controller->request->data[$submit])) {
						$controller->request->data[$submit] = array();
					}
					$obj['order'] = count($controller->request->data[$submit]);
					$controller->request->data[$submit][] = $obj;
					break;
					
				case 'Save':
					$data = $controller->request->data;
					$id = $data[$model->alias]['id'];
					$models = array($modelOption);
					foreach ($models as $m) {
						if (isset($data[$m])) {
							$x = $data[$m];
							foreach ($x as $i => $obj) {
								if (empty($obj['value'])) {
									unset($controller->request->data[$m][$i]);
								}
							}
						}
					}
					
					if ($model->saveAll($controller->request->data)) {
						$controller->Message->alert('general.edit.success');
						return $controller->redirect(array('controller' => $controller->name, 'action' => 'view', $selectedOption, $id));
					} else {
						$this->log($model->validationErrors, 'error');
						$controller->Message->alert('general.edit.failed');
					}
					break;
				
				default:
					break;
			}
		}
		return true;
	}
}
