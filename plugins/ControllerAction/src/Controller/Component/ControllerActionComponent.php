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

ControllerActionComponent - Current Version 3.1.12
3.1.12 (Zack) - added new event onGetConvertOptions to add additional condition to the query to generate the convert options for delete and transfer
3.1.11 (Zack) - added logic to reorder() to swap the order of the list that is pass over with the original list
3.1.10 (Thed) - added new event onDeleteTransfer
3.1.9 (Malcolm) - Added 'getTriggerFrom()' get method
3.1.8 (Jeff) - session variable to store the primary key value of object includes the plugin name now
3.1.7 (Jeff) - added properties $view and function renderView() so that custom view can be rendered with all events triggered
3.1.6 (Jeff) - created function url($action) to return url with params
3.1.5 (Jeff) - moved initButtons to afterAction so that query params can be passed to buttons
3.1.4 (Jeff) - removed $controller param from addAfterSave and replaced with $requestData
3.1.3 (Jeff) - added new event deleteBeforeAction
3.1.2 (Jeff) - added deleteStrategy for transferring of records, new event deleteOnInitialize
3.1.1 (Jeff) - modified add(), edit() to allow changing of table
3.1.0 (Jeff) - moved renderFields() to be called after the event (afterAction) is triggered
3.0.9 (Jeff) - fixed getContains to retrieve only id, name and foreignKeys fields
3.0.8 (Jeff) - fixed remove() not throwing errors if delete fails
3.0.7 (Jeff) - edited ControllerAction.Controller.beforePaginate to use $this->model instead of $model
3.0.6 (Malcolm) - $request->data = $requestData->getArrayCopy(); added after addAfterPatch dispatch event
			 - for purpose of modifying request->data after validation (eg. unsetting a field the value can be removed from the input field after validation)
3.0.5 (Jeff) - renamed beforeRender to afterAction, afterAction is called in processAction() now. 
			 - this change is necessary to be compatible with CakePHP v3.1.0
			 - optimized getContains to only fetch id and name instead of all fields which are not being used most of the time
3.0.4 (Jeff) - added sortable types in renderFields() to be able to sort by date/time
3.0.3 (Jeff) - added in search() to implement auto_contain|auto_search|auto_order options to be used in indexBeforePaginate
3.0.2 (Jeff) - removed debug message on event (ControllerAction.Model.onPopulateSelectOptions)
3.0.1 (Jeff) - add debug messages on all events triggered by this component
*/

namespace ControllerAction\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Network\Response;
use Cake\Network\Exception\NotFoundException;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\I18n\I18n;
use Cake\Core\Configure;
use Cake\Log\Log;

use ControllerAction\Model\Traits\ControllerActionV4Trait;

class ControllerActionComponent extends Component {
	use ControllerActionV4Trait; // extended functionality from v4
	
	private $plugin;
	private $controller;
	private $triggerFrom = 'Controller';
	private $currentAction;
	private $ctpFolder;
	private $paramsPass;
	private $config;
	private $defaultActions = ['search', 'index', 'add', 'view', 'edit', 'remove', 'download', 'reorder']; 
	private $deleteStrategy = 'cascade'; // cascade | transfer
	private $view = '';

	public $model = null;
	public $models = [];
	public $buttons = [];
	public $orderField = 'order';
	public $autoRender = true;
	public $autoProcess = true;
	public $ignoreFields = ['modified', 'created'];
	public $templatePath = '/ControllerAction/';
	public $pageOptions = [10, 20, 30, 40, 50];
	public $Session;
	public $debug = false;

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
		$this->config = new ArrayObject([]);

