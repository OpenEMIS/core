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

namespace ControllerAction\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Network\Response;
use Cake\Network\Exception\NotFoundException;

class ControllerActionComponent extends Component {
	private $plugin;
	private $controller;
	private $triggerFrom = 'Controller';
	private $currentAction;
	private $ctpFolder;
	private $paramsPass;
	private $defaultActions = ['index', 'add', 'view', 'edit', 'remove', 'download', 'reorder'];

	public $model = null;
	public $models = [];
	public $buttons = [];
	public $params = [];
	public $orderField = 'order';
	public $autoRender = true;
	public $autoProcess = true;
	public $removeStraightAway = true;
	public $ignoreFields = ['modified', 'created'];
	public $templatePath = '/ControllerAction/';
	public $indexActions = [
		'view' => array('class' => 'fa fa-eye'),
		'edit' => array('class' => 'fa fa-pencil'),
		'delete' => array('class' => 'fa fa-trash')
	];
	public $pageOptions = [10, 20, 30, 40, 50];
	public $Session;

	public $components = ['ControllerAction.Alert', 'Paginator'];

	// Is called before the controller's beforeFilter method.
	public function initialize(array $config) {
		if (array_key_exists('templates', $config)) {
			$this->templatePath = $config['templates'];
		}
		if (array_key_exists('ignoreFields', $config)) {
			$this->ignoreFields = array_merge($this->ignoreFields, $config['ignoreFields']);
		}
		$controller = $this->_registry->getController();
		$this->paramsPass = $this->request->params['pass'];
		$this->currentAction = $this->request->params['action'];
		$this->ctpFolder = $controller->name;

		$this->controller = $controller;
		$this->Session = $this->request->session();
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Event $event) {
		$controller = $this->controller;
		
		$action = $this->request->params['action'];
		if (!method_exists($controller, $action)) { // method cannot be found in controller
			if (in_array($action, $this->defaultActions)) { // default actions
				$this->currentAction = $action;
				$this->request->params['action'] = 'ComponentAction';
				$this->initComponentsForModel();
			} else { // check if it's a model action
				foreach ($this->models as $name => $attr) {
					if (strtolower($action) === strtolower($name)) { // model class found
						$currentAction = 'index';
						if (!empty($this->paramsPass)) {
							$currentAction = array_shift($this->paramsPass);
						}

						$actions = isset($attr['actions']) ? $attr['actions'] : $this->defaultActions;

						if (!in_array($currentAction, $actions)) {
							return $this->controller->redirect(['action' => $action]);
						}
						$this->model($attr['className'], $actions);
						$this->model->alias = $name;
						$this->currentAction = $currentAction;
						$this->ctpFolder = $this->model->alias();
						$this->request->params['action'] = 'ComponentAction';
						$this->initComponentsForModel();

						$event = new Event('ControllerAction.Controller.onInitialize', $this, ['model' => $this->model]);
						$event = $this->controller->eventManager()->dispatch($event);
						if ($event->isStopped()) { return $event->result; }
						
						$this->triggerFrom = 'Model';
						break;
					}
				}
			}
			$event = new Event('ControllerAction.Model.beforeAction', $this);
			$event = $this->model->eventManager()->dispatch($event);
			if ($event->isStopped()) { return $event->result; }
			$this->buildDefaultValidation();
		}
		if (!is_null($this->model)) {
			$this->initButtons();
		}
	}

	// Is called after the controller executes the requested action’s logic, but before the controller’s renders views and layout.
	public function beforeRender(Event $event) {
		$controller = $this->controller;
		if (!is_null($this->model) && !empty($this->model->fields)) {
			$action = $this->triggerFrom == 'Model' ? $this->model->alias : $this->currentAction;

			$this->renderFields();

			$event = new Event('ControllerAction.Model.afterAction', $this);
			$event = $this->model->eventManager()->dispatch($event);
			if ($event->isStopped()) { return $event->result; }
			$this->request->params['action'] = $action;

			uasort($this->model->fields, [$this, 'sortFields']);
			$controller->set('model', $this->model->alias());
			$controller->set('table', $this->model);
			$controller->set('action', $this->currentAction);
			$controller->set('_fields', $this->model->fields);
			$controller->set('_triggerFrom', $this->triggerFrom);
			if ($this->triggerFrom == 'Model') {
				$controller->set('_alias', $this->model->alias);
			}
		}
	}

