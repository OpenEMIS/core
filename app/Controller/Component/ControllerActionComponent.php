<?php
class ControllerActionComponent extends Component {
	private $plugin;
	private $controller;
	private $model = null;
	private $triggerFrom = 'Controller';
	private $currentAction;
	private $defaultActions = array('index', 'add', 'view', 'edit', 'remove');
	public $autoRender = true;

	public $components = array('Session', 'Message');

	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		if (isset($this->settings['model'])) {
			$model = $this->getModel($this->settings['model']);
			$this->model = $controller->{$model['model']};
			$this->getFields($this->model);
		}
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Controller $controller) {
		$action = $controller->action;
		if (!method_exists($controller, $action)) { // method cannot be found in controller
			if (in_array($action, $this->defaultActions)) { // default actions
				$this->currentAction = $controller->request->params['action'];
				$controller->request->params['action'] = 'ComponentAction';
			} else { // check if it's a model action
				foreach ($controller->uses as $model) {
					$split = explode('.', $model);
					$this->plugin = null;
					$modelClass = $model;
					if (count($split) > 1) {
						$this->plugin = $split[0];
						$modelClass = $split[1];
					}

					if ($action === $modelClass) { // model class found
						$paramPass = $controller->params->pass;
						$currentAction = 'index';
						if (!empty($paramPass)) {
							$currentAction = array_shift($paramPass);
						}
						$this->model = $controller->{$modelClass};
						$this->currentAction = $currentAction;
						$controller->request->params['action'] = 'ComponentAction';
						$this->initComponentsForModel();
						$this->getFields($this->model);
						if (method_exists($this->model, 'beforeAction')) {
							$this->model->beforeAction();
						}
						$this->triggerFrom = 'Model';
						break;
						//pr('test');die;
						//pr($controller->params->pass);die;
						//if (method_exists())
					}
				}
			}
		}

		if (!empty($this->model)) {
			$controller->set('model', $this->model->alias);
		}
	}

	// Is called after the controller executes the requested action’s logic, but before the controller’s renders views and layout.
	public function beforeRender(Controller $controller) {
		if (!is_null($this->model) && !empty($this->model->fields)) {
			$controller->request->params['action'] = $this->currentAction;
			uasort($this->model->fields, array($this, 'sortFields'));
			$controller->set('_fields', $this->model->fields);
			$controller->set('_triggerFrom', $this->triggerFrom);

			if ($this->triggerFrom == 'Model' && method_exists($this->model, 'afterAction')) {
				$this->model->afterAction();
			}
		}
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
		$paramsPass = $this->controller->params->pass;

		$result = null;
		if ($this->triggerFrom == 'Controller') {
			$result = call_user_func_array(array($this, $this->currentAction), $paramsPass);
		} else if ($this->triggerFrom == 'Model') {
			$result = call_user_func_array(array($this->model, $this->currentAction), $paramsPass);
			if (empty($this->plugin)) {
				$path = APP . 'View' . DS . $this->controller->name . DS;
				$ctp = $this->model->alias . DS . $this->currentAction;
				if (file_exists($path . DS . $ctp . '.ctp')) {
					$this->controller->render($ctp);
				}
			} else {

			}
		}

		if ($this->autoRender) {
			$view = $this->currentAction == 'add' ? 'edit' : $this->currentAction;
			$this->controller->render('../Elements/ControllerAction/' . $view . '_template');
		}
	}

	public function index() {
		$model = $this->model;
		$model->contain();
		$data = $model->find('all');
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
				$pass = $this->controller->params->pass;
				$params = isset($this->controller->viewVars['params']) ? $this->controller->viewVars['params'] : array();

				$action = array('action' => isset($params['back']) ? $params['back'] : 'index');
				if ($this->triggerFrom == 'Model') {
					unset($pass[0]);
					$action = array('action' => get_class($model));
					$action[] = isset($params['back']) ? $params['back'] : 'index';
				}
				$action = array_merge($action, $pass);
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
			$pass = $this->controller->params->pass;
			$params = isset($this->controller->viewVars['params']) ? $this->controller->viewVars['params'] : array();

			$action = array('action' => isset($params['back']) ? $params['back'] : 'index');
			if ($this->triggerFrom == 'Model') {
				unset($pass[0]);
				$action = array('action' => get_class($model));
				$action[] = isset($params['back']) ? $params['back'] : 'index';
			}
			
			$action = array_merge($action, $pass);
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