		$this->debug = Configure::read('debug');
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Event $event) {
		$controller = $this->controller;
		
		$action = $this->request->params['action'];
		$this->debug('Startup');
		if (!method_exists($controller, $action)) { // method cannot be found in controller
			if (in_array($action, $this->defaultActions)) { // default actions
				$this->currentAction = $action;
				$this->request->params['action'] = 'ComponentAction';
				$this->initComponentsForModel();
			} else { // check if it's a model action
				$this->debug(__METHOD__, ': Searching Models');
				foreach ($this->models as $name => $attr) {
					if (strtolower($action) === strtolower($name)) { // model class found
						$this->debug(__METHOD__, ': ' . $name . ' found');
						$currentAction = 'index';
						if (!empty($this->paramsPass)) {
							$currentAction = array_shift($this->paramsPass);
						}

						$actions = isset($attr['actions']) ? $attr['actions'] : $this->defaultActions;
						$options = isset($attr['options']) ? $attr['options'] : [];

						$this->model($attr['className'], $actions, $options);
						$this->model->alias = $name;
						$this->currentAction = $currentAction;
						$this->ctpFolder = $this->model->alias();
						$this->request->params['action'] = 'ComponentAction';
						$this->initComponentsForModel();

						$this->debug(__METHOD__, ': Event -> ControllerAction.Controller.onInitialize');
						$event = new Event('ControllerAction.Controller.onInitialize', $this, [$this->model, new ArrayObject([])]);
						$event = $this->controller->eventManager()->dispatch($event);
						if ($event->isStopped()) { return $event->result; }
						
						$this->triggerFrom = 'Model';
						break;
					}
				}
			}
			$this->debug(__METHOD__, ': Event -> ControllerAction.Model.beforeAction');
			$event = new Event('ControllerAction.Model.beforeAction', $this);
			$event = $this->model->eventManager()->dispatch($event);
			if ($event->isStopped()) { return $event->result; }
			$this->buildDefaultValidation();
		}
	}

	public function renderFields() {
		foreach ($this->model->fields as $key => $attr) {
			if ($key == $this->orderField) {
				$this->model->fields[$this->orderField]['visible'] = ['view' => false];
			}
			if (array_key_exists('options', $attr)) {
				if (in_array($attr['type'], ['string', 'integer'])) {
					$this->model->fields[$key]['type'] = 'select';
				}
				if (empty($attr['options']) && empty($attr['attr']['empty'])) {
					if (!array_key_exists('empty', $attr)) {
						$this->model->fields[$key]['attr']['empty'] = $this->Alert->getMessage('general.select.noOptions');
					}
				}
			}

			// make field sortable by default if it is a string data-type
			if (!array_key_exists('type', $attr)) {
				$this->log($key, 'debug');
			}

			$sortableTypes = ['string', 'date', 'time', 'datetime'];
			if (in_array($attr['type'], $sortableTypes) && !array_key_exists('sort', $attr) && $this->model->hasField($key)) {
				$this->model->fields[$key]['sort'] = true;
			} else if ($attr['type'] == 'select' && !array_key_exists('options', $attr)) {
				if ($this->isForeignKey($key)) {
					// $associatedObjectName = Inflector::pluralize(str_replace('_id', '', $key));
					// $associatedObject = $this->model->{Inflector::camelize($associatedObjectName)};
					$associatedObject = $this->getAssociatedBelongsToModel($key);
					
					$query = $associatedObject->find('list');
					
					$event = new Event('ControllerAction.Model.onPopulateSelectOptions', $this, [$query]);
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

				if (!array_key_exists('attr', $this->model->fields[$key])) {
					$this->model->fields[$key]['attr'] = [];
				}
				$onChange = '';
				if (is_bool($attr['onChangeReload']) && $attr['onChangeReload'] == true) {
					$onChange = "$('#reload').click();return false;";
				} else {
					$onChange = "$('#reload').val('" . $attr['onChangeReload'] . "').click();return false;";
				}
				$this->model->fields[$key]['attr']['onchange'] = $onChange;
			}
		}
	}

	public function model($model=null, $actions=[], $options=[]) {
		if (array_key_exists('deleteStrategy', $options)) {
			$this->deleteStrategy = $options['deleteStrategy'];
		}

		if (is_null($model)) {
			return $this->model;
		} else {
			if (!empty($actions)) {
				// removing actions
				// may not be the perfect solution yet
				foreach ($actions as $action) {
					$splitStr = str_split($action);
					if ($splitStr[0] == '!') {
						foreach ($this->defaultActions as $i => $val) {
							if ($val == substr($action, 1, strlen($action))) {
								unset($this->defaultActions[$i]);
								break;
							}
						}
					} else {
						$this->defaultActions = $actions;
						break;
					}
				}
			}
			$this->plugin = $this->getPlugin($model);
			$this->model = $this->controller->loadModel($model);
			$this->model->alias = $this->model->alias();
			$this->getFields($this->model);
		}
	}

	public function action() {
		return $this->currentAction;
	}

	public function removeDefaultActions(array $actions) {
		$defaultActions = $this->defaultActions;
		foreach ($actions as $action) {
			if (array_search($action, $defaultActions)) {
				unset($defaultActions[array_search($action, $defaultActions)]);
			}
		}
		$this->defaultActions = $defaultActions;
	}

	public function addDefaultActions(array $actions) {
		$defaultActions = $this->defaultActions;
		foreach ($actions as $action) {
			if (! array_search($action, $defaultActions)) {
				$defaultActions[] = $action;
			}
		}
		$this->defaultActions = $defaultActions;
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

	public function paramsPass() {
		$params = $this->request->pass;
		if ($this->triggerFrom == 'Model') {
			if ($this->triggerFrom == 'Model') {
				unset($params[0]);
			}
		}
		return $params;
	}

	public function paramsQuery() {
		return $this->request->query;
	}

	public function params() {
		$params = $this->paramsPass();
		return array_merge($params, $this->paramsQuery());
	}

	public function url($action) {
		$controller = $this->controller;
		$url = ['plugin' => $controller->plugin, 'controller' => $controller->name];

		if ($this->triggerFrom == 'Model') {
			$url['action'] = $this->model->alias;
			$url[0] = $action;
		} else {
			$url['action'] = $action;
		}
		$url = array_merge($url, $this->params());
		return $url;
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
					if (array_key_exists('null', $attr)) {
						if ($attr['null'] === false && $key !== 'id' && !in_array($key, $this->ignoreFields)) {
							$validator->add($key, 'notBlank', ['rule' => 'notBlank']);
							if ($this->isForeignKey($key)) {
								$validator->requirePresence($key);
							}
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
		$defaultUrl = ['plugin' => $controller->plugin, 'controller' => $controller->name];

		$buttons = new ArrayObject([]);

		foreach ($this->defaultActions as $action) {
			$actionUrl = $defaultUrl;
			$actionUrl['action'] = $action;

			if ($this->triggerFrom == 'Model') {
				$actionUrl['action'] = $this->model->alias;
				$actionUrl[0] = $action;
			}

			if ($action != 'index') {
				if ($this->currentAction != 'index') {
					$model = $this->model;
					$primaryKey = $model->primaryKey();
					$idKey = $model->aliasField($primaryKey);
					$sessionKey = $model->registryAlias() . '.' . $primaryKey;
					if (empty($pass)) {
						if ($this->Session->check($sessionKey)) {
							$pass = [$this->Session->read($sessionKey)];
						}
					} elseif (isset($pass[0]) && $pass[0]==$action) {
						if ($this->Session->check($sessionKey)) {
							$pass[1] = $this->Session->read($sessionKey);
						}		
					}
				}
				$actionUrl = array_merge($actionUrl, $pass);
			}
			$actionUrl = array_merge($actionUrl, $named);
			$buttons[$action] = array('url' => $actionUrl);
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
		if ($buttons->offsetExists('remove')) {
			$buttons['remove']['strategy'] = $this->deleteStrategy;
		}

		// logic for Reorder buttons
		$schema = $this->getSchema($this->model);
		if (!is_null($this->model) && array_key_exists($this->orderField, $schema)) {
			$reorderUrl = $defaultUrl;
			$reorderUrl['action'] = 'reorder';
			$reorderUrl = array_merge($reorderUrl, $named, $pass);
			$buttons['reorder'] = array('url' => $reorderUrl);
		} else {
			if (array_key_exists('reorder', $buttons)) {
				unset($buttons['reorder']);
			}
		}

		$params = [$buttons, $this->currentAction, $this->triggerFrom == 'Model'];
		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.onInitializeButtons');
		$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.onInitializeButtons', null, $params);
		if ($event->isStopped()) { return $event->result; }

		$this->buttons = $buttons->getArrayCopy();
	}

	private function initComponentsForModel() {
		$this->debug(__METHOD__);
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
				if (method_exists($this->model, $this->currentAction) || $this->model->behaviors()->hasMethod($this->currentAction)) {
					$result = call_user_func_array([$this->model, $this->currentAction], $this->paramsPass);
				} else {
					if (in_array($this->currentAction, $this->defaultActions)) {
						$result = call_user_func_array([$this, $this->currentAction], $this->paramsPass);
					} else {
						return $this->controller->redirect(['action' => $this->model->alias]);
					}
				}
			}
			if ($result instanceof Response) {
				return $result;
			}
		}
		$this->debug('processAction');
		$this->afterAction();
		
		if (!$result instanceof Response) {
			$this->render();
		}
		return $result;
	}

	public function afterAction() {
		$controller = $this->controller;
		if (!is_null($this->model) && !empty($this->model->fields)) {
			$action = $this->triggerFrom == 'Model' ? $this->model->alias : $this->currentAction;

			$this->initButtons();

			$this->config['action'] = $this->currentAction;
			$this->config['table'] = $this->model;
			$this->config['fields'] = $this->model->fields;
			$this->config['buttons'] = $this->buttons;
			if (!array_key_exists('formButtons', $this->config)) {
				$this->config['formButtons'] = true; // need better solution
			}
			
			$this->debug(__METHOD__, ': Event -> ControllerAction.Model.afterAction');
			$event = new Event('ControllerAction.Model.afterAction', $this, [$this->config]);
			$event = $this->model->eventManager()->dispatch($event);
			if ($event->isStopped()) { return $event->result; }

			$this->renderFields();

			$this->request->params['action'] = $action;

			uasort($this->model->fields, [$this, 'sortFields']);
			$this->config['fields'] = $this->model->fields;
			
			$controller->set('ControllerAction', $this->config);

			// deprecated: backward compatible
			$controller->set('action', $this->currentAction);
			$controller->set('model', $this->model->alias());
		}
	}

	public function render() {
		$ext = $this->request->params['_ext'];
		if (in_array($ext, ['json', 'xml'])) {

			if (array_key_exists('paging', $this->request->params)) {
				$this->controller->set('paging', $this->request->params['paging'][$this->model->alias()]);
			}
			$this->controller->set('format', $ext);
			$data = $this->controller->viewVars['data'];
			foreach ($data as $key => $value) {
				foreach ($value->visibleProperties() as $property) {
					if (is_resource($value->$property)) {
						$value->$property = base64_encode("data:image/jpeg;base64,".stream_get_contents($value->$property));						
					}
				}
			}
			$this->controller->set('query', $this->request->query);
			// $this->controller->set('_serialize', ['data', 'paging', 'format', 'query', '_navigations']);
			$this->controller->set('_serialize', ['data', 'paging', 'query']);
		
		} else {

			if (empty($this->plugin)) {
				$path = APP . 'Template' . DS . $this->controller->name . DS;
			} else {
				$path = ROOT . DS . 'plugins' . DS . $this->plugin . DS . 'src' . DS . 'Template' . DS;
			}
			$ctp = $this->ctpFolder . DS . $this->currentAction;

			if (file_exists($path . DS . $ctp . '.ctp')) {
				if ($this->autoRender) {
					$this->autoRender = false;
					$this->controller->render($ctp);
				}
			}

			if ($this->autoRender) {
				if (empty($this->view)) {
					$view = $this->currentAction == 'add' ? 'edit' : $this->currentAction;
					$this->controller->render($this->templatePath . $view);
				} else {
					$this->controller->render($this->view);
				}
			}

		}
	}

	public function renderView($view) {
		$this->view = $view;
	}

	public function getModalOptions($type) {
		$modal = [];

		if ($type == 'remove' && in_array($type, $this->defaultActions)) {
			$modal['title'] = $this->model->alias();
			$modal['content'] = __('Are you sure you want to delete this record.');

			$modal['form'] = [
				'model' => $this->model,
				'formOptions' => ['type' => 'delete', 'url' => $this->url('remove')],
				'fields' => ['id' => ['type' => 'hidden', 'id' => 'recordId']]
			];

			$modal['buttons'] = [
				'<button type="submit" class="btn btn-default">' . __('Delete') . '</button>'
			];
			$modal['cancelButton'] = true;
		}
		return $modal;
	}

	public function getContains($model, $type = 'belongsTo') { // type is not being used atm
		$contain = [];
		foreach ($model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
				$columns = $assoc->schema()->columns();
				if (in_array('name', $columns)) {
					$fields = ['id', 'name'];
					foreach ($columns as $col) {
						if ($this->endsWith($col, '_id')) {
							$fields[] = $col;
						}
					}
					$contain[$assoc->name()] = ['fields' => $fields];
				} else if (in_array($assoc->name(), ['ModifiedUser', 'CreatedUser'])) {
					$contain[$assoc->name()] = ['fields' => ['id', 'first_name', 'last_name']];
				} else {
					$contain[$assoc->name()] = [];
				}
			}
		}
		return $contain;
	}

	public function getSearchKey() {
		return $this->Session->read($this->model->alias().'.search.key');
	}

	public function search($model, $order = []) {
		$alias = $this->model->alias();
		$controller = $this->controller;
		$request = $this->request;
		$limit = $this->Session->check($alias.'.search.limit') ? $this->Session->read($alias.'.search.limit') : key($this->pageOptions);
		$search = $this->Session->check($alias.'.search.key') ? $this->Session->read($alias.'.search.key') : '';
		$schema = $this->getSchema($model);

		if ($request->is(['post', 'put'])) {
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

		$query = $model->find();

		$options = new ArrayObject([
			'limit' => $this->pageOptions[$limit], 
			'auto_contain' => true,
			'auto_search' => true,
			'auto_order' => true
		]);

		$this->Session->write($alias.'.search.key', $search);
		$this->request->data['Search']['searchField'] = $search;
		$this->request->data['Search']['limit'] = $limit;
		
		$this->config['search'] = $search;
		$this->config['pageOptions'] = $this->pageOptions;

		$this->debug(__METHOD__, ': Event -> ControllerAction.Controller.beforePaginate');
		$event = new Event('ControllerAction.Controller.beforePaginate', $this, [$this->model, $query, $options]);
		$event = $this->controller->eventManager()->dispatch($event);
		if ($event->isStopped()) { return $event->result; }

		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.index.beforePaginate');
		$event = new Event('ControllerAction.Model.index.beforePaginate', $this, [$this->request, $query, $options]);
		$event = $this->model->eventManager()->dispatch($event);
		if ($event->isStopped()) { return $event->result; }

		if ($options['auto_contain']) {
			$contain = $this->getContains($model);
			if (!empty($contain)) {
				$query->contain($contain);
			}
		}

		if ($options['auto_search']) {
			$OR = [];
			if (!empty($search)) {
				foreach($schema as $name => $obj) {
					if ($obj['type'] == 'string' && $name != 'password') {
						$OR[$model->aliasField("$name").' LIKE'] = '%' . $search . '%';
					}
				}
			}

			if (!empty($OR)) {
				$query->where(['OR' => $OR]);
			}
		}

		if ($options['auto_order']) {
			if (empty($order) && array_key_exists($this->orderField, $schema)) {
				$query->order([$model->aliasField($this->orderField) => 'asc']);
			}
		}

		unset($options['auto_contain']);
		unset($options['auto_search']);
		unset($options['auto_order']);

		$data = $this->Paginator->paginate($query, $options->getArrayCopy());

		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.index.afterPaginate');
		$event = new Event('ControllerAction.Model.index.afterPaginate', $this, [$data]);
		$event = $this->model->eventManager()->dispatch($event);
		if ($event->isStopped()) { return $event->result; }
		if (!empty($event->result)) {
			$data = $event->result;
		}
		
		return $data;
	}

	public function index() {
		$model = $this->model;
		
		$settings = new ArrayObject(['pagination' => true, 'model' => $model->registryAlias()]);
		$query = $model->find();

		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.index.beforeAction');
		$event = new Event('ControllerAction.Model.index.beforeAction', $this, [$query, $settings]);
		$event = $model->eventManager()->dispatch($event);
		if ($event->isStopped()) { return $event->result; }
		if ($event->result) {
			$query = $event->result;
		}

		$indexElements = [
			['name' => 'OpenEmis.ControllerAction/index', 'data' => [], 'options' => []]
		];

		try {
			if ($settings['pagination']) {
				if ($settings['model'] != $model->registryAlias()) {
					$model = TableRegistry::get($settings['model']);
				}
				$data = $this->search($model);
				$indexElements[] = ['name' => 'OpenEmis.pagination', 'data' => [], 'options' => []];
			} else {
				$data = $query->all();
			}
		} catch (NotFoundException $e) {
			$this->log($e->getMessage(), 'debug');
			$action = $this->url('index');
			if (array_key_exists('page', $action)) {
				unset($action['page']);
			}
			return $this->controller->redirect($action);
		}
		
		if ($data->count() == 0) {
			$this->Alert->info('general.noData');
		}

		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.index.afterAction');
		$event = new Event('ControllerAction.Model.index.afterAction', $this, [$data]);
		$event = $this->model->eventManager()->dispatch($event);
		if (!empty($event->result)) {
			$data = $event->result;
		}
		if ($event->isStopped()) { return $event->result; }

		$modals = ['delete-modal' => $this->getModalOptions('remove')];
		$this->config['form'] = true;
		$this->config['formButtons'] = false;
		$this->controller->set(compact('data', 'modals', 'indexElements'));
	}

	public function view($id=0) {
		$model = $this->model;

		// Event: viewBeforeAction
		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.view.beforeAction');
		$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.view.beforeAction');
		if ($event->isStopped()) { return $event->result; }
		if ($event->result instanceof Table) {
			$model = $event->result;
		}
		// End Event

		$primaryKey = $model->primaryKey();
		$idKey = $model->aliasField($primaryKey);
		$sessionKey = $model->registryAlias() . '.' . $primaryKey;
		$contain = [];

		foreach ($model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
				$contain[] = $assoc->name();
			}
		}

		if (empty($id)) {
			if ($this->Session->check($sessionKey)) {
				$id = $this->Session->read($sessionKey);
			}
		}
		
		if ($model->exists([$idKey => $id])) {
			$query = $model->find()->where([$idKey => $id])->contain($contain);

			// Event: viewEditBeforeQuery
			$this->debug(__METHOD__, ': Event -> ControllerAction.Model.viewEdit.beforeQuery');
			$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.viewEdit.beforeQuery', null, [$query]);
			if ($event->isStopped()) { return $event->result; }
			// End Event

			// Event: viewBeforeQuery
			$this->debug(__METHOD__, ': Event -> ControllerAction.Model.view.beforeQuery');
			$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.view.beforeQuery', null, [$query]);
			if ($event->isStopped()) { return $event->result; }
			// End Event

			$entity = $query->first();

			if (empty($entity)) {
				$this->Alert->warning('general.notExists');
				return $this->controller->redirect($this->url('index'));
			}

			// Event: viewAfterAction
			$this->debug(__METHOD__, ': Event -> ControllerAction.Model.view.afterAction');
			$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.view.afterAction', null, [$entity]);
			if ($event->isStopped()) { return $event->result; }
			// End Event

			$this->Session->write($sessionKey, $id);
			$modals = ['delete-modal' => $this->getModalOptions('remove')];
			$this->controller->set('data', $entity);
			$this->controller->set('modals', $modals);
		} else {
			$this->Alert->warning('general.notExists');
			return $this->controller->redirect($this->url('index'));
		}
		$this->config['form'] = false;
	}

	public function add() {
		$model = $this->model;
		$request = $this->request;

		// Event: addEditBeforeAction
		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.addEdit.beforeAction');
		$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.addEdit.beforeAction');
		if ($event->isStopped()) { return $event->result; }
		if ($event->result instanceof Table) {
			$model = $event->result;
		}
		// End Event

		// Event: addBeforeAction
		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.beforeAction');
		$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.beforeAction');
		if ($event->isStopped()) { return $event->result; }
		if ($event->result instanceof Table) {
			$model = $event->result;
		}
		// End Event

		$entity = $model->newEntity();

	if ($request->is(['get'])) {
			// Event: addOnInitialize
			$this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.onInitialize');
			$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.onInitialize', null, [$entity]);
			if ($event->isStopped()) { return $event->result; }
			// End Event
		} else if ($request->is(['post', 'put'])) {
			$submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
			$patchOptions = new ArrayObject([]);
			$requestData = new ArrayObject($request->data);

			$params = [$entity, $requestData, $patchOptions];

			if ($submit == 'save') {
				// Event: addEditBeforePatch
				$this->debug(__METHOD__, ': Event -> ControllerAction.Model.addEdit.beforePatch');
				$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.addEdit.beforePatch', null, $params);
				if ($event->isStopped()) { return $event->result; }
				// End Event
				
				// Event: addBeforePatch
				$this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.beforePatch');
				$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.beforePatch', null, $params);
				if ($event->isStopped()) { return $event->result; }
				// End Event

				$patchOptionsArray = $patchOptions->getArrayCopy();
				$request->data = $requestData->getArrayCopy();
				$entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);
// pr($entity);die;
				// Event: addAfterPatch
				$this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.afterPatch');
				$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.afterPatch', null, $params);
				if ($event->isStopped()) { return $event->result; }
				// End Event
				$request->data = $requestData->getArrayCopy();

				$process = function ($model, $entity) {
					return $model->save($entity);
				};

				// Event: onBeforeSave
				$this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.beforeSave');
				$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.beforeSave', null, [$entity, $requestData]);
				if ($event->isStopped()) { return $event->result; }
				if (is_callable($event->result)) {
					$process = $event->result;
				}
				// End Event

				if ($process($model, $entity)) {
					$this->Alert->success('general.add.success');
					// Event: addAfterSave
					$this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.afterSave');
					$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.afterSave', null, [$entity, $requestData]);
					if ($event->isStopped()) { return $event->result; }
					// End Event

					return $this->controller->redirect($this->url('index'));
				} else {
					$this->log($entity->errors(), 'debug');
					$this->Alert->error('general.add.failed');
				}
			} else {
				$patchOptions['validate'] = false;
				$methodKey = 'on' . ucfirst($submit);

				// Event: addEditOnReload
				$eventKey = 'ControllerAction.Model.addEdit.' . $methodKey;
				$this->debug(__METHOD__, ': Event -> ' . $eventKey);
				$method = 'addEdit' . ucfirst($methodKey);
				$event = $this->dispatchEvent($this->model, $eventKey, $method, $params);
				if ($event->isStopped()) { return $event->result; }
				// End Event

				// Event: addOnReload
				$eventKey = 'ControllerAction.Model.add.' . $methodKey;
				$this->debug(__METHOD__, ': Event -> ' . $eventKey);
				$method = 'add' . ucfirst($methodKey);
				$event = $this->dispatchEvent($this->model, $eventKey, $method, $params);
				if ($event->isStopped()) { return $event->result; }
				// End Event
				
				$patchOptionsArray = $patchOptions->getArrayCopy();
				$request->data = $requestData->getArrayCopy();
				$entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);
			}
		}

		// Event: addEditAfterAction
		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.addEdit.afterAction');
		$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.addEdit.afterAction', null, [$entity]);
		if ($event->isStopped()) { return $event->result; }
		// End Event

		// Event: addAfterAction
		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.add.afterAction');
		$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.add.afterAction', null, [$entity]);
		if ($event->isStopped()) { return $event->result; }
		// End Event
		$this->config['form'] = true;
		$this->controller->set('data', $entity);
	}

	public function edit($id=0) {
		$model = $this->model;
		$request = $this->request;

		// Event: addEditBeforeAction
		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.addEdit.beforeAction');
		$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.addEdit.beforeAction');
		if ($event->isStopped()) { return $event->result; }
		if ($event->result instanceof Table) {
			$model = $event->result;
		}
		// End Event
		
		// Event: editBeforeAction
		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.beforeAction');
		$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.beforeAction');
		if ($event->isStopped()) { return $event->result; }
		if ($event->result instanceof Table) {
			$model = $event->result;
		}
		// End Event

		$primaryKey = $model->primaryKey();
		$idKey = $model->aliasField($primaryKey);

		if ($model->exists([$idKey => $id])) {
			$query = $model->find()->where([$idKey => $id]);

			// Event: viewEditBeforeQuery
			$this->debug(__METHOD__, ': Event -> ControllerAction.Model.viewEdit.beforeQuery');
			$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.viewEdit.beforeQuery', null, [$query]);
			if ($event->isStopped()) { return $event->result; }
			// End Event

			// Event: editBeforeQuery
			$this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.beforeQuery');
			$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.beforeQuery', null, [$query]);
			if ($event->isStopped()) { return $event->result; }
			// End Event

			$entity = $query->first();

			if (empty($entity)) {
				$this->Alert->warning('general.notExists');
				return $this->controller->redirect($this->url('index'));
			}
			
			if ($this->request->is(['get'])) {
				// Event: editOnInitialize
				$this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.onInitialize');
				$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.onInitialize', null, [$entity]);
				if ($event->isStopped()) { return $event->result; }
				// End Event
			} else if ($this->request->is(['post', 'put'])) {
				$submit = isset($this->request->data['submit']) ? $this->request->data['submit'] : 'save';
				$patchOptions = new ArrayObject([]);
				$requestData = new ArrayObject($request->data);

				$params = [$entity, $requestData, $patchOptions];

				if ($submit == 'save') {
					// Event: addEditBeforePatch
					$this->debug(__METHOD__, ': Event -> ControllerAction.Model.addEdit.beforePatch');
					$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.addEdit.beforePatch', null, $params);
					if ($event->isStopped()) { return $event->result; }
					// End Event
					
					// Event: editBeforePatch
					$this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.beforePatch');
					$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.beforePatch', null, $params);
					if ($event->isStopped()) { return $event->result; }
					// End Event

					$patchOptionsArray = $patchOptions->getArrayCopy();
					$request->data = $requestData->getArrayCopy();
					$entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);

					$process = function ($model, $entity) {
						return $model->save($entity);
					};

					// Event: onBeforeSave
					$this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.beforeSave');
					$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.beforeSave', null, [$entity, $requestData]);
					if ($event->isStopped()) { return $event->result; }
					if (is_callable($event->result)) {
						$process = $event->result;
					}
					// End Event

					if ($process($model, $entity)) {
						// event: onSaveSuccess
						$this->Alert->success('general.edit.success');

						// Event: editAfterSave
						$this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.afterSave');
						$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.afterSave', null, $params);
						if ($event->isStopped()) { return $event->result; }
						// End Event
						
						return $this->controller->redirect($this->url('view'));
					} else {
						// event: onSaveFailed
						$this->log($entity->errors(), 'debug');
						$this->Alert->error('general.edit.failed');
					}
				} else {
					$patchOptions['validate'] = false;
					$methodKey = 'on' . ucfirst($submit);

					// Event: addEditOnReload
					$eventKey = 'ControllerAction.Model.addEdit.' . $methodKey;
					$this->debug(__METHOD__, ': Event -> ' . $eventKey);
					$method = 'addEdit' . ucfirst($methodKey);
					$event = $this->dispatchEvent($this->model, $eventKey, $method, $params);
					if ($event->isStopped()) { return $event->result; }
					// End Event

					// Event: editOnReload
					$eventKey = 'ControllerAction.Model.edit.' . $methodKey;
					$this->debug(__METHOD__, ': Event -> ' . $eventKey);
					$method = 'edit' . ucfirst($methodKey);
					$event = $this->dispatchEvent($this->model, $eventKey, $method, $params);
					if ($event->isStopped()) { return $event->result; }
					// End Event

					$patchOptionsArray = $patchOptions->getArrayCopy();
					$request->data = $requestData->getArrayCopy();
					$entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);
				}
			}

			// Event: addEditAfterAction
			$this->debug(__METHOD__, ': Event -> ControllerAction.Model.addEdit.afterAction');
			$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.addEdit.afterAction', null, [$entity]);
			if ($event->isStopped()) { return $event->result; }
			// End Event

			// Event: editAfterAction
			$this->debug(__METHOD__, ': Event -> ControllerAction.Model.edit.afterAction');
			$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.edit.afterAction', null, [$entity]);
			if ($event->isStopped()) { return $event->result; }
			// End Event

			$this->controller->set('data', $entity);
		} else {
			$this->Alert->warning('general.notExists');
			return $this->controller->redirect($this->url('index'));
		}
		$this->config['form'] = true;
	}

	public function remove($id=0) {
		$request = $this->request;
		$model = $this->model;
		$settings = new ArrayObject([]);

		// Event: deleteBeforeAction
		$this->debug(__METHOD__, ': Event -> ControllerAction.Model.delete.beforeAction');
		$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.delete.beforeAction', null, [$settings]);
		if ($event->isStopped()) { return $event->result; }
		if ($settings->offsetExists('model')) {
			if ($settings['model'] instanceof Table) {
				$model = $settings['model'];
			} else {
				$model = TableRegistry::get($settings['model']);
			}
		}
		if ($settings->offsetExists('deleteStrategy')) {
			$this->deleteStrategy = $settings['deleteStrategy'];
		}
		// End Event

		$primaryKey = $model->primaryKey();
		$idKey = $model->aliasField($primaryKey);

		if ($request->is('get')) {
			if ($model->exists([$idKey => $id])) {
				$entity = $model->get($id);

				$query = $model->find();
				$listOptions = new ArrayObject([]);

				// Event: deleteOnInitialize
				$this->debug(__METHOD__, ': Event -> ControllerAction.Model.delete.onInitialize');
				$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.delete.onInitialize', null, [$entity, $query, $listOptions]);
				if ($event->isStopped()) { return $event->result; }
				// End Event

				if ($listOptions->count() == 0) {
					$listOptions['keyField'] = 'id';
					$listOptions['valueField'] = 'name';
				}
				$query->find('list', $listOptions->getArrayCopy())->where([$idKey . ' <> ' => $id]);

				// Event: deleteUpdateCovertOptions
				$this->debug(__METHOD__, ': Event -> ControllerAction.Model.onGetConvertOptions');
				$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.onGetConvertOptions', null, [$entity, $query]);
				if ($event->isStopped()) { return $event->result; }

				$convertOptions = $query->toArray();
				if (empty($convertOptions)) {
					$convertOptions[''] = __('No Available Options');
				}

				$associations = [];
				foreach ($model->associations() as $assoc) {
					if (!$assoc->dependent()) {
						if ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany') {
							if (!array_key_exists($assoc->alias(), $associations)) {
								$count = 0;
								if($assoc->type() == 'oneToMany') {
									$count = $assoc->find()
									->where([$assoc->aliasField($assoc->foreignKey()) => $id])
									->count();
								} else {
									$modelAssociationTable = $assoc->junction();
									$count = $modelAssociationTable->find()
										->where([$modelAssociationTable->aliasField($assoc->foreignKey()) => $id])
										->count();
								}
								$title = $this->Alert->getMessage($assoc->aliasField('title'));
								if ($title == '[Message Not Found]') {
									$title = $assoc->name();
								}
								$associations[$assoc->alias()] = ['model' => $title, 'count' => $count];
							}
						}
					}
				}

				$this->controller->set('data', $entity);
				$this->controller->set('convertOptions', $convertOptions);
				$this->controller->set('associations', $associations);
			} else {
				$this->Alert->warning('general.notExists');
				return $this->controller->redirect($this->url('index'));
			}
		} else if ($request->is('delete') && !empty($request->data[$primaryKey])) {
			$this->autoRender = false;
			$id = $request->data[$primaryKey];
			$deleteOptions = new ArrayObject([]);

			$process = function ($model, $id, $deleteOptions) {
				$entity = $model->get($id);
				return $model->delete($entity, $deleteOptions->getArrayCopy());
			};

			// Event: onBeforeDelete
			$params = [$deleteOptions, $id];
			$this->debug(__METHOD__, ': Event -> ControllerAction.Model.onBeforeDelete');
			$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.onBeforeDelete', null, $params);
			if ($event->isStopped()) { return $event->result; }
			if (is_callable($event->result)) {
				$process = $event->result;
			}
			// End Event
			if ($this->deleteStrategy == 'cascade') {
				if ($process($model, $id, $deleteOptions)) {
					$this->Alert->success('general.delete.success');
				} else {
					$this->Alert->error('general.delete.failed');
				}
				return $this->controller->redirect($this->url('index'));
			} else {
				$transferFrom = $this->request->data('id');
				$transferTo = $this->request->data('transfer_to');

				// Checking of association for delete transfer, if the association count is 0,
				// it means that no record is associated with it and it is safe to delete the record
				$totalCount = 0;

				if (empty($transferTo)) {
					$associations = [];
					foreach ($model->associations() as $assoc) {
						if ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany') {
							if (!array_key_exists($assoc->alias(), $associations)) {
								$count = 0;
								if($assoc->type() == 'oneToMany') {
									$count = $assoc->find()
									->where([$assoc->aliasField($assoc->foreignKey()) => $transferFrom])
									->count();
									$totalCount = $totalCount + $count;
								} else {
									$modelAssociationTable = $assoc->junction();
									$count += $modelAssociationTable->find()
										->where([$modelAssociationTable->aliasField($assoc->foreignKey()) => $transferFrom])
										->count();
									$totalCount = $totalCount + $count;
								}
								$associations[$assoc->alias()] = $assoc->table();
							}
						}
					}
				}
				if ($totalCount > 0) {
					$this->Alert->error('general.deleteTransfer.restrictDelete');
					return $this->controller->redirect($this->url('remove'));
				} else {
					$associations = [];
					foreach ($model->associations() as $assoc) {
						if ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany') {
							if (!array_key_exists($assoc->alias(), $associations)) {
								// $assoc->dependent(false);
								$associations[$assoc->alias()] = $assoc;
							}
						}
					}

					if ($process($model, $transferFrom, $deleteOptions)) {
						$id = $request->data[$primaryKey];
						$transferOptions = new ArrayObject([]);

						$transferProcess = function($associations, $transferFrom, $transferTo, $model) {
							foreach ($associations as $assoc) {
								if ($assoc->type() == 'oneToMany') {
									$assoc->updateAll(
										[$assoc->foreignKey() => $transferTo],
										[$assoc->foreignKey() => $transferFrom]
									);

								} else if ($assoc->type() == 'manyToMany') {
									$modelAssociationTable = $assoc->junction();

									// List of the target foreign keys for subqueries
									$targetForeignKeys = $modelAssociationTable->find()
										->select([$modelAssociationTable->aliasField($assoc->targetForeignKey())])
										->where([
											$modelAssociationTable->aliasField($assoc->foreignKey()) => $transferTo
										]);

									// List of id in the junction table to be deleted
									$idNotToUpdate = $modelAssociationTable->find('list',[
											'keyField' => 'id',
											'valueField' => 'id'
										])
										->where([
											$modelAssociationTable->aliasField($assoc->foreignKey()) => $transferFrom,
											$modelAssociationTable->aliasField($assoc->targetForeignKey()).' IN' => $targetForeignKeys
										])
										->toArray();

									$condition = [];

									if (empty($idNotToUpdate)) {
										$condition = [$assoc->foreignKey() => $transferFrom];
									} else {
										$condition = [$assoc->foreignKey() => $transferFrom, 'id NOT IN' => $idNotToUpdate];
									}
									
									// Update all transfer records
									$modelAssociationTable->updateAll(
										[$assoc->foreignKey() => $transferTo],
										$condition
									);
								}
							}
						};

						// Event: onDeleteTransfer
						$params = [$transferOptions, $id];
						$this->debug(__METHOD__, ': Event -> ControllerAction.Model.onDeleteTransfer');
						$event = $this->dispatchEvent($this->model, 'ControllerAction.Model.onDeleteTransfer', null, $params);
						if ($event->isStopped()) { return $event->result; }
						if (is_callable($event->result)) {
							$transferProcess = $event->result;
						}

						$transferProcess($associations, $transferFrom, $transferTo, $model);
						$this->Alert->success('general.delete.success');
					} else {
						$this->Alert->error('general.delete.failed');
					}
					return $this->controller->redirect($this->url('index'));
				}
			}
		} else {
			$this->Alert->error('general.delete.failed');
			return $this->controller->redirect($this->url('index'));
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

	public function reorder() {
		$this->autoRender = false;
		$this->controller->autoRender=false;
		$request = $this->request;

		if ($request->is('ajax')) {
			$model = $this->model;
			$primaryKey = $model->primaryKey();
			$orderField = $this->orderField;
			
			$ids = json_decode($request->data("ids"));

			$originalOrder = $model->find('list')
				->where([$model->aliasField($primaryKey).' IN ' => $ids])
				->select(['id' => $model->aliasField($primaryKey), 'name' => $model->aliasField($orderField)])
				->order([$model->aliasField($orderField)])
				->toArray();

			$originalOrder = array_reverse($originalOrder);

			foreach ($ids as $order => $id) {
				$orderValue = array_pop($originalOrder);
				$model->updateAll([$orderField => $orderValue], [$primaryKey => $id]);
			}
		}
	}
	
	// NOT IN USED
	// public function fixOrder($conditions) {
	// 	$model = $this->model;
	// 	$count = $model->find('count', array('conditions' => $conditions));
	// 	if($count > 0) {
	// 		$list = $model->find('list', array(
	// 			'conditions' => $conditions,
	// 			'order' => array(
	// 				$model->alias().'.'.$this->orderField,
	// 				$model->alias().'.'.$model->primaryKey()
	// 			)
	// 		));
	// 		$order = 1;
	// 		foreach($list as $id => $name) {
	// 			$model->id = $id;
	// 			$model->saveField($this->orderField, $order++);
	// 		}
	// 	}
	// }

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
		return ['plugin' => $plugin, 'model' => $modelClass];
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

		$order = false;
		if (array_key_exists('after', $attr)) {
			$after = $attr['after'];
			$order = $this->getOrderValue($model, $after, 'after');
		} else if (array_key_exists('before', $attr)) {
			$before = $attr['before'];
			$order = $this->getOrderValue($model, $before, 'before');
		}
		
		if (!empty($this->plugin)) {
			$className = $this->plugin . '.' . $className;
		}
		
		$_attr = [
			'type' => 'string',
			'null' => true,
			'autoIncrement' => false,
			'order' => $order ? $order : $model->fieldOrder,
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
		$params = [$attr, $this->currentAction, $this->request];
		$this->debug(__METHOD__, ': Event -> ' . $eventKey);
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
			uasort($fields, [$this, 'sortFields']);
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

	public function debug($method, $message='') {
		if ($this->debug) {
			$pos = strrpos($method, "\\");
			if ($pos !== false) {
				$pos++;
				$method = substr($method, $pos);
			}
			Log::write('debug', $method . $message);
		}
	}

	public function getTriggerFrom() {
		return $this->triggerFrom;
	}

	private function getOrderValue($model, $field, $insert) {
		if (!array_key_exists($field, $model->fields)) {
			Log::write('Attempted to add ' . $insert . ' invalid field: ' . $field);
			return false;
		}
		$order = 0;

		uasort($model->fields, [$this, '_sortByOrder']);

		if ($insert == 'before') {
			foreach ($model->fields as $key => $attr) {
				if ($key == $field) {
					$order = $attr['order'] - 1;
					break;
				}
				$model->fields[$key]['order'] = $attr['order'] - 1;
			}
		} else if ($insert == 'after') {
			$start = false;
			foreach ($model->fields as $key => $attr) {
				if ($start) {
					$model->fields[$key]['order'] = $attr['order'] + 1;
				}
				if ($key == $field) {
					$start = true;
					$order = $attr['order'] + 1;
				}
			}
		}
		return $order;
	}

	public function getAssociatedRecords($model, $entity) {
		$primaryKey = $model->primaryKey();
		$id = $entity->$primaryKey;
		$associations = [];
		foreach ($model->associations() as $assoc) {
			if (!$assoc->dependent() && ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany')) {
				if (!array_key_exists($assoc->alias(), $associations)) {
					$count = 0;
					if ($assoc->type() == 'oneToMany') {
						$count = $assoc->find()
						->where([$assoc->aliasField($assoc->foreignKey()) => $id])
						->count();
					} else {
						$modelAssociationTable = $assoc->junction();
						$count = $modelAssociationTable->find()
							->where([$modelAssociationTable->aliasField($assoc->foreignKey()) => $id])
							->count();
					}
					$title = $assoc->name();
					$event = $assoc->dispatchEvent('ControllerAction.Model.transfer.getModelTitle', [], $this);
					if (!is_null($event->result)) {
						$title = $event->result;
					}
					$associations[$assoc->alias()] = ['model' => $title, 'count' => $count];
				}
			}
		}
		return $associations;
	}

	public function hasAssociatedRecords($model, $entity) {
		$records = $this->getAssociatedRecords($model, $entity);
		$found = false;
		foreach ($records as $count) {
			if ($count['count'] > 0) {
				$found = true;
				break;
			}
		}
		return $found;
	}
}