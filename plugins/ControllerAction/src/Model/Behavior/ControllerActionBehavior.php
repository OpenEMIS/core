<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
// use Cake\I18n\Time;
use Cake\Log\Log;

class ControllerActionBehavior extends Behavior {
	protected $_defaultConfig = [
		'actions' => [
			'index' => true, 
			'add' => true, 
			'view' => true, 
			'edit' => true, 
			'remove' => 'cascade',
			'search' => ['orderField' => 'order'],
			'reorder' => ['orderField' => 'order']
		],
		'fields' => [
			'excludes' => ['modified', 'created']
		]
	];

	public function initialize(array $config) {
		$this->attachActions();
		$this->initializeFields();
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();

		// Model default priority is 10
		// $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 5];
		return $events;
	}

	public function buildValidator(Event $event, Validator $validator, $name) {
		$schema = $this->_table->schema();

		$columns = $schema->columns();
		foreach ($columns as $col) {
			$attr = $schema->column($col);

			if ($validator->hasField($col)) {
				$set = $validator->field($col);

				if (!$set->isEmptyAllowed()) {
					$set->add('notBlank', ['rule' => 'notBlank']);
				}
				if (!$set->isPresenceRequired()) {
					if ($this->isForeignKey($col)) {
						$validator->requirePresence($col);
					}
				}
			} else {
				if (array_key_exists('null', $attr)) {
					$ignoreFields = $this->config('fields.excludes');
					if ($attr['null'] === false && $col !== 'id' && !in_array($col, $ignoreFields)) {
						$validator->add($col, 'notBlank', ['rule' => 'notBlank']);
						if ($this->isForeignKey($col)) {
							$validator->requirePresence($col);
						}
					}
				}
			}
		}
	}

	private function attachActions() {
		$actions = $this->config('actions');
		$model = $this->_table;

		foreach ($this->_defaultConfig['actions'] as $action => $value) {
			if ($actions[$action] !== false) {
				$behavior = ucfirst($action);
				if (file_exists(__DIR__ . DS . $behavior . 'Behavior.php')) {
					if (is_array($value)) {
						if ($model->hasBehavior($behavior)) {
							$model->removeBehavior($behavior);
							Log::write('debug', 'Conflicts in behavior: ' . $behavior);
						}
						$model->addBehavior('ControllerAction.' . $behavior, $value);
					} else {
						$model->addBehavior('ControllerAction.' . $behavior);
					}
					
				}
			}
		}
	}

	private function initializeFields() {
		$alias = $this->_table->alias();
		$className = $this->_table->registryAlias();
		$schema = $this->_table->schema();

		$columns = $schema->columns();
		$fields = [];
		$visibility = ['view' => true, 'edit' => true, 'index' => true];
		$order = 10;

		foreach ($columns as $i => $col) {
			$attr = $schema->column($col);
			$attr['model'] = $alias;
			$attr['className'] = $className;
			$attr['visible'] = $col != 'password' ? $visibility : false;
			$attr['order'] = $order * ($i+1);
			$attr['field'] = $col;

			$fields[$col] = $attr;
		}
		$primaryKey = $this->_table->primaryKey();

		if (!is_array($primaryKey) && array_key_exists($primaryKey, $fields)) { // not composite primary keys
			$fields[$primaryKey]['type'] = 'hidden';
		}

		$excludedFields = $this->config('fields.excludes');
		foreach ($excludedFields as $field) {
			if (array_key_exists($field, $fields)) {
				$fields[$field]['visible']['index'] = false;
				$fields[$field]['visible']['view'] = true;
				$fields[$field]['visible']['edit'] = false;
				$fields[$field]['labelKey'] = 'general';
			}
		}
		
		$this->_table->fields = $fields;
	}

	public function actions($action=null) {
		$actions = $this->config('actions');

		$data = false;
		if (is_null($action)) {
			foreach ($actions as $key => $enabled) {
				if ($enabled !== false) {
					$data[$key] = $enabled;
				}
			}
		} else {
			if (array_key_exists($action, $actions)) {
				$data = $actions[$action];
			}
		}
		return $data;
	}

	public function paramsPass($index=null) {
		$params = $this->_table->request->pass;
		if (count($params) > 0) {
			if (!is_numeric($params[0])) {
				array_shift($params);
			}
			if (!is_null($index)) {
				if (isset($params[$index])) {
					$params = $params[$index];
				} else {
					$params = null;
				}
			}
		}
		return $params;
	}

	public function paramsQuery() {
		return $this->_table->request->query;
	}

	public function params() {
		$params = $this->paramsPass();
		return array_merge($params, $this->paramsQuery());
	}

	public function toggle($action, $enabled) {
		$actions = $this->config('actions');

		if (array_key_exists($action, $actions)) {
			$flag = $actions[$action];
			if ($flag != $enabled) {
				if ($enabled) {
					$this->_table->addBehavior('ControllerAction.' . ucfirst($action));
				} else {
					$this->_table->removeBehavior(ucfirst($action));
				}
				$actions[$action] = $enabled;
			}
		} else {
			Log::write('debug', __METHOD__ . ': ' . $action . ' does not exists!');
		}
		$this->config('actions', $actions);
	}

