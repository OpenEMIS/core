<?php
class ControllerActionComponent extends Component {
	private $controller;
	private $model = null;
	private $triggerFrom = 'Controller';
	private $currentAction;
	private $defaultActions = array('add', 'view', 'edit', 'delete');
	public $autoRender = true;

	public $components = array('Session', 'Message');

	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller = $controller;
		if (isset($this->settings['model'])) {
			$model = $this->getModel($this->settings['model']);
			$this->model = $controller->{$model['model']};
			$this->getFields($this->model);
			$controller->set('model', $this->model->alias);
		}
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Controller $controller) {
		$action = $controller->action;
		if (!method_exists($controller, $action)) { // method cannot be found in controller
			if (in_array($action, $this->defaultActions)) { // default actions
				$this->currentAction = $controller->request->params['action'];
				$controller->request->params['action'] = 'ComponentAction';
				call_user_func_array(array($this, $action), $controller->params->pass);
				if ($this->autoRender) {
					$page = $action;
					if ($action == 'add') {
						$page = 'edit';
					}
					$controller->render('../Elements/templates/' . $page);
				}
			} else { // check if it's a model action
				foreach ($controller->uses as $model) {
					$split = explode('.', $model);
					$plugin = null;
					$modelClass = $model;
					if (count($split) > 1) {
						$plugin = $split[0];
						$modelClass = $split[1];
					}

					if ($action === $modelClass) { // model class found
						$paramPass = 'index';
						pr($controller->params->pass);die;
						//if (method_exists())
					}
					$this->triggerFrom = 'Model';
				}
			}
			
		}
	}

	// Is called after the controller executes the requested action’s logic, but before the controller’s renders views and layout.
	public function beforeRender(Controller $controller) {
		if (!is_null($this->model) && !empty($this->model->fields)) {
			uasort($this->model->fields, array($this, 'sortFields'));
			$controller->request->params['action'] = $this->currentAction;
			$controller->set('_fields', $this->model->fields);
			$controller->set('_triggerFrom', $this->triggerFrom);

		}
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

	public function edit($id=0) {
		$model = $this->model;
		if ($model->exists($id)) {
			$data = $model->findById($id);
			
			if ($this->controller->request->is(array('post', 'put'))) {
				if ($model->saveAll($this->controller->request->data)) {
					$this->Message->alert('general.edit.success');
					$pass = $this->controller->params->pass;

					$action = array('action' => 'view');
					if ($this->triggerFrom == 'Model') {
						unset($pass[0]);
						$action = array('action' => get_class($model), 'view');
					}
					
					$action = array_merge($action, $pass);
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
		$fields = $this->fields;
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
		uasort($fields, array($this->alias, 'sortFields'));
		$this->fields = $fields;
	}
	
	public static function sortFields($a, $b) {
		return $a['order'] >= $b['order'];
	}
}
