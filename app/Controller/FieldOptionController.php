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

App::uses('AppController', 'Controller');

class FieldOptionController extends AppController {
	public $uses = array(
		'FieldOption',
		'FieldOptionValue'
	);
	public $optionList = array();
	public $options = array();
	public $model = null;

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index'));
		$this->Navigation->addCrumb('Field Options', array('controller' => 'FieldOption', 'action' => 'index'));
		$this->optionList = $this->FieldOption->findOptions(true);
		// change the index to start from 1
		array_unshift($this->optionList, array());
		unset($this->optionList[0]);
		$this->options = $this->buildOptions($this->optionList);
	}

	private function buildOptions($list) {
		$options = array();
		foreach ($list as $key => $values) {
			$key = $key;
			if (!empty($values['parent'])) {
				$parent = __($values['parent']);
				if (!array_key_exists($parent, $options)) {
					$options[$parent] = array();
				}
				$options[$parent][$key] = __($values['name']);
			} else {
				$options[$key] = __($values['name']);
			}
		}
		return $options;
	}

	public function doRender() {
		$views = $this->model->getRender($this);

		if (in_array($this->action, $views)) {
			$this->autoRender = false;
			$this->render($this->model->alias . '/' . $this->action);
		}
	}

	public function index($selectedOption = 1) {
		if (!array_key_exists($selectedOption, $this->optionList)) {
			$selectedOption = 1;
		}
		$options = $this->options;
		$obj = $this->optionList[$selectedOption];
		$this->FieldOptionValue->setParent($obj);
		$this->model = $this->FieldOptionValue->getModel();
		$model = $this->model->alias;
		$header = $this->FieldOptionValue->getHeader();
		$subOptions = $this->FieldOptionValue->getSubOptions();
		$conditions = array();
		if (!empty($subOptions)) {
			$conditionId = $this->FieldOptionValue->getModel()->getConditionId();
			$selectedSubOption = $this->FieldOptionValue->getFirstSubOptionKey($subOptions);
			if (isset($this->request->params['named'][$conditionId])) {
				$selectedSubOption = $this->request->params['named'][$conditionId];
			}
			if ($selectedSubOption != 0) {
				$conditions[$conditionId] = $selectedSubOption;
			}
			$this->set(compact('subOptions', 'selectedSubOption', 'conditionId'));
		}
		$data = $this->FieldOptionValue->getAllValues($conditions);
		$fields = $this->FieldOptionValue->getValueFields();

		$this->set(compact('data', 'header', 'selectedOption', 'options', 'model', 'fields'));
		$this->Navigation->addCrumb($header);
		$this->doRender();
	}

	public function indexEdit($selectedOption = 1) {
		if (!array_key_exists($selectedOption, $this->optionList)) {
			$selectedOption = 1;
		}
		$options = $this->options;
		$obj = $this->optionList[$selectedOption];
		$this->FieldOptionValue->setParent($obj);
		$this->model = $this->FieldOptionValue->getModel();
		$model = $this->model->alias;
		$header = $this->FieldOptionValue->getHeader();
		$subOptions = $this->FieldOptionValue->getSubOptions();
		$conditions = array();
		if (!empty($subOptions)) {
			$conditionId = $this->FieldOptionValue->getModel()->getConditionId();
			$selectedSubOption = $this->FieldOptionValue->getFirstSubOptionKey($subOptions);
			if (isset($this->request->params['named'][$conditionId])) {
				$selectedSubOption = $this->request->params['named'][$conditionId];
			}
			$conditions[$conditionId] = $selectedSubOption;
			$this->set(compact('selectedSubOption', 'conditionId'));
		}
		$data = $this->FieldOptionValue->getAllValues($conditions);
		if ($model === 'FieldOptionValue') {
			$conditions['field_option_id'] = $obj['id'];
		}
		$this->set(compact('data', 'header', 'selectedOption', 'options', 'model', 'conditions'));
		$this->Navigation->addCrumb($header);
		$this->doRender();
	}

	public function reorder($selectedOption = 1) {
		if ($this->request->is('post') || $this->request->is('put')) {
			$obj = $this->optionList[$selectedOption];
			$this->FieldOptionValue->setParent($obj);
			$data = $this->request->data;
			$model = $this->FieldOptionValue->getModel();
			$conditions = array();
			$redirect = array('action' => 'indexEdit', $selectedOption);

			if (!empty($this->request->params['named'])) {
				$conditionId = key($this->request->params['named']);
				$selectedSubOption = current($this->request->params['named']);
				$conditions[$conditionId] = $selectedSubOption;
				$redirect = array_merge($redirect, $conditions);
			}

			$model->reorder($data, $conditions);
			return $this->redirect($redirect);
		}
	}

	public function add($selectedOption = 1) {
		if (!array_key_exists($selectedOption, $this->optionList)) {
			$selectedOption = 1;
		}

		$obj = $this->optionList[$selectedOption];
		$this->FieldOptionValue->setParent($obj);
		$header = $this->FieldOptionValue->getHeader();
		$fields = $this->FieldOptionValue->getValueFields();
		$model = $this->FieldOptionValue->getModel();
		$this->model = $model;
		$selectedSubOption = false;
		$conditionId = false;

		// get suboption value from index page and set it as the default option
		if (!empty($this->request->params['named'])) {
			$conditionId = key($this->request->params['named']);
			$selectedSubOption = current($this->request->params['named']);
			$this->set(compact('conditionId', 'selectedSubOption'));
		}

		if ($this->request->is(array('post', 'put'))) {
			if ($model->postAdd($this) === false) {
				if ($this->FieldOptionValue->saveValue($this->request->data)) {
					$redirect = array('action' => 'index', $selectedOption);
					if ($conditionId !== false) {
						$redirect = array_merge($redirect, array($conditionId => $this->request->data[$model->alias][$conditionId]));
					}
					$this->Message->alert('general.add.success');
					return $this->redirect($redirect);
				} else {
					$this->Message->alert('general.add.failed');
				}
			}
		}
		$this->set('model', $model->alias);
		$this->set(compact('header', 'fields', 'selectedOption'));
		$this->Navigation->addCrumb($header);
		$this->doRender();
	}

	public function view($selectedOption = 1, $selectedValue = 0) {
		if (!array_key_exists($selectedOption, $this->optionList)) {
			$selectedOption = 1;
		}
		$obj = $this->optionList[$selectedOption];

		$plugin = $obj['plugin'];
		$code = $obj['code'];
		if (!is_null($plugin)) {
			$ModelClass = ClassRegistry::init($plugin.'.'.$code);
		} else {
			$ModelClass = ClassRegistry::init($code);
		}
		$allowDelete = (isset($ModelClass->allowDelete))? $ModelClass->allowDelete: false;

		$this->FieldOptionValue->setParent($obj);
		$this->model = $this->FieldOptionValue->getModel();
		$data = $this->FieldOptionValue->getValue($selectedValue);
		$selectedSubOption = false;
		$conditionId = false;

		if (!empty($this->request->params['named'])) {
			$conditionId = key($this->request->params['named']);
			$selectedSubOption = current($this->request->params['named']);
			$this->set(compact('conditionId', 'selectedSubOption'));
		}

		if (empty($data)) {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'index', $selectedOption));
		}
		$header = $this->FieldOptionValue->getHeader();
		$fields = $this->FieldOptionValue->getValueFields();
		$this->set('model', $this->model->alias);
		$this->set(compact('data', 'header', 'fields', 'selectedOption', 'selectedValue', 'allowDelete'));
		$this->Navigation->addCrumb($header);
		$this->doRender();
	}

	public function edit($selectedOption = 1, $selectedValue = 0) {
		if ($selectedValue == 0) {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'index', $selectedOption));
		}
		if (!array_key_exists($selectedOption, $this->optionList)) {
			$selectedOption = 1;
		}
		$obj = $this->optionList[$selectedOption];
		$this->FieldOptionValue->setParent($obj);
		$model = $this->FieldOptionValue->getModel();
		$this->model = $model;
		$selectedSubOption = false;
		$conditionId = false;

		if (!empty($this->request->params['named'])) {
			$conditionId = key($this->request->params['named']);
			$selectedSubOption = current($this->request->params['named']);
			$this->set(compact('conditionId', 'selectedSubOption'));
		}

		$currentValues = $this->FieldOptionValue->getValue($selectedValue);
		if ($this->request->is(array('post', 'put'))) {
			if (isset($currentValues[$model->alias]['type'])) {
				$this->request->data[$model->alias]['type'] = $currentValues[$model->alias]['type'];
			}
			if ($model->postEdit($this) === false) {
				if ($this->FieldOptionValue->saveValue($this->request->data)) {
					$redirect = array('action' => 'view', $selectedOption, $selectedValue);
					if ($conditionId !== false) {
						$redirect = array_merge($redirect, array($conditionId => $this->request->data[$model->alias][$conditionId]));
					}
					$this->Message->alert('general.edit.success');
					return $this->redirect($redirect);
				} else {
					$this->Message->alert('general.edit.failed');
				}
			}
		} else {
			$this->request->data = $this->FieldOptionValue->getValue($selectedValue);
		}
		$header = $this->FieldOptionValue->getHeader();
		$fields = $this->FieldOptionValue->getValueFields($this);
		$this->set('model', $model->alias);
		$this->set(compact('header', 'fields', 'selectedOption', 'selectedValue'));
		$this->Navigation->addCrumb($header);
		$this->doRender();
	}

	public function delete($selectedOption = 1, $selectedValue = 0) {
		if ($selectedValue == 0) {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'index', $selectedOption));
		}

		if(!array_key_exists($selectedOption, $this->optionList)) {
			// field option doesnt exist
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'index'));
		}

		$obj = $this->optionList[$selectedOption];
		$this->FieldOptionValue->setParent($obj);

		$plugin = $obj['plugin'];
		$code = $obj['code'];
		if (!is_null($plugin)) {
			$ModelClass = ClassRegistry::init($plugin.'.'.$code);
		} else {
			$ModelClass = ClassRegistry::init($code);
		}
		$allowDelete = (isset($ModelClass->allowDelete))? $ModelClass->allowDelete: false;
		if (!$allowDelete) {
			$this->Message->alert('general.delete.failed');
			return $this->redirect(array('action' => 'view', $selectedOption, $selectedValue));
		}

		$allFieldOptionValues = $ModelClass->getList(array('listOnly' => true, 'visibleOnly' => true));

		if (array_key_exists($selectedValue, $allFieldOptionValues)) {
			// unset only if field option exists in list
			unset($allFieldOptionValues[$selectedValue]);
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'index', $selectedOption));
		}

		$model = $ModelClass->alias;
		$ModelClass->recursive = -1;
		$currentFieldOptionValue = $ModelClass->findById($selectedValue);

		// if no legal records to migrate to ... they are not allowed to delete
		if (empty($allFieldOptionValues)) {
			$this->Message->alert('general.delete.cannotDeleteOnlyRecord');
			return $this->redirect(array('action' => 'view', $selectedOption, $selectedValue));
		}

		$modifyForeignKey = array();
		$hasManyArray = $ModelClass->hasMany;
		
		foreach ($hasManyArray as $key => $value) {
			$CurrModelClass = ClassRegistry::init($value['className']);
			$foreignKeyId = Inflector::underscore($code)."_id";
			$modifyForeignKey[$key] = $CurrModelClass->find('count',
				array(
					'recursive' => -1,
					'conditions' => array(
						$CurrModelClass->alias . '.' .$foreignKeyId => $selectedValue
					)
				)
			);
		}

		if ($this->request->is(array('post', 'put'))) {
			$convertValue = $this->request->data[$model]['convert_to'];
			foreach ($modifyForeignKey as $key => $value) {
				$CurrModelClass = ClassRegistry::init($key);
				$foreignKeyId = Inflector::underscore($code)."_id";
				$CurrModelClass->updateAll(
					array($key.'.'.$foreignKeyId => $convertValue),
					array($key.'.'.$foreignKeyId => $selectedValue)
				);
			}
			if ($ModelClass->delete($selectedValue)) {
				$this->Message->alert('general.delete.success');
				return $this->redirect(array('action' => 'index', $selectedOption));
			}
		}
		
		$header = $this->FieldOptionValue->getHeader();
		
		$this->set('allOtherFieldOptionValues', $allFieldOptionValues);
		$this->set(compact('header', 'currentFieldOptionValue', 'modifyForeignKey', 'selectedOption', 'selectedValue', 'allowDelete', 'model'));

		$this->Navigation->addCrumb($header);

	}
}

