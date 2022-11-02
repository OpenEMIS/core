<?php
namespace ControllerAction\Model\Traits;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Response;
use Cake\Controller\Exception\MissingActionException;

//use ControllerAction\Model\Traits\ControllerActionV4Trait;
//use ControllerActionV4Trait; // extended functionality from v4

trait ControllerActionV4Trait {
	// implementing CA v4 functions in using Traits
	// when CA v3 is completely not in use, this trait will replace all logic in CAComponent

	private function _initComponents($model) {
		$model->controller = $this->controller;
		$model->request = $this->request;
		$model->Session = $this->request->session();

		// Copy all component objects from Controller to Model
		$components = $this->controller->components()->loaded();
		foreach ($components as $component) {
			$model->{$component} = $this->controller->{$component};
		}
	}

	private function _render($model) {
		list($plugin, $alias) = pluginSplit($model->registryAlias());

		if (empty($plugin)) {
			$path = APP . 'Template' . DS . $this->controller->name . DS;
		} else {
			$path = ROOT . DS . 'plugins' . DS . $plugin . DS . 'src' . DS . 'Template' . DS;
		}
		$this->ctpFolder = $model->alias();
		$ctp = $this->ctpFolder . DS . $model->action;

		if (file_exists($path . DS . $ctp . '.ctp')) {
			if ($this->autoRender) {
				$this->autoRender = false;
				$this->controller->render($ctp);
			}
		} else {
			if ($this->autoRender) {
				if (empty($this->view)) {
					$view = $model->action == 'add' ? 'edit' : $model->action;
					// $this->controller->render($this->templatePath . $view);
					$this->controller->render($this->templatePath . 'template');
				} else {
					$this->controller->render($this->view);
				}
			}
		}
	}

	private function _renderFields($model) {
		foreach ($model->fields as $key => $attr) {
			if ($key == $this->orderField) {
				$model->fields[$this->orderField]['visible'] = ['view' => false];
			}
			if (array_key_exists('options', $attr)) {
				if (in_array($attr['type'], ['string', 'integer'])) {
					$model->fields[$key]['type'] = 'select';
				}
				if (empty($attr['options']) && empty($attr['attr']['empty'])) {
					if (!array_key_exists('empty', $attr)) {
						$model->fields[$key]['attr']['empty'] = $this->Alert->getMessage('general.select.noOptions');
					}
				}

				// for automatic adding of '-- Select --' if there are no '' value fields in dropdown
				$addSelect = true;
				if ($attr['type'] == 'chosenSelect') {
                    $addSelect = false;
                }

				if (array_key_exists('select', $attr)) {
					if ($attr['select'] === false) {
						$addSelect = false;
					} else {
						$addSelect = true;
					}
				}
				if ($addSelect) {
					if (is_array($attr['options'])) {
						// need to check if options has any ''
						if (!array_key_exists('', $attr['options'])) {
							if ($attr['type'] != 'chosenSelect') {
                                if (in_array($model->action, ['edit', 'add'])) {
                                    $model->fields[$key]['options'] = ['' => __('-- Select --')] + $attr['options'];
                                }
							} else {
								$model->fields[$key]['options'] = ['' => __('-- Select --')] + $attr['options'];
							}
						}
					}
				}
			}

			// make field sortable by default if it is a string data-type
			if (!array_key_exists('type', $attr)) {
				pr('Please set a data type for ' . $key);
			}

			$sortableTypes = ['string', 'date', 'time', 'datetime'];
			if (in_array($attr['type'], $sortableTypes) && !array_key_exists('sort', $attr) && $model->hasField($key)) {
				$model->fields[$key]['sort'] = true;
			} else if ($attr['type'] == 'select' && !array_key_exists('options', $attr)) {
				if ($model->isForeignKey($key)) {
					$associatedObject = $model->getAssociatedModel($key);

					$query = $associatedObject->find();

					// need to include associated object
					$event = new Event('ControllerAction.Model.onPopulateSelectOptions', $this, [$query]);
					$event = $associatedObject->eventManager()->dispatch($event);
					if ($event->isStopped()) { return $event->result; }
					if (!empty($event->result)) {
						$query = $event->result;
					}

					if ($model->action != 'index') { // should not populate options for index page
						if ($query instanceof Query) {
							$query->limit(500); // to prevent out of memory error, options should not be more than 500 records anyway
							$queryData = $query->toArray();
							$hasDefaultField = false;
							$defaultValue = false;
							$optionsArray = [];
							foreach ($queryData as $okey => $ovalue) {
								$optionsArray[$ovalue->id] = $ovalue->name;
								if ($ovalue->has('default')) {
									$hasDefaultField = true;
									if ($ovalue->default) {
										$defaultValue = $ovalue->id;
									}
								}
							}

							if (!empty($defaultValue) && !(is_bool($attr['default']) && !$attr['default'])) {
								$model->fields[$key]['default'] = $defaultValue;
							}
							if ($attr['type'] != 'chosenSelect') {
	                            if (in_array($model->action, ['edit', 'add'])) {
								    $optionsArray = ['' => __('-- Select --')] + $optionsArray;
	                            }
							}

							$model->fields[$key]['options'] = $optionsArray;
						} else {
							$model->fields[$key]['options'] = $query;
						}
					}
				}
			}

			if (array_key_exists('onChangeReload', $attr)) {

				if (!array_key_exists('attr', $model->fields[$key])) {
					$model->fields[$key]['attr'] = [];
				}
				$onChange = '';
				if (is_bool($attr['onChangeReload']) && $attr['onChangeReload'] == true) {
					$onChange = "$('#reload').click();return false;";
				} else {
					$onChange = "$('#reload').val('" . $attr['onChangeReload'] . "').click();return false;";
				}
				$model->fields[$key]['attr']['onchange'] = $onChange;
			}
		}
	}