	public function renderFields() {
		foreach ($this->model->fields as $key => $attr) {
			if ($key == $this->orderField) {
				$this->model->fields[$this->orderField]['visible']['view'] = false;
			}
			if (array_key_exists('options', $attr) && in_array($attr['type'], ['string', 'integer'])) {
				$this->model->fields[$key]['type'] = 'select';
			}
			// make field sortable by default if it is a string data-type
			if ($attr['type'] == 'string' && !array_key_exists('sort', $attr) && $this->model->hasField($key)) {
				$this->model->fields[$key]['sort'] = true;
			} else if ($attr['type'] == 'select' && !array_key_exists('options', $attr)) {
				if ($this->isForeignKey($key)) {
					// $associatedObjectName = Inflector::pluralize(str_replace('_id', '', $key));
					// $associatedObject = $this->model->{Inflector::camelize($associatedObjectName)};
					$associatedObject = $this->getAssociatedBelongsToModel($key);
					
					$query = $associatedObject->find('list');
					$event = new Event('ControllerAction.Model.onPopulateSelectOptions', $this, compact('query'));
					$event = $associatedObject->eventManager()->dispatch($event);
					if ($event->isStopped()) { return $event->result; }
					if (!empty($event->result)) {
						$query = $event->result;
					}

					if (is_object($query)) {
						$this->model->fields[$key]['options'] = $query->toArray();
					} else {
						$this->model->fields[$key]['options'] = $query;
					}
				}
			}
			if (array_key_exists('onChangeReload', $attr)) {
				if (!array_key_exists('attr', $attr)) {
					$this->model->fields[$key]['attr'] = [];
				}
				$onChange = '';
				if (is_bool($attr['onChangeReload']) && $attr['onChangeReload'] == true) {
					$onChange = "$('#reload').click()";
				} else {
					$onChange = "$('#reload').val('" . $attr['onChangeReload'] . "').click()";
				}
				$this->model->fields[$key]['attr']['onchange'] = $onChange;
			}
		}
	}

	public function model($model=null, $actions=[]) {
		if (is_null($model)) {
			return $this->model;
		} else {
			if (!empty($actions)) {
				$this->defaultActions = $actions;
			}
			$this->plugin = $this->getPlugin($model);
			$this->model = $this->controller->loadModel($model);
			$this->model->alias = $this->model->alias();
			$this->getFields($this->model);
		}
	}

	public function vars() {
		return $this->controller->viewVars;
	}

	public function getVar($key) {
		$value = null;
		if (isset($this->controller->viewVars[$key])) {
			$value = $this->controller->viewVars[$key];
		}
		return $value;
	}

	public function buildDefaultValidation() {
		$action = $this->currentAction;
		if ($action != 'index' && $action != 'view') {
			$validator = $this->model->validator();
			foreach ($this->model->fields as $key => $attr) {
				if ($validator->hasField($key)) {
					$set = $validator->field($key);

					if (!$set->isEmptyAllowed()) {
						$set->add('notBlank', ['rule' => 'notBlank']);
					}
					if (!$set->isPresenceRequired()) {
						if ($this->isForeignKey($key)) {
							$validator->requirePresence($key);
						}
					}
				} else { // field not presence in validator
					if ($attr['null'] === false && $key !== 'id' && !in_array($key, $this->ignoreFields)) {
						$validator->add($key, 'notBlank', ['rule' => 'notBlank']);
						if ($this->isForeignKey($key)) {
							$validator->requirePresence($key);
						}
					}
				}
			}
			// pr('buildDefaultValidation');
			// pr($validator);
		}
	}