	public function url($action, $params = true /* 'PASS' | 'QUERY' | false */) {
		$controller = $this->_table->controller;
		$url = [
			'plugin' => $controller->plugin,
			'controller' => $controller->name,
			'action' => $this->_table->alias,
			0 => $action
		];

		if ($params === true) {
			$url = array_merge($url, $this->params());
		} else if ($params === 'PASS') {
			$url = array_merge($url, $this->paramsPass());
		} else if ($params === 'QUERY') {
			$url = array_merge($url, $this->paramsQuery());
		}
		
		return $url;
	}

	public function field($name, $attr=[]) {
		$model = $this->_table;

		if (!isset($model->fieldOrder)) {
			$model->fieldOrder = 1;
		}
		$order = $model->fieldOrder++;

		if (array_key_exists('after', $attr)) {
			$after = $attr['after'];
			$order = $this->getOrderValue($after, 'after');
		} else if (array_key_exists('before', $attr)) {
			$before = $attr['before'];
			$order = $this->getOrderValue($before, 'before');
		}
		if ($order == false) {
			$order = $model->fieldOrder - 1;
		}
		
		$_attr = [
			'type' => 'string',
			'null' => true,
			'autoIncrement' => false,
			'visible' => true,
			'field' => $name,
			'model' => $model->alias(),
			'className' => $model->registryAlias()
		];

		if (array_key_exists($name, $model->fields)) {
			$_attr = array_merge($_attr, $model->fields[$name]);
		}

		$attr = array_merge($_attr, $attr);
		$attr['order'] = $order;
		$model->fields[$name] = $attr;

		$method = 'onUpdateField' . Inflector::camelize($name);
		$eventKey = 'ControllerAction.Model.' . $method;

		$params = [$attr, $model->action, $model->request];
		$event = $this->dispatchEvent($model, $eventKey, $method, $params);
		if (is_array($event->result)) {
			$model->fields[$field] = $event->result;
		}
	}

	public function setFieldVisible($actions, $fields) {
		foreach ($this->_table->fields as $key => $attr) {
			if (in_array($key, $fields)) {
				foreach ($actions as $action) {
					$this->_table->fields[$key]['visible'][$action] = true;
				}
			} else {
				$this->_table->fields[$key]['visible'] = false;
			}
		}
	}

	public function setFieldOrder($field, $order=0) {
		$fields = $this->_table->fields;

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
			// uasort($fields, [$this, 'sortFields']);
		}
		$this->_table->fields = $fields;
	}

	public function isForeignKey($field) {
		$model = $this->_table;
		foreach ($model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					return true;
				}
			}
		}
		return false;
	}

	public function getAssociatedModel($field, $type='belongsTo' /* hasOne | hasMany | belongsToMany */) {
		$associationTypes = [
			'belongsTo' => 'manyToOne',
			'hasMany' => 'oneToMany',
			'belongsToMany' => 'manyToMany'
		];
		$model = null;

		foreach ($this->_table->associations() as $assoc) {
			if ($assoc->type() == $associationTypes[$type]) { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					$model = $assoc;
					break;
				}
			}
		}
		return $model;
	}

	public function getAssociatedEntity($field) {
		$associationKey = $this->getAssociatedModel($field);
		$associatedEntity = null;
		if (is_object($associationKey)) {
			$associatedEntity = Inflector::underscore(Inflector::singularize($associationKey->alias()));
		} else {
			// die($field . '\'s association not found in ' . $this->model->alias());
			Log::write('debug', $field . '\'s association not found in ' . $this->_table->alias());
		}
		return $associatedEntity;
	}

	public function getContains($type = 'belongsTo') { // type is not being used atm
		$model = $this->_table;
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

	public function endsWith($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}

	private function onEvent($subject, $eventKey, $method) {
		$eventMap = $subject->implementedEvents();
		if (!array_key_exists($eventKey, $eventMap) && !is_null($method)) {
			if (method_exists($subject, $method) || $subject->behaviors()->hasMethod($method)) {
				$subject->eventManager()->on($eventKey, [], [$subject, $method]);
			}
		}
	}

	private function dispatchEvent($subject, $eventKey, $method=null, $params=[], $autoOff=false) {
		$this->onEvent($subject, $eventKey, $method);
		$event = new Event($eventKey, $this, $params);
		$event = $subject->eventManager()->dispatch($event);
		if(!is_null($method) && $autoOff) {
			$this->offEvent($subject, $eventKey, $method);
		}
		return $event;
	}

	private function offEvent($subject, $eventKey, $method) {
		$subject->eventManager()->off($eventKey, [$subject, $method]);
	}

	private function getOrderValue($field, $insert) {
		$model = $this->_table;
		if (!array_key_exists($field, $model->fields)) {
			Log::write('Attempted to add ' . $insert . ' invalid field: ' . $field);
			return false;
		}
		$order = 0;

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
}