	private function _sortByOrder($a, $b) {
 		if (!isset($a['order']) && !isset($b['order'])) {
 			return true;
 		} else if (!isset($a['order']) && isset($b['order'])) {
 			return true;
 		} else if (isset($a['order']) && !isset($b['order'])) {
 			return false;
 		} else {
 			return $a["order"] - $b["order"];
 		}
	}

	private function _validateOptions($options) {
		if (!array_key_exists('alias', $options)) {
			pr('There is no alias set for ' . $this->request->action);
			die;
		}

		if (!array_key_exists('className', $options)) {
			pr('There is no className set for ' . $this->request->action);
			die;
		}

		$className = $options['className'];
		$alias = $options['alias'];
		$model = $this->controller->loadModel($className);
		$model->alias = $alias;

		return $model;
	}

	public function process($options=[]) {
		$request = $this->request;
		$controller = $this->controller;

		$model = $this->_validateOptions($options);

		$this->_initComponents($model);

		$extra = new ArrayObject([
			'elements' => [],
			'config' => ['form' => false]
		]);

		$paramsPass = $request->params['pass'];
		$action = 'index';

		if (count($paramsPass) > 0) {
			if (!is_numeric($paramsPass[0])) { // this is an action
				$action = array_shift($paramsPass);
			}
		}

		$model->action = $action;
		$entity = null;

		$event = $controller->dispatchEvent('ControllerAction.Controller.onInitialize', [$model, $extra], $this);
		if ($event->isStopped()) { return $event->result; }

		$event = $model->dispatchEvent('ControllerAction.Model.beforeAction', [$extra], $this);
		if ($event->isStopped()) { return $event->result; }

		// dispatch event for specific action
		$event = $model->dispatchEvent("ControllerAction.Model.$action", [$extra], $this);
		if ($event->isStopped()) { return $event->result; }
		if ($event->result instanceof Entity) {
			$entity = $event->result;
		} else if ($event->result instanceof Response) {
			return $event->result;
		} else if (is_null($event->result)) {
			throw new MissingActionException([
                'controller' => $controller->name . "Controller",
                'action' => $action,
                'prefix' => '',
                'plugin' => $request->params['plugin'],
            ]);
		}

		$extra['entity'] = $entity;
		$event = $model->dispatchEvent('ControllerAction.Model.afterAction', [$extra], $this);
		if ($event->isStopped()) { return $event->result; }

		$elements = $extra['elements'];
		uasort($elements, [$this, '_sortByOrder']);

		$this->_renderFields($model);
		uasort($model->fields, [$this, '_sortByOrder']);

		$extra['config']['action'] = $model->action;
		$extra['config']['table'] = $model;
		$extra['config']['fields'] = $model->fields;

		$this->deprecatedFunctions(['model' => $model->alias()]);

		$controller->set('ControllerAction', $extra['config']);
		$controller->set('elements', $elements);
		$this->_render($model);
	}

	private function deprecatedFunctions($params) {
		$this->controller->set('model', $params['model']);
	}
}
