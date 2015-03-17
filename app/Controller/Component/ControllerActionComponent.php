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

ControllerActionComponent - Version 1.0.6
*/

class ControllerActionComponent extends Component {
	private $plugin;
	private $controller;
	private $model = null;
	private $triggerFrom = 'Controller';
	private $currentAction;
	private $ctpFolder;
	private $paramsPass;
	private $defaultActions = array('index', 'add', 'view', 'edit', 'remove');
	public $autoRender = true;
	public $autoProcess = true;

	public $components = array('Session', 'Message');

	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$this->paramsPass = $controller->params->pass;
		$this->currentAction = $controller->action;
		$this->ctpFolder = $controller->name;

		if (isset($this->settings['model'])) {
			$model = $this->getModel($this->settings['model']);
			$this->plugin = $model['plugin'];
			$this->model = $controller->{$model['model']};
			$this->getFields($this->model);
		}
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Controller $controller) {
		$action = $controller->action;
		if (!method_exists($controller, $action)) { // method cannot be found in controller
			if (in_array($action, $this->defaultActions)) { // default actions
				$controller->request->params['action'] = 'ComponentAction';
			} else { // check if it's a model action
				foreach ($controller->uses as $modelName) {
					$model = $this->getModel($modelName);
					$modelClass = $model['model'];

					if ($action === $modelClass) { // model class found
						$currentAction = 'index';
						if (!empty($this->paramsPass)) {
							$currentAction = array_shift($this->paramsPass);
						}
						$this->plugin = $model['plugin'];
						$this->model = $controller->{$modelClass};
						$this->getFields($this->model);
						$this->currentAction = $currentAction;
						$this->ctpFolder = $this->model->alias;
						$controller->request->params['action'] = 'ComponentAction';
						$this->initComponentsForModel();
						if (method_exists($this->model, 'beforeAction')) {
							$this->model->beforeAction();
						}
						$this->triggerFrom = 'Model';
						break;
					}
				}
			}
		}
	}

	// Is called after the controller executes the requested action’s logic, but before the controller’s renders views and layout.
	public function beforeRender(Controller $controller) {
		if (!is_null($this->model) && !empty($this->model->fields)) {
			$action = $this->currentAction;

			if ($this->triggerFrom == 'Controller') {
				
				
			} else if ($this->triggerFrom == 'Model') {
				$action = $this->model->alias;
				if (method_exists($this->model, 'afterAction')) {
					$this->model->afterAction();
				}
			}
			$controller->request->params['action'] = $action;

			uasort($this->model->fields, array($this, 'sortFields'));
			$controller->set('model', $this->model->alias);
			$controller->set('action', $this->currentAction);
			$controller->set('_fields', $this->model->fields);
			$controller->set('_triggerFrom', $this->triggerFrom);

			$this->initButtons();
		}
	}

	private function initButtons() {
		$controller = $this->controller;

		$named = $controller->request->params['named'];
		$pass = $controller->request->params['pass'];
		if ($this->triggerFrom == 'Model') {
			unset($pass[0]);
		}

		$buttons = array();

		foreach ($this->defaultActions as $action) {
			$actionUrl = array('action' => $action);

			if ($this->triggerFrom == 'Model') {
				$actionUrl['action'] = $this->model->alias;
				$actionUrl[] = $action;
			}
			$actionUrl = array_merge($actionUrl, $named, $pass);
			$buttons[$action] = array('url' => $actionUrl);
		}

		$backAction = 'index';
		if ($this->currentAction == 'edit') {
			$backAction = 'view';
		}

		$backUrl = array('action' => $backAction);
		if ($this->triggerFrom == 'Model') {
			$backUrl['action'] = $this->model->alias;
			$backUrl[] = $backAction;
		}
		$backUrl = array_merge($backUrl, $named, $pass);
		$buttons['back'] = array('url' => $backUrl);
		$controller->set('_buttons', $buttons);
	}

	private function initComponentsForModel() {
		$this->model->controller = $this->controller;
		$this->model->request = $this->controller->request;
		$this->model->Navigation = $this->controller->Navigation;
		$this->model->Session = $this->controller->Session;
		$this->model->Message = $this->controller->Message;
		$this->model->ControllerAction = $this;
		$this->model->action = $this->currentAction;
		$this->model->setVar = null;
	}

	public function processAction() {
		$result = null;
		if ($this->autoProcess) {
			if ($this->triggerFrom == 'Controller') {
				if (in_array($this->currentAction, $this->defaultActions)) {
					$result = call_user_func_array(array($this, $this->currentAction), $this->paramsPass);
				}
			} else if ($this->triggerFrom == 'Model') {
				if (method_exists($this->model, $this->currentAction)) {
					$result = call_user_func_array(array($this->model, $this->currentAction), $this->paramsPass);
				} else {
					if (in_array($this->currentAction, $this->defaultActions)) {
						$result = call_user_func_array(array($this, $this->currentAction), $this->paramsPass);
					}
				}
			}
		}

		$this->render();
	}

	public function render() {
		if (empty($this->plugin)) {
			$path = APP . 'View' . DS . $this->controller->name . DS;
		} else {
			$path = APP . 'Plugin' . DS . $this->plugin . DS . 'View' . DS;
		}
		$ctp = $this->ctpFolder . DS . $this->currentAction;

		if (file_exists($path . DS . $ctp . '.ctp')) {
			if ($this->autoRender) {
				$this->autoRender = false;
				$this->controller->render($ctp);
			}
		}

		if ($this->autoRender) {
			$view = $this->currentAction == 'add' ? 'edit' : $this->currentAction;
			$this->controller->render('../Elements/ControllerAction/' . $view . '_template');
		}
	}

	public function index() {
		$model = $this->model;
		$data = $model->find('all', array(
			'recursive' => 0
		));
		$this->controller->set('data', $data);
	}

	public function view($id=0) {
		$model = $this->model;
		if ($model->exists($id)) {
			$data = $model->findById($id);
			$this->Session->write($model->alias.'.id', $id);
			$this->controller->set(compact('data'));
		} else {
			$this->Message->alert('general.notExists');
			$action = 'index';
			if ($this->triggerFrom == 'Model') {
				$action = get_class($model);
			}
			return $this->controller->redirect(array('action' => $action));
		}
	}

	public function add() {
		$model = $this->model;
		if ($this->controller->request->is(array('post', 'put'))) {
			$model->create();
			if ($model->saveAll($this->controller->request->data)) {
				$this->Message->alert('general.add.success');
				$named = $this->controller->params['named'];
				$pass = $this->controller->params['pass'];
				$params = isset($this->controller->viewVars['params']) ? $this->controller->viewVars['params'] : array();

				$action = array('action' => isset($params['back']) ? $params['back'] : 'view');
				if ($this->triggerFrom == 'Model') {
					unset($pass[0]);
					$action = array('action' => get_class($model));
					$action[] = isset($params['back']) ? $params['back'] : 'view';
				}
				$action[] = $model->getLastInsertID();
				$action = array_merge($action, $named, $pass);
				return $this->controller->redirect($action);
			} else {
				$this->log($model->validationErrors, 'debug');
				$this->Message->alert('general.add.failed');
			}
		}
	}

	public function edit($id=0) {
		$model = $this->model;
		if ($model->exists($id)) {
			$data = $model->findById($id);
			
			if ($this->controller->request->is(array('post', 'put'))) {
				if ($model->saveAll($this->controller->request->data)) {
					$this->Message->alert('general.edit.success');
					$named = $this->controller->params['named'];
					$pass = $this->controller->params['pass'];
					$params = isset($this->controller->viewVars['params']) ? $this->controller->viewVars['params'] : array();

					$action = array('action' => isset($params['back']) ? $params['back'] : 'view');
					if ($this->triggerFrom == 'Model') {
						unset($pass[0]);
						$action = array('action' => get_class($model));
						$action[] = isset($params['back']) ? $params['back'] : 'view';
					}
					
					$action = array_merge($action, $named, $pass);
					return $this->controller->redirect($action);
				} else {
					$this->log($model->validationErrors, 'debug');
					$this->Message->alert('general.edit.failed');
				}
			} else {
				$this->controller->request->data = $data;
			}
		} else {
			$this->Message->alert('general.notExists');
			$action = 'index';
			if ($this->triggerFrom == 'Model') {
				$action = get_class($model);
			}
			return $this->controller->redirect(array('action' => $action));
		}
	}

	public function remove() {
		$model = $this->model;
		if ($this->Session->check($model->alias . '.id')) {
			$id = $this->Session->read($model->alias . '.id');
			if($model->delete($id)) {
				$this->Message->alert('general.delete.success');
			} else {
				$this->log($model->validationErrors, 'debug');
				$this->Message->alert('general.delete.failed');
			}
			$this->Session->delete($model->alias . '.id');
			$named = $this->controller->params['named'];
			$pass = $this->controller->params['pass'];
			$params = isset($this->controller->viewVars['params']) ? $this->controller->viewVars['params'] : array();

			$action = array('action' => isset($params['back']) ? $params['back'] : 'index');
			if ($this->triggerFrom == 'Model') {
				unset($pass[0]);
				$action = array('action' => get_class($model));
				$action[] = isset($params['back']) ? $params['back'] : 'index';
			}
			
			$action = array_merge($action, $named, $pass);
			return $this->controller->redirect($action);
		}
	}

	public function getModel($model) {
		$split = explode('.', $model);
		$plugin = null;
		$modelClass = $model;
		if (count($split) > 1) {
			$plugin = $split[0];
			$modelClass = $split[1];
		}
		return array('plugin' => $plugin, 'model' => $modelClass);
	}
	
	public function getFields($model) {
		$defaultFields = array('modified_user_id', 'modified', 'created_user_id', 'created', 'order');

		$fields = $model->schema();
		$belongsTo = $model->belongsTo;
		
		$i = 0;
		foreach($fields as $key => $obj) {
			$fields[$key]['order'] = $i++;
			$fields[$key]['visible'] = true;
			if (!array_key_exists('model', $fields[$key])) {
				$fields[$key]['model'] = $model->alias;
			}
		}
		
		$fields['id']['type'] = 'hidden';
		foreach ($defaultFields as $field) {
			if (array_key_exists($field, $fields)) {
				if ($field == 'modified_user_id') {
					$fields[$field]['type'] = $field;
					$fields[$field]['dataModel'] = 'ModifiedUser';
				}
				if ($field == 'created_user_id') {
					$fields[$field]['type'] = $field;
					$fields[$field]['dataModel'] = 'CreatedUser';
				}
				$fields[$field]['visible'] = array('view' => true, 'edit' => false);
				$fields[$field]['labelKey'] = 'general';
			}
		}
		$model->fields = $fields;
		return $fields;
	}
	
	public function setFieldOrder($field, $order) {
		$fields = $this->model->fields;
		$found = false;
		$count = 0;
		foreach ($fields as $key => $obj) {
			$count++;
			if (!isset($fields[$key]['order'])) {
				$fields[$key]['order'] = $count;
			}
			
			if ($found && $key !== $field) {
				$fields[$key]['order'] = $fields[$key]['order'] + 1;
			} else {
				if ($field === $key) {
					$found = true;
					$fields[$key]['order'] = $order;
				} else if ($fields[$key]['order'] == $order) {
					$found = true;
					$fields[$key]['order'] = $order + 1;
				}
			}
		}
		$fields[$field]['order'] = $order;
		uasort($fields, array($this, 'sortFields'));
		$this->model->fields = $fields;
	}
	
	public static function sortFields($a, $b) {
		return $a['order'] >= $b['order'];
	}
}