	public function isForeignKey($field) {
		$model = $this->model;
		foreach ($model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					return true;
				}
			}
		}
		return false;
	}

	public function getAssociatedBelongsToModel($field) {
		$relatedModel = null;

		foreach ($this->model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					$relatedModel = $assoc;
					break;
				}
			}
		}
		return $relatedModel;
	}

	public function getAssociatedEntityArrayKey($field) {
		$associationKey = $this->getAssociatedBelongsToModel($field);
		$associatedEntityArrayKey = null;
		if (is_object($associationKey)) { 
			$associatedEntityArrayKey = Inflector::underscore(Inflector::singularize($associationKey->alias()));
		} else {
			die($field . '\'s association not found in ' . $this->model->alias());
		}
		return $associatedEntityArrayKey;
	}

	private function initButtons() {
		$controller = $this->controller;

		$named = $this->request->query;
		$pass = $this->request->params['pass'];
		if ($this->triggerFrom == 'Model') {
			unset($pass[0]);
		}
		$defaultUrl = ['plugin' => $this->request->params['plugin'], 'controller' => $controller->name];

		$buttons = [];

		foreach ($this->defaultActions as $action) {
			$actionUrl = $defaultUrl;
			$actionUrl['action'] = $action;

			if ($this->triggerFrom == 'Model') {
				$actionUrl['action'] = $this->model->alias;
				$actionUrl[] = $action;
			}

			if ($action != 'index') {
				$actionUrl = array_merge($actionUrl, $pass);
			}
			$actionUrl = array_merge($actionUrl, $named);
			if ($action != 'remove') {
				$buttons[$action] = array('url' => $actionUrl);
			} else {
				$buttons['delete'] = array('url' => $actionUrl);
			}
		}

		$backAction = 'index';
		if ($this->currentAction == 'edit' || $this->currentAction == 'remove') {
			$backAction = 'view';
		}

		$backUrl = $defaultUrl;
		$backUrl['action'] = $backAction;
		if ($this->triggerFrom == 'Model') {
			$backUrl['action'] = $this->model->alias;
			$backUrl[] = $backAction;
		}
		if ($backAction != 'index') {
			$backUrl = array_merge($backUrl, $pass);
		}
		$backUrl = array_merge($backUrl, $named);
		$buttons['back'] = array('url' => $backUrl);
		if (in_array('remove', $this->defaultActions)) {
			$buttons['delete']['removeStraightAway'] = $this->removeStraightAway;
		}

		// logic for Reorder buttons
		$schema = $this->getSchema($this->model);
		if (!is_null($this->model) && array_key_exists($this->orderField, $schema)) {
			$reorderUrl = $defaultUrl;
			$reorderUrl['action'] = 'reorder';
			$reorderUrl = array_merge($reorderUrl, $named, $pass);
			$buttons['reorder'] = array('url' => $reorderUrl);
		} else {
			unset($buttons['reorder']);
		}
		
		$this->buttons = $buttons;
		$controller->set('_buttons', $buttons);
		foreach ($this->indexActions as $action => $attr) {
			if (array_key_exists($action, $buttons)) {
				$this->indexActions[$action] = array_merge($this->indexActions[$action], $buttons[$action]);
			} else {
				unset($this->indexActions[$action]);
			}
		}
		$event = new Event('ControllerAction.Model.index.onInitializeButtons', $this, ['actions' => $this->indexActions]);
		$event = $this->model->eventManager()->dispatch($event);
		if ($event->isStopped()) { return $event->result; }
		if (!is_null($event->result)) {
			$this->indexActions = $event->result;
		}
		$controller->set('_indexActions', $this->indexActions);
	}

	private function initComponentsForModel() {
		$this->model->controller = $this->controller;
		$this->model->request = $this->request;
		$this->model->Session = $this->request->session();
		$this->model->action = $this->currentAction;

		// Copy all component objects from Controller to Model
		$components = $this->controller->components()->loaded();
		foreach ($components as $component) {
			$this->model->$component = $this->controller->$component;
		}
	}

	public function processAction() {
		$result = null;
		if ($this->autoProcess) {
			if ($this->triggerFrom == 'Controller') {
				if (in_array($this->currentAction, $this->defaultActions)) {
					$result = call_user_func_array([$this, $this->currentAction], $this->paramsPass);
				}
			} else if ($this->triggerFrom == 'Model') {
				if (method_exists($this->model, $this->currentAction)) {
					$result = call_user_func_array([$this->model, $this->currentAction], $this->paramsPass);
				} else {
					if (in_array($this->currentAction, $this->defaultActions)) {
						$result = call_user_func_array([$this, $this->currentAction], $this->paramsPass);
					}
				}
			}
		}
		if (!$result instanceof Response) {
			$this->render();
		}
		return $result;
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
			$this->controller->render($this->templatePath . $view);
		}
	}

	public function getModalOptions($type) {
		$modal = array();

		if ($type == 'remove' && in_array($type, $this->defaultActions)) {
			$modal['id'] = 'delete-modal';
			$modal['title'] = $this->model->alias();
			$modal['content'] = __('Are you sure you want to delete this record.');
			$action = $this->triggerFrom == 'Controller' ? $this->buttons['delete']['url']['action'] : $this->buttons['delete']['url']['action'].'/'.$this->buttons['delete']['url'][0];
			$modal['formOptions'] = ['type' => 'delete', 'action' => $action];
			$modal['fields'] = [
				'id' => array('type' => 'hidden', 'id' => 'recordId')
			];
			$modal['buttons'] = [
				'<button type="submit" class="btn btn-default">' . __('Delete') . '</button>'
			];
		}
		return $modal;
	}

	public function search($model, $order = []) {
		$alias = $model->alias();
		$controller = $this->controller;
		$request = $this->request;
		$limit = $this->Session->check($alias.'.search.limit') ? $this->Session->read($alias.'.search.limit') : key($this->pageOptions);
		$search = $this->Session->check($alias.'.search.key') ? $this->Session->read($alias.'.search.key') : '';
		$schema = $this->getSchema($model);

		if ($request->is(array('post', 'put'))) {
			if (isset($request->data['Search'])) {
				if (array_key_exists('searchField', $request->data['Search'])) {
					$search = trim($request->data['Search']['searchField']);
				}

				if (array_key_exists('limit', $request->data['Search'])) {
					$limit = $request->data['Search']['limit'];
					$this->Session->write($alias.'.search.limit', $limit);
				}
			}
		}
		$conditions = isset($options['conditions']) ? $options['conditions'] : [];

		$contain = [];
		foreach ($model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
				$contain[] = $assoc->name();
			}
		}

		// all string fields are searchable by default
		$OR = isset($conditions['OR']) ? $conditions['OR'] : [];
		foreach($schema as $name => $obj) {
			if ($obj['type'] == 'string' && $name != 'password') {
				$OR["$alias.$name LIKE"] = '%' . $search . '%';
			}
		}
		if (!empty($OR)) {
			$conditions['OR'] = $OR;
		}

		if (empty($order) && array_key_exists($this->orderField, $schema)) {
			$order = [$this->model->aliasField($this->orderField) => 'asc'];
		}

		$paginateOptions = ['limit' => $this->pageOptions[$limit], 'order' => $order, 'conditions' => $conditions];
		if (!empty($contain)) {
			$paginateOptions['contain'] = $contain;
		}

		$this->Session->write($alias.'.search.key', $search);
		$this->request->data['Search']['searchField'] = $search;
		$this->request->data['Search']['limit'] = $limit;
		$controller->set('search', $search);
		$controller->set('pageOptions', $this->pageOptions);

		$event = new Event('ControllerAction.Controller.beforePaginate', $this, ['model' => $model, 'options' => $paginateOptions]);
		$event = $this->controller->eventManager()->dispatch($event);
		if ($event->isStopped()) { return $event->result; }
		if (!empty($event->result)) {
			$paginateOptions = $event->result;
		}
		$event = new Event('ControllerAction.Model.index.beforePaginate', $this, ['request' => $this->request, 'options' => $paginateOptions]);
		$event = $this->model->eventManager()->dispatch($event);
		if ($event->isStopped()) { return $event->result; }
		if (!empty($event->result)) {
			$paginateOptions = $event->result;
		}

		$data = $this->Paginator->paginate($model, $paginateOptions);

		$event = new Event('ControllerAction.Model.index.afterPaginate', $this, ['data' => $data]);
		$event = $this->model->eventManager()->dispatch($event);
		if ($event->isStopped()) { return $event->result; }
		if (!empty($event->result)) {
			$data = $event->result;
		}
		
		return $data;
	}

	public function index() {
		$model = $this->model;

		$event = new Event('ControllerAction.Model.index.beforeAction', $this);
		$event = $model->eventManager()->dispatch($event);
		if ($event->isStopped()) { return $event->result; }

		try {
			$data = $this->search($model);
		} catch (NotFoundException $e) {
			$this->log($e->getMessage(), 'debug');
			$action = $this->buttons['index']['url'];
			if (array_key_exists('page', $action)) {
				unset($action['page']);
			}
			return $this->controller->redirect($action);
		}
		$modal = $this->getModalOptions('remove');

		$indexElements = array(
			array('name' => 'ControllerAction.index', 'data' => array(), 'options' => array())
		);

		if ($data->count() == 0) {
			$this->Alert->info('general.noData');
		} else {
			$indexElements[] = array('name' => 'ControllerAction.pagination', 'data' => array(), 'options' => array());
		}

		$event = new Event('ControllerAction.Model.index.afterAction', $this, ['data' => $data]);
		$event = $model->eventManager()->dispatch($event);
		if ($event->isStopped()) { return $event->result; }
		if (!is_null($event->result)) {
			$data = $event->result;
		}

		$this->controller->set('data', $data);
		$this->controller->set('modal', $modal);
		$this->controller->set('indexElements', $indexElements);
	}

	public function view($id=0) {
		$model = $this->model;
		$primaryKey = $model->primaryKey();
		$idKey = $model->aliasField($primaryKey);

		$event = new Event('ControllerAction.Model.view.beforeAction', $this);
		$event = $model->eventManager()->dispatch($event);
		if ($event->isStopped()) { return $event->result; }

		$contain = [];
		foreach ($model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
				$contain[] = $assoc->name();
			}
		}

		if (empty($id)) {
			if ($this->Session->check($idKey)) {
				$id = $this->Session->read($idKey);
			}
		}
		
		if ($model->exists([$idKey => $id])) {
			$query = $model->findById($id);

			$event = new Event('ControllerAction.Model.viewEdit.beforeQuery', $this, compact('query', 'contain'));
			$event = $model->eventManager()->dispatch($event);
			if ($event->isStopped()) { return $event->result; }
			if (!empty($event->result)) {
				list($query, $contain) = array_values($event->result);
			}
			$event = new Event('ControllerAction.Model.view.beforeQuery', $this, compact('query', 'contain'));
			$event = $this->model->eventManager()->dispatch($event);
			if ($event->isStopped()) { return $event->result; }
			if (!empty($event->result)) {
				list($query, $contain) = array_values($event->result);
			}

			$data = $query->contain($contain)->first();

			if (empty($data)) {
				$this->Alert->warning('general.notExists');
				$action = $this->buttons['index']['url'];
				return $this->controller->redirect($action);
			}

			$event = new Event('ControllerAction.Model.view.afterAction', $this, ['entity' => $data]);
			$event = $model->eventManager()->dispatch($event);
			if ($event->isStopped()) { return $event->result; }
			if (!empty($event->result)) {
				$data = $event->result;
			}

			$this->Session->write($idKey, $id);
			$modal = $this->getModalOptions('remove');
			$this->controller->set('modal', $modal);
			$this->controller->set(compact('data'));
		} else {
			$this->Alert->warning('general.notExists');
			$action = $this->buttons['index']['url'];
			return $this->controller->redirect($action);
		}
	}

	public function add() {
		$model = $this->model;
		$request = $this->request;
		$event = $this->dispatchEvent($model, 'ControllerAction.Model.addEdit.beforeAction');
		if ($event->isStopped()) { return $event->result; }
		$event = $this->dispatchEvent($model, 'ControllerAction.Model.add.beforeAction');
		if ($event->isStopped()) { return $event->result; }
		
		$data = $model->newEntity();

		if ($request->is(['get'])) {
			$event = $this->dispatchEvent($model, 'ControllerAction.Model.add.onInitialize', null, ['entity' => $data]);
			if ($event->isStopped()) { return $event->result; }
			if (!empty($event->result)) {
				$data = $event->result;
			}
		} else if ($request->is(['post', 'put'])) {
			$submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
			$patchOptions = [];
			$params = ['entity' => $data, 'data' => $request->data, 'options' => $patchOptions];

			if ($submit == 'save') {
				// Event: addEditBeforePatch
				$event = $this->dispatchEvent($model, 'ControllerAction.Model.addEdit.beforePatch', null, $params);
				if ($event->isStopped()) { return $event->result; }
				if (!empty($event->result)) {
					$params = array_merge($params, $event->result);
				}
				// End Event
				
				// Event: addBeforePatch
				$event = $this->dispatchEvent($model, 'ControllerAction.Model.add.beforePatch', null, $params);
				if ($event->isStopped()) { return $event->result; }
				if (!empty($event->result)) {
					$params = array_merge($params, $event->result);
				}
				
				// End Event
				list($data, $this->request->data, $patchOptions) = array_values($params);
				$data = $model->patchEntity($data, $request->data, $patchOptions);

				$event = $this->dispatchEvent($model, 'ControllerAction.Model.add.afterPatch', null, $params);
				if ($event->isStopped()) { return $event->result; }
				if (!empty($event->result)) {
					$params = array_merge($params, $event->result);
				}

				if ($model->save($data)) {
					$this->Alert->success('general.add.success');
					$action = $this->buttons['index']['url'];
					
					$event = $this->dispatchEvent($model, 'ControllerAction.Model.add.afterSaveRedirect', null, ['action' => $action]);
					if ($event->isStopped()) { return $event->result; }
					if (!empty($event->result)) {
						$action = $event->result;
					}
					
					return $this->controller->redirect($action);
				} else {
					$this->log($data->errors(), 'debug');
					$this->Alert->error('general.add.failed');
				}
			} else {
				$params['options']['validate'] = false;
				$methodKey = 'on' . ucfirst($submit);

				// Event: addEditOnReload
				$eventKey = 'ControllerAction.Model.addEdit.' . $methodKey;
				$method = 'addEdit' . ucfirst($methodKey);

				$event = $this->dispatchEvent($model, $eventKey, $method, $params);
				if ($event->isStopped()) { return $event->result; }
				if (!empty($event->result)) {
					$params = array_merge($params, $event->result);
				}
				// End Event

				// Event: addOnReload
				$eventKey = 'ControllerAction.Model.add.' . $methodKey;
				$method = 'add' . ucfirst($methodKey);

				$event = $this->dispatchEvent($model, $eventKey, $method, $params);
				if ($event->isStopped()) { return $event->result; }
				if (!empty($event->result)) {
					$params = array_merge($params, $event->result);
				}
				// End Event
				
				list($data, $this->request->data, $patchOptions) = array_values($params);
				$data = $model->patchEntity($data, $request->data, $patchOptions);
			}
		}

		// Event: addEditAfterAction
		$event = $this->dispatchEvent($model, 'ControllerAction.Model.addEdit.afterAction', null, ['entity' => $data]);
		if ($event->isStopped()) { return $event->result; }
		if (is_object($event->result)) {
			$data = $event->result;
		}
		// End Event

		// Event: addAfterAction
		$event = $this->dispatchEvent($model, 'ControllerAction.Model.add.afterAction', null, ['entity' => $data]);
		if ($event->isStopped()) { return $event->result; }
		if (is_object($event->result)) {
			$data = $event->result;
		}
		// End Event
		$this->controller->set('data', $data);
	}

	public function edit($id=0) {
		$model = $this->model;
		$primaryKey = $model->primaryKey();
		$idKey = $model->aliasField($primaryKey);
		$contain = [];

		$event = $model->eventManager()->dispatch(new Event('ControllerAction.Model.addEdit.beforeAction', $this));
		if ($event->isStopped()) { return $event->result; }
		$event = $model->eventManager()->dispatch(new Event('ControllerAction.Model.edit.beforeAction', $this));
		if ($event->isStopped()) { return $event->result; }

		if ($model->exists([$idKey => $id])) {
			$query = $model->findById($id);
			$event = new Event('ControllerAction.Model.viewEdit.beforeQuery', $this, compact('query', 'contain'));
			$event = $model->eventManager()->dispatch($event);
			if ($event->isStopped()) { return $event->result; }
			if (!empty($event->result)) {
				list($query, $contain) = array_values($event->result);
			}
			$event = new Event('ControllerAction.Model.edit.beforeQuery', $this, compact('query', 'contain'));
			$event = $model->eventManager()->dispatch($event);
			if ($event->isStopped()) { return $event->result; }
			if (!empty($event->result)) {
				list($query, $contain) = array_values($event->result);
			}
			$data = $query->contain($contain)->first();

			if (empty($data)) {
				$this->Alert->warning('general.notExists');
				$action = $this->buttons['index']['url'];
				return $this->controller->redirect($action);
			}
			
			if ($this->request->is(['get'])) {
				$event = new Event('ControllerAction.Model.edit.onInitialize', $this, ['entity' => $data]);
				$event = $model->eventManager()->dispatch($event);
				if ($event->isStopped()) { return $event->result; }
				if (!empty($event->result)) {
					$data = $event->result;
				}
			} else if ($this->request->is(['post', 'put'])) {
				$submit = isset($this->request->data['submit']) ? $this->request->data['submit'] : 'save';
				$patchOptions = [];

				if ($submit == 'save') {
					$event = new Event('ControllerAction.Model.addEdit.beforePatch', $this, ['entity' => $data, 'data' => $this->request->data, 'options' => $patchOptions]);
					$event = $model->eventManager()->dispatch($event);
					if ($event->isStopped()) { return $event->result; }
					if (!empty($event->result)) {
						list($data, $this->request->data, $patchOptions) = array_values($event->result);
					}
					$event = new Event('ControllerAction.Model.edit.beforePatch', $this, ['entity' => $data, 'data' => $this->request->data, 'options' => $patchOptions]);
					$event = $model->eventManager()->dispatch($event);
					if ($event->isStopped()) { return $event->result; }
					if (!empty($event->result)) {
						list($data, $this->request->data, $patchOptions) = array_values($event->result);
					}
					$data = $model->patchEntity($data, $this->request->data, $patchOptions);
					if ($model->save($data)) {
						// event: onSaveSuccess
						$this->Alert->success('general.edit.success');
						$action = $this->buttons['view']['url'];

						$event = $this->dispatchEvent($model, 'ControllerAction.Model.edit.afterSaveRedirect', null, ['action' => $action]);
						if ($event->isStopped()) { return $event->result; }
						if (!empty($event->result)) {
							$action = $event->result;
						}
						
						return $this->controller->redirect($action);
					} else {
						// event: onSaveFailed
						$this->log($data->errors(), 'debug');
						$this->Alert->error('general.edit.failed');
					}
				} else {
					$patchOptions['validate'] = false;
					$methodKey = 'on' . ucfirst($submit);
					//addEditOnReload
					$eventKey = 'ControllerAction.Model.addEdit.' . $methodKey;
					$method = 'addEdit' . ucfirst($methodKey);
					$event = new Event($eventKey, $this, ['entity' => $data, 'data' => $this->request->data, 'options' => $patchOptions]);
					if (method_exists($model, $method) || $model->behaviors()->hasMethod($method)) {
						$model->eventManager()->on($eventKey, [], [$model, $method]);
					}
					$event = $model->eventManager()->dispatch($event);
					if ($event->isStopped()) { return $event->result; }
					if (!empty($event->result)) {
						list($data, $this->request->data, $patchOptions) = array_values($event->result);
					}
					//editOnReload
					$eventKey = 'ControllerAction.Model.edit.' . $methodKey;
					$method = 'edit' . ucfirst($methodKey);
					$event = new Event($eventKey, $this, ['entity' => $data, 'data' => $this->request->data, 'options' => $patchOptions]);
					if (method_exists($model, $method) || $model->behaviors()->hasMethod($method)) {
						$model->eventManager()->on($eventKey, [], [$model, $method]);
					}
					$event = $model->eventManager()->dispatch($event);
					if ($event->isStopped()) { return $event->result; }
					if (!empty($event->result)) {
						list($data, $this->request->data, $patchOptions) = array_values($event->result);
					}
					$data = $model->patchEntity($data, $this->request->data, $patchOptions);
				}
			}
			$event = new Event('ControllerAction.Model.addEdit.afterAction', $this, ['entity' => $data]);
			$event = $model->eventManager()->dispatch($event);
			if ($event->isStopped()) { return $event->result; }
			if (is_object($event->result)) {
				$data = $event->result;
			}
			$event = new Event('ControllerAction.Model.edit.afterAction', $this, ['entity' => $data]);
			$event = $model->eventManager()->dispatch($event);
			if ($event->isStopped()) { return $event->result; }
			if (is_object($event->result)) {
				$data = $event->result;
			}
			$this->controller->set('data', $data);
		} else {
			$this->Alert->warning('general.notExists');
			$action = $this->buttons['index']['url'];
			return $this->controller->redirect($action);
		}
	}

	public function remove() {
		$this->autoRender = false;
		$request = $this->request;
		$model = $this->model;
		$primaryKey = $model->primaryKey();
		
		if ($request->is('delete') && !empty($request->data[$primaryKey])) {
			$id = $request->data[$primaryKey];
			$data = $model->get($id);
			if ($this->removeStraightAway) {
				if ($model->delete($data)) {
					$this->Alert->success('general.delete.success');
				} else {
					$this->Alert->error('general.delete.failed');
				}
				$action = $this->buttons['index']['url'];
				return $this->controller->redirect($action);
			} else {
				//return $this->removeAndTransfer(array('selectedValue' => $id));
			}
		}
	}

	public function download($id) {
		$fileUpload = $this->model->behaviors()->get('FileUpload');
		$name = '';
		if (!empty($fileUpload)) {
			$name = $fileUpload->config('name');
			$content = $fileUpload->config('content');
		}

		$data = $this->model->get($id);
		$fileName = $data->$name;
		$pathInfo = pathinfo($fileName);

		$file = $fileUpload->getActualFile($data->$content);
		$fileType = $fileUpload->getFileType($pathInfo['extension']);
		if (!$fileType) {
			$fileType = 'image/jpg';
		}
		// echo '<img src="data:image/jpg;base64,' .   base64_encode($file)  . '" />';

		header("Pragma: public", true);
		header("Expires: 0"); // set expiration time
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: " . $fileType);
		header('Content-Disposition: attachment; filename="' . $fileName . '"');

		echo $file;
		exit();
	}

	/*
	private function removeAndTransfer($options = array()) {

		// 'selectedOption' => false, 'selectedValue' => 0
		$selectedOption = isset($options['selectedOption']) ? $options['selectedOption'] : false;

		$selectedValue = isset($options['selectedValue']) ? $options['selectedValue'] : 0;
		$model = $this->model;
		$modelName = $model->alias();

		if ($selectedValue == 0) {
			$this->Alert->warning('general.notExists');
			$action = $this->controller->viewVars['_buttons']['index']['url'];
			return $this->controller->redirect($action);
		}

		$allowDelete = (isset($model->allowDelete))? $model->allowDelete: false;
		if (!$allowDelete) {
			$this->Alert->error('general.delete.failed');
			$action = $this->controller->viewVars['_buttons']['view']['url'];
			return $this->controller->redirect($action);
		}

		$allFieldOptionValues = $model->find('list', array('conditions'=>array($model->getListConditions)));
		if (array_key_exists($selectedValue, $allFieldOptionValues)) {
			// unset only if field option exists in list
			unset($allFieldOptionValues[$selectedValue]);
		} else {
			$this->Alert->warning('general.notExists');
			$action = $this->controller->viewVars['_buttons']['index']['url'];
			return $this->controller->redirect($action);
		}

		$model->recursive = -1;
		$currentFieldValue = $model->findById($selectedValue);

		// if no legal records to migrate to ... they are not allowed to delete
		if (empty($allFieldOptionValues)) {
			$this->Alert->warning('general.delete.cannotDeleteOnlyRecord');
			$action = $this->controller->viewVars['_buttons']['view']['url'];
			return $this->controller->redirect($action);
		}

		$modifyForeignKey = array();
		$hasManyArray = $model->hasMany;
		//pr($model->getAllHasManyTables());die;
		foreach ($hasManyArray as $key => $value) {
			$CurrModelClass = ClassRegistry::init($value['className']);
			$foreignKeyId = isset($value['foreignKey']) ? $value['foreignKey'] : Inflector::underscore($modelName)."_id";
			$modifyForeignKey[$key] = $CurrModelClass->find('count',
				array(
					'recursive' => -1,
					'conditions' => array(
						$CurrModelClass->alias() . '.' .$foreignKeyId => $selectedValue
					)
				)
			);
		}

		$children = false;
		if (isset($currentFieldValue[$modelName]['parent_id'])) {
			$children = $model->find('all', array('conditions'=>array('parent_id'=>$currentFieldValue[$modelName]['id'])));
		}

		if ($this->request->is(array('post', 'put'))) {
			$convertValue = $this->request->data[$modelName]['convert_to'];
			foreach ($modifyForeignKey as $key => $value) {
				$CurrModelClass = ClassRegistry::init($key);
				$foreignKeyId = isset($hasManyArray[$key]['foreignKey']) ? $hasManyArray[$key]['foreignKey'] : Inflector::underscore($modelName)."_id";
				$CurrModelClass->updateAll(
					array($key.'.'.$foreignKeyId => $convertValue),
					array($key.'.'.$foreignKeyId => $selectedValue)
				);
			}
			if (isset($currentFieldValue[$modelName]['parent_id']) && count($children)>0) {
				foreach ($children as $c) {
					$model->id = $c[$modelName]['id'];
					$c[$modelName]['parent_id'] = $convertValue;
					unset($c[$modelName]['lft']);
					unset($c[$modelName]['rght']);
					$model->data = $c;
					$model->saveAll();
				}
			}
			$model->id = $selectedValue;
			if ($model->delete()) {
				$this->Alert->success('general.delete.successAfterTransfer');
				$action = $this->controller->viewVars['_buttons']['index']['url'];
				if (isset($action[1])) {
					unset($action[1]);
				}
				return $this->controller->redirect($action);
			}
		}
				
		$this->controller->set('allOtherFieldOptionValues', $allFieldOptionValues);
		$this->controller->set(compact('header', 'currentFieldValue', 'modifyForeignKey', 'selectedOption', 'selectedValue', 'allowDelete', 'model', 'children'));
	}
	*/

	public function reorder($id=0) {
		$model = $this->model;

		if ($id != 0) {
			$named = $this->controller->params['named'];
			$move = $named['move'];

			$actionUrl = array('action' => 'index');
			if ($this->triggerFrom == 'Model') {
				$actionUrl['action'] = $this->model->alias();
				$actionUrl[] = $action;
			}
			unset($named['move']);
			$actionUrl = array_merge($actionUrl, $named);
			
			$conditions = array();
			//$conditions = isset($this->controller->viewVars['conditions']) ? $this->controller->viewVars['conditions'] : array();
			$this->fixOrder($conditions);
			
			$idField = $model->alias().'.'.$model->primaryKey();
			$orderField = $model->alias() . '.' . $this->orderField;
			$order = $model->field($this->orderField, array($model->primaryKey() => $id));
			$idConditions = array_merge(array($idField => $id), $conditions);
			$updateConditions = array_merge(array($idField . ' <>' => $id), $conditions);

			if($move === 'up') {
				$model->updateAll(array($orderField => $order-1), $idConditions);
				$updateConditions[$orderField] = $order-1;
				$model->updateAll(array($orderField => $order), $updateConditions);
			} else if($move === 'down') {
				$model->updateAll(array($orderField => $order+1), $idConditions);
				$updateConditions[$orderField] = $order+1;
				$model->updateAll(array($orderField => $order), $updateConditions);
			} else if($move === 'first') {
				$model->updateAll(array($orderField => 1), $idConditions);
				$updateConditions[$orderField . ' <'] = $order;
				$model->updateAll(array($orderField => '`'.$orderField . '` + 1'), $updateConditions);
			} else if($move === 'last') {
				$count = $model->find('count', array('conditions' => $conditions));
				$model->updateAll(array($orderField => $count), $idConditions);
				$updateConditions[$orderField . ' >'] = $order;
				$model->updateAll(array($orderField => '`'.$orderField . '` - 1'), $updateConditions);
			}
		}

		return $this->controller->redirect($actionUrl);
	}

	public function fixOrder($conditions) {
		$model = $this->model;
		$count = $model->find('count', array('conditions' => $conditions));
		if($count > 0) {
			$list = $model->find('list', array(
				'conditions' => $conditions,
				'order' => array(
					$model->alias().'.'.$this->orderField,
					$model->alias().'.'.$model->primaryKey()
				)
			));
			$order = 1;
			foreach($list as $id => $name) {
				$model->id = $id;
				$model->saveField($this->orderField, $order++);
			}
		}
	}

	public function getPlugin($model) {
		$array = $this->getModel($model);
		return $array['plugin'];
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

	public function getSchema($model) {
		$schema = $model->schema();
		$columns = $schema->columns();
		$fields = [];
		foreach ($columns as $col) {
			$fields[$col] = $schema->column($col);
		}
		return $fields;
	}

	public function addField($field, $attr=[]) {
		$this->field($field, $attr);
	}

	public function field($field, $attr=[]) {
		$model = $this->model;
		$className = $model->alias();

		if (!isset($model->fieldOrder)) {
			$model->fieldOrder = 0;
		}
		$model->fieldOrder = $model->fieldOrder + 1;
		
		if (!empty($this->plugin)) {
			$className = $this->plugin . '.' . $className;
		}
		
		$_attr = [
			'type' => 'string',
			'null' => true,
			'autoIncrement' => false,
			'order' => $model->fieldOrder,
			'visible' => true,
			'field' => $field,
			'model' => $model->alias(),
			'className' => $className
		];

		if (array_key_exists($field, $model->fields)) {
			$_attr = array_merge($_attr, $model->fields[$field]);
		}

		$attr = array_merge($_attr, $attr);
		$model->fields[$field] = $attr;

		$method = 'onUpdateField' . Inflector::camelize($field);
		$eventKey = 'ControllerAction.Model.' . $method;
		$params = ['attr' => $attr, 'action' => $this->currentAction, 'request' => $this->request];
		$event = $this->dispatchEvent($this->model, $eventKey, $method, $params);
		if (is_array($event->result)) {
			$model->fields[$field] = $event->result;
		}

		return $model->fields[$field];
	}
	
	public function getFields($model) {
		$ignoreFields = $this->ignoreFields;
		$className = $model->alias();
		if (!empty($this->plugin)) {
			$className = $this->plugin . '.' . $className;
		}
		$fields = $this->getSchema($model);
		$visibility = ['view' => true, 'edit' => true, 'index' => true];

		$i = 50;
		foreach($fields as $key => $obj) {
			$fields[$key]['order'] = $i++;
			$fields[$key]['visible'] = $visibility;
			$fields[$key]['field'] = $key;
			$fields[$key]['model'] = $model->alias();
			$fields[$key]['className'] = $className;

			if ($key == 'password') {
				$fields[$key]['visible'] = false;
			}
			/*
			if ($obj['type'] == 'binary') {
				$fields[$key]['visible']['index'] = false;
			}
			*/
		}
		
		$fields[$model->primaryKey()]['type'] = 'hidden';
		foreach ($ignoreFields as $field) {
			if (array_key_exists($field, $fields)) {
				$fields[$field]['visible']['index'] = false;
				$fields[$field]['visible']['view'] = true;
				$fields[$field]['visible']['edit'] = false;
				$fields[$field]['labelKey'] = 'general';
			}
		}
		$model->fields = $fields;
		return $fields;
	}
	
	public function setFieldOrder($field, $order=0) {
		$fields = $this->model->fields;

		if (is_array($field)) {
			foreach ($field as $key) {
				$fields[$key]['order'] = $order++;
			}
		} else {
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
		}
		$this->model->fields = $fields;
	}

	public function setFieldVisible($actions, $fields) {
		foreach ($this->model->fields as $key => $attr) {
			if (in_array($key, $fields)) {
				foreach ($actions as $action) {
					$this->model->fields[$key]['visible'][$action] = true;
				}
			} else {
				$this->model->fields[$key]['visible'] = false;
			}
		}
	}
	
	public static function sortFields($a, $b) {
		if (isset($a['order']) && isset($b['order'])) {
			return $a['order'] >= $b['order'];
		} else {
			return true;
		}
	}

	public function dispatchEvent($subject, $eventKey, $method=null, $params=[]) {
		$eventMap = $subject->implementedEvents();
		$event = new Event($eventKey, $this, $params);

		if (!array_key_exists($eventKey, $eventMap) && !is_null($method)) {
			if (method_exists($subject, $method) || $subject->behaviors()->hasMethod($method)) {
				$subject->eventManager()->on($eventKey, [], [$subject, $method]);
			}
		}
		return $subject->eventManager()->dispatch($event);
	}

	public function endsWith($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}
}