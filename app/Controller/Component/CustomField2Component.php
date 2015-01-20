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

class CustomField2Component extends Component {
	private $controller;

	public $components = array('Session', 'Message');

	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		
		$models = $this->settings['models'];
		foreach ($models as $key => $model) {
			if (!is_null($model)) {
				$this->{$key} = ClassRegistry::init($model);
			} else {
				$this->{$key} = null;
			}

			$modelInfo = explode('.', $model);
			$base = count($modelInfo) == 1 ? $modelInfo[0] : $modelInfo[1];
			$this->controller->set('Custom_' . $key, $base);
		}
		$this->controller->set('viewType', $this->settings['viewType']);
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Controller $controller) {
		$fieldTypeOptions = $this->get('fieldType');
		$selectedFieldType = key($fieldTypeOptions);
		$mandatoryOptions = $this->get('mandatory');
		$selectedMandatory = 0;
		$uniqueOptions = $this->get('unique');
		$selectedUnique = 0;
		$visibleOptions = $this->get('visible');
		$selectedVisible = 1;

		if($this->controller->action == 'add' || $this->controller->action == 'edit') {
			$params = $this->controller->params->named;

			foreach ($params as $key => $value) {
	    		$this->controller->set('Custom_' . ucfirst($key) . 'Id', $value);
	    	}

			$groupName = $this->Group->field('name', array($this->Group->alias.'.id' => $params['group']));
			$this->controller->set('groupName', $groupName);

			if ($this->controller->request->is(array('post', 'put'))) {
				$data = $this->controller->request->data;
				$selectedFieldType = $data[$this->Field->alias]['type'];

				if ($data['submit'] == 'reload' || (isset($this->FieldOption) && $data['submit'] == $this->FieldOption->alias) || (isset($this->TableColumn) && $data['submit'] == $this->TableColumn->alias) || (isset($this->TableRow) && $data['submit'] == $this->TableRow->alias)) {
					//always reset to 0 when reload; in other conditions should set to 0 too
					$selectedMandatory = 0;
					$selectedUnique = 0;

					$this->controller->request->data[$this->Field->alias]['is_mandatory'] = $selectedMandatory;
					$this->controller->request->data[$this->Field->alias]['is_unique'] = $selectedUnique;
				} else {
					//actual submit
				}
			} else {
				//the below are set so that variable can be access from $this->request->data in view for add and edit
				$this->controller->request->data[$this->Field->alias]['type'] = $selectedFieldType;
				$this->controller->request->data[$this->Field->alias]['is_mandatory'] = $selectedMandatory;
				$this->controller->request->data[$this->Field->alias]['is_unique'] = $selectedUnique;
				$this->controller->request->data[$this->Field->alias]['visible'] = $selectedVisible;
			}
		}

		$fieldTypeDisabled = $this->controller->action == 'edit' ? 'disabled' : '' ;
		$mandatoryDisabled = $this->getMandatoryDisabled($selectedFieldType);
		$uniqueDisabled = $this->getUniqueDisabled($selectedFieldType);

		$controller->set('fieldTypeOptions', $fieldTypeOptions);
		$controller->set('fieldTypeDisabled', $fieldTypeDisabled);
		$controller->set('mandatoryOptions', $mandatoryOptions);
		$controller->set('mandatoryDisabled', $mandatoryDisabled);
		$controller->set('uniqueOptions', $uniqueOptions);
		$controller->set('uniqueDisabled', $uniqueDisabled);
		$controller->set('visibleOptions', $visibleOptions);
	}

	public function get($code) {
		$options = array(
			'fieldType' => array(
				1 => __('Section Break'),
				2 => __('Text'),
				3 => __('Dropdown'),
				4 => __('Checkbox'),
				5 => __('Textarea'),
				6 => __('Number'),
				7 => __('Table')
			),
			'mandatory' => array(
				1 => __('Yes'),
				0 => __('No')
			),
			'unique' => array(
				1 => __('Yes'),
				0 => __('No')
			),
			'visible' => array(
				1 => __('Yes'),
				0 => __('No')
			)
		);
		
		$index = explode('.', $code);
		foreach($index as $i) {
			if(isset($options[$i])) {
				$option = $options[$i];
			} else {
				$option = array('[Option Not Found]');
				break;
			}
		}
		return $option;
	}

    public function getMandatoryDisabled($fieldTypeId=1) {
		$arrMandatory = array(2,5,6);
		if(in_array($fieldTypeId, $arrMandatory)) {
			$result = '';
		} else {
			$result = 'disabled';
		}

		return $result;
    }

	public function getUniqueDisabled($fieldTypeId=1) {
		$arrUnique = array(2,6);
		if(in_array($fieldTypeId, $arrUnique)) {
			$result = '';
		} else {
			$result = 'disabled';
		}
		return $result;
    }

    public function doRender($page) {
    	if (!empty($this->controller->params->plugin)) {
    		$this->controller->render('../../../../View/Elements/custom_fields/'.$page);
    	} else {
			$this->controller->render('/Elements/custom_fields/'.$page);
    	}
    }

    public function checkModule() {
    	if(isset($this->settings['models']['Module'])) {
			$params = $this->controller->params->named;

	    	foreach ($params as $key => $value) {
	    		$this->controller->set('Custom_' . ucfirst($key) . 'Id', $value);
	    	}

			$Module = ClassRegistry::init($this->Module->alias);
			$modules = $Module->find('list' , array(
				'conditions' => array($this->Module->alias.'.visible' => 1),
				'order' => array($this->Module->alias.'.order')
			));
			$selectedModule = isset($params['module']) ? $params['module'] : key($modules);
			
			$moduleOptions = array();
			foreach ($modules as $key => $module) {
				$moduleOptions['module:' . $key] = $module;
			}

			$this->controller->set('moduleOptions', $moduleOptions);
		} else {
			$selectedModule = null;
		}

		return $selectedModule;
    }

    public function index() {
		$params = $this->controller->params->named;
		$selectedModule = $this->checkModule();

		if(!is_null($selectedModule)) {
			$groupsConditions = array(
				$this->Group->alias.'.'.Inflector::underscore($this->Module->alias).'_id' => $selectedModule
			);
		} else {
			$groupsConditions = array();
		}

		$groups = $this->Group->find('list', array(
			'conditions' => $groupsConditions,
			'order' => array(
				$this->Group->alias.'.name'
			)
		));

		if(!empty($groups)) {
			$selectedGroup = isset($params['group']) ? $params['group'] : key($groups);

			$groupOptions = array();
			foreach ($groups as $key => $group) {
				$groupOptions['group:' . $key] = $group;
			}

			$this->Field->contain();
			$data = $this->Field->find('all', array(
				'conditions' => array(
					$this->Field->alias.'.'.Inflector::underscore($this->Group->alias).'_id' => $selectedGroup
				),
				'order' => array(
					$this->Field->alias.'.order', 
					$this->Field->alias.'.name'
				)
			));

			$this->Session->write($this->Group->alias.'.id', $selectedGroup);

			$this->controller->set('groupOptions', $groupOptions);
			$this->controller->set('selectedGroup', $selectedGroup);
			$this->controller->set('data', $data);
		} else {
			$this->Message->alert('general.noData');
		}

		$this->controller->set('selectedModule', $selectedModule);
    }

    public function view($id=0) {
    	$params = $this->controller->params->named;

    	if ($this->Field->exists($id)) {
			$data = $this->Field->findById($id);
			$this->Session->write($this->Field->alias.'.id', $id);
			$this->controller->set('data', $data);
		} else {
			$this->Message->alert('general.notExists');
			$params['action'] = 'index';
			return $this->controller->redirect($params);
		}
    }

    public function add() {
    	$params = $this->controller->params->named;

    	if ($this->controller->request->is(array('post', 'put'))) {
    		$data = $this->controller->request->data;
    		
    		if ($data['submit'] == 'reload') {

			} else if(isset($this->FieldOption) && $data['submit'] == $this->FieldOption->alias) {
				$this->controller->request->data[$this->FieldOption->alias][] =array(
					'value' => '',
					'visible' => 1
				);
			} else if(isset($this->TableColumn) && $data['submit'] == $this->TableColumn->alias) {
				$this->controller->request->data[$this->TableColumn->alias][] =array(
					'name' => '',
					'visible' => 1
				);
			} else if(isset($this->TableRow) && $data['submit'] == $this->TableRow->alias) {
				$this->controller->request->data[$this->TableRow->alias][] =array(
					'name' => '',
					'visible' => 1
				);
    		} else {
    			if(isset($this->FieldOption) && isset($this->controller->request->data[$this->FieldOption->alias])) {
					foreach ($this->controller->request->data[$this->FieldOption->alias] as $key => $obj) {
						if(empty($obj['value'])) {
							unset($this->controller->request->data[$this->FieldOption->alias][$key]);
						}
					}
				}
				if(isset($this->TableColumn) && isset($this->controller->request->data[$this->TableColumn->alias])) {
					foreach ($this->controller->request->data[$this->TableColumn->alias] as $key => $obj) {
						if(empty($obj['name'])) {
							unset($this->controller->request->data[$this->TableColumn->alias][$key]);
						}
					}
				}
				if(isset($this->TableRow) && isset($this->controller->request->data[$this->TableRow->alias])) {
					foreach ($this->controller->request->data[$this->TableRow->alias] as $key => $obj) {
						if(empty($obj['name'])) {
							unset($this->controller->request->data[$this->TableRow->alias][$key]);
						}
					}
				}

	    		if ($this->Field->saveAll($this->controller->request->data)) {
					$this->Message->alert('general.add.success');
					$params['action'] = 'index';
					return $this->controller->redirect($params);
				} else {
					$this->log($this->Field->validationErrors, 'debug');
					$this->Message->alert('general.add.failed');
				}
    		}
    	}
    	$this->controller->render('edit');
    }

    public function edit($id=0) {
		if ($this->Field->exists($id)) {
			$params = $this->controller->params->named;

			$fieldContains = array();
			$fieldContains = isset($this->FieldOption) ? array_merge(array($this->FieldOption->alias), $fieldContains) : $fieldContains;
			$fieldContains = isset($this->TableColumn) ? array_merge(array($this->TableColumn->alias), $fieldContains) : $fieldContains;
			$fieldContains = isset($this->TableRow) ? array_merge(array($this->TableRow->alias), $fieldContains) : $fieldContains;
			$this->Field->contain($fieldContains);

			$data = $this->Field->findById($id);
			
			if ($this->controller->request->is(array('post', 'put'))) {
				$data = $this->controller->request->data;

				if ($data['submit'] == 'reload') {

				} else if(isset($this->FieldOption) && $data['submit'] == $this->FieldOption->alias) {
					$this->controller->request->data[$this->FieldOption->alias][] =array(
						'value' => '',
						'visible' => 1
					);
				} else if(isset($this->TableColumn) && $data['submit'] == $this->TableColumn->alias) {
					$this->controller->request->data[$this->TableColumn->alias][] =array(
						'name' => '',
						'visible' => 1
					);
				} else if(isset($this->TableRow) && $data['submit'] == $this->TableRow->alias) {
					$this->controller->request->data[$this->TableRow->alias][] =array(
						'name' => '',
						'visible' => 1
					);
				} else {
					if(isset($this->FieldOption) && isset($this->controller->request->data[$this->FieldOption->alias])) {
						foreach ($this->controller->request->data[$this->FieldOption->alias] as $key => $obj) {
							if(empty($obj['value'])) {
								unset($this->controller->request->data[$this->FieldOption->alias][$key]);
							}
						}
					}
					if(isset($this->TableColumn) && isset($this->controller->request->data[$this->TableColumn->alias])) {
						foreach ($this->controller->request->data[$this->TableColumn->alias] as $key => $obj) {
							if(empty($obj['name'])) {
								unset($this->controller->request->data[$this->TableColumn->alias][$key]);
							}
						}
					}
					if(isset($this->TableRow) && isset($this->controller->request->data[$this->TableRow->alias])) {
						foreach ($this->controller->request->data[$this->TableRow->alias] as $key => $obj) {
							if(empty($obj['name'])) {
								unset($this->controller->request->data[$this->TableRow->alias][$key]);
							}
						}
					}

					$dataSource = $this->Field->getDataSource();
					$dataSource->begin();
					if(isset($this->FieldOption)) {
						$this->FieldOption->updateAll(
						    array($this->FieldOption->alias.'.visible' => 0),
						    array($this->FieldOption->alias.'.'.Inflector::underscore($this->Field->alias).'_id' => $id)
						);
					}
					if(isset($this->TableColumn)) {
						$this->TableColumn->updateAll(
						    array($this->TableColumn->alias.'.visible' => 0),
						    array($this->TableColumn->alias.'.'.Inflector::underscore($this->Field->alias).'_id' => $id)
						);
					}
					if(isset($this->TableRow)) {
						$this->TableRow->updateAll(
						    array($this->TableRow->alias.'.visible' => 0),
						    array($this->TableRow->alias.'.'.Inflector::underscore($this->Field->alias).'_id' => $id)
						);
					}

					if ($this->Field->saveAll($this->controller->request->data)) {
						$dataSource->commit();
						$this->Message->alert('general.edit.success');
						$params = array_merge(array('action' => 'view', $id), $params);
						return $this->controller->redirect($params);
					} else {
						$dataSource->rollback();
						$this->log($this->Field->validationErrors, 'debug');
						$this->Message->alert('general.edit.failed');
					}
				}
			} else {
				$selectedFieldType = $data[$this->Field->alias]['type'];
				$mandatoryDisabled = $this->getMandatoryDisabled($selectedFieldType);
				$uniqueDisabled = $this->getUniqueDisabled($selectedFieldType);

				$this->controller->set('mandatoryDisabled', $mandatoryDisabled);
				$this->controller->set('uniqueDisabled', $uniqueDisabled);
				$this->controller->request->data = $data;
			}
		} else {
			$this->Message->alert('general.notExists');
			$params['action'] = 'index';
			return $this->controller->redirect($params);
		}
    }

    public function delete() {
		$params = $this->controller->params->named;

    	if ($this->Session->check($this->Field->alias . '.id')) {
			$id = $this->Session->read($this->Field->alias . '.id');

			$dataSource = $this->Field->getDataSource();
			$dataSource->begin();
			if(isset($this->FieldOption)) {
				$this->FieldOption->updateAll(
				    array($this->FieldOption->alias.'.visible' => 0),
				    array($this->FieldOption->alias.'.'.Inflector::underscore($this->Field->alias).'_id' => $id)
				);
			}
			if(isset($this->TableColumn)) {
				$this->TableColumn->updateAll(
				    array($this->TableColumn->alias.'.visible' => 0),
				    array($this->TableColumn->alias.'.'.Inflector::underscore($this->Field->alias).'_id' => $id)
				);
			}
			if(isset($this->TableRow)) {
				$this->TableRow->updateAll(
				    array($this->TableRow->alias.'.visible' => 0),
				    array($this->TableRow->alias.'.'.Inflector::underscore($this->Field->alias).'_id' => $id)
				);
			}

			if($this->Field->delete($id)) {
				$dataSource->commit();
				$this->Message->alert('general.delete.success');
			} else {
				$dataSource->rollback();
				$this->log($this->Field->validationErrors, 'debug');
				$this->Message->alert('general.delete.failed');
			}
			$this->Session->delete($this->Field->alias.'.id');
			$params['action'] = 'index';
			return $this->controller->redirect($params);
		}
    }

    public function reorder() {
    	$params = $this->controller->params->named;
		$selectedModule = $this->checkModule();

		if(!is_null($selectedModule)) {
			$groupsConditions = array(
				$this->Group->alias.'.'.Inflector::underscore($this->Module->alias).'_id' => $selectedModule
			);
		} else {
			$groupsConditions = array();
		}

		$groups = $this->Group->find('list', array(
			'conditions' => $groupsConditions,
			'order' => array(
				$this->Group->alias.'.name'
			)
		));

		if(!empty($groups)) {
			$selectedGroup = isset($params['group']) ? $params['group'] : key($groups);
			$groupOptions = array();
			foreach ($groups as $key => $template) {
				$groupOptions['group:' . $key] = $template;
			}

			$this->Field->contain();
			$data = $this->Field->find('all', array(
				'conditions' => array(
					$this->Field->alias.'.'.Inflector::underscore($this->Group->alias).'_id' => $selectedGroup,
					$this->Field->alias.'.visible' => 1
				),
				'order' => array(
					$this->Field->alias.'.order', 
					$this->Field->alias.'.name'
				)
			));

			$this->controller->set('groupOptions', $groupOptions);
			$this->controller->set('selectedGroup', $selectedGroup);
			$this->controller->set('data', $data);
		} else {
			$this->Message->alert('general.noData');
		}

		$this->controller->set('selectedModule', $selectedModule);
		$this->doRender('reorder');
    }

	public function moveOrder($groupId=0) {
		$params = $this->controller->params->named;

		$data = $this->controller->request->data;
		$conditions = array($this->Field->alias.'.'.Inflector::underscore($this->Group->alias).'_id' => $groupId);

		$id = $data[$this->Field->alias]['id'];
		$idField = $this->Field->alias.'.id';
		$orderField = $this->Field->alias.'.order';
		$move = $data[$this->Field->alias]['move'];
		$order = $this->Field->field('order', array('id' => $id));
		$idConditions = array_merge(array($idField => $id), $conditions);
		$updateConditions = array_merge(array($idField . ' <>' => $id), $conditions);
		
		$this->fixOrder($conditions);
		if($move === 'up') {
			$this->Field->updateAll(array($orderField => $order-1), $idConditions);
			$updateConditions[$orderField] = $order-1;
			$this->Field->updateAll(array($orderField => $order), $updateConditions);
		} else if($move === 'down') {
			$this->Field->updateAll(array($orderField => $order+1), $idConditions);
			$updateConditions[$orderField] = $order+1;
			$this->Field->updateAll(array($orderField => $order), $updateConditions);
		} else if($move === 'first') {
			$this->Field->updateAll(array($orderField => 1), $idConditions);
			$updateConditions[$orderField . ' <'] = $order;
			$this->Field->updateAll(array($orderField => $orderField . ' + 1'), $updateConditions);
		} else if($move === 'last') {
			$count = $this->Field->find('count', array('conditions' => $conditions));
			$this->Field->updateAll(array($orderField => $count), $idConditions);
			$updateConditions[$orderField . ' >'] = $order;
			$this->Field->updateAll(array($orderField => $orderField . ' - 1'), $updateConditions);
		}

		$params = array_merge(array('action' => 'reorder', $groupId), $params);
		return $this->controller->redirect($params);
    }

    public function fixOrder($conditions) {
		$count = $this->Field->find('count', array('conditions' => $conditions));
		if($count > 0) {
			$list = $this->Field->find('list', array(
				'conditions' => $conditions,
				'order' => array($this->Field->alias.'.order')
			));
			$order = 1;
			foreach($list as $id => $name) {
				$this->Field->id = $id;
				$this->Field->saveField('order', $order++);
			}
		}
	}

	public function preview() {
		$params = $this->controller->params->named;
		$selectedModule = $this->checkModule();

		if(!is_null($selectedModule)) {
			$groupsConditions = array(
				$this->Group->alias.'.'.Inflector::underscore($this->Module->alias).'_id' => $selectedModule
			);
		} else {
			$groupsConditions = array();
		}

		$groups = $this->Group->find('list', array(
			'conditions' => $groupsConditions,
			'order' => array(
				$this->Group->alias.'.name'
			)
		));

		if(!empty($groups)) {
			$selectedGroup = isset($params['group']) ? $params['group'] : key($groups);
			$groupOptions = array();
			foreach ($groups as $key => $template) {
				$groupOptions['group:' . $key] = $template;
			}

			$fieldContains = array();
			$fieldContains = isset($this->FieldOption) ? array_merge(array($this->FieldOption->alias), $fieldContains) : $fieldContains;
			$fieldContains = isset($this->TableColumn) ? array_merge(array($this->TableColumn->alias), $fieldContains) : $fieldContains;
			$fieldContains = isset($this->TableRow) ? array_merge(array($this->TableRow->alias), $fieldContains) : $fieldContains;
			$this->Field->contain($fieldContains);
			$data = $this->Field->find('all', array(
				'conditions' => array(
					$this->Field->alias.'.'.Inflector::underscore($this->Group->alias).'_id' => $selectedGroup,
					$this->Field->alias.'.visible' => 1
				),
				'order' => array(
					$this->Field->alias.'.order', 
					$this->Field->alias.'.name'
				)
			));
			$model = $this->Field->alias;
			$modelOption = isset($this->FieldOption) ? $this->FieldOption->alias : '';
			$modelValue = '';
			$modelRow = isset($this->TableRow) ? $this->TableRow->alias : '';
			$modelColumn = isset($this->TableColumn) ? $this->TableColumn->alias : '';
			$modelCell = '';
			$action = 'edit';

			$this->Session->write($this->Group->alias.'.id', $selectedGroup);

			$this->controller->set('groupOptions', $groupOptions);
			$this->controller->set('selectedGroup', $selectedGroup);
			$this->controller->set('data', $data);
			$this->controller->set('model', $model);
			$this->controller->set('modelOption', $modelOption);
			$this->controller->set('modelValue', $modelValue);
			$this->controller->set('modelRow', $modelRow);
			$this->controller->set('modelColumn', $modelColumn);
			$this->controller->set('modelCell', $modelCell);
			$this->controller->set('action', $action);
		} else {
			$this->Message->alert('general.noData');
		}

		$this->controller->set('selectedModule', $selectedModule);
		$this->doRender('preview');
    }
}
