<?php
namespace ControllerAction\Model\Traits;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Controller\Exception\MissingActionException;
use Cake\Http\Session;
use Cake\Http\ServerRequest;

//use ControllerAction\Model\Traits\ControllerActionV4Trait;
//use ControllerActionV4Trait; // extended functionality from v4

trait ControllerActionV4Trait {
	// implementing CA v4 functions in using Traits
	// when CA v3 is completely not in use, this trait will replace all logic in CAComponent

	private function _initComponents($model) {
		$model->controller = $this->controller;
		$model->request = $this->controller->getRequest();
		$model->Session =  $this->controller->getRequest()->getSession();

		// Copy all component objects from Controller to Model
        $registry = $this->controller->components();
        foreach ($registry->loaded() as $name) {
            // Skip legacy/removed components (e.g., Cookie in CakePHP 4)
            if (in_array($name, ['Cookie'], true)) {
                continue;
            }

            $instance = $registry->get($name); // <-- safe, no __get()
            if ($instance !== null) {
                $model->{$name} = $instance;
            }
        }
	}

	private function _render($model) {

		list($plugin, $alias) = pluginSplit($model->getRegistryAlias());
		if (empty($plugin)) {
			$path = APP . 'Template' . DS . $this->controller->getName() . DS;
		} else {
			//$path = ROOT . DS . 'plugins' . DS . $plugin . DS . 'src' . DS . 'Template' . DS;
			$path = ROOT . DS . 'plugins' . DS . $plugin . DS . 'templates' . DS;
		}

		$this->ctpFolder = $model->getAlias();
		$ctp = $this->ctpFolder . DS . $model->action;
		if (file_exists($path . DS . $ctp . '.php')) {
			if ($this->autoRender) {
				$this->autoRender = false;
				$this->controller->render($ctp);
			}
		} else {
			if ($this->autoRender) {
				if (empty($this->view)) {
					$view = $model->action == 'add' ? 'edit' : $model->action;
					//$this->controller->render($this->templatePath . $view);
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
			if (isset($attr['options'])) {
				if (in_array($attr['type'], ['string', 'integer'])) {
					$model->fields[$key]['type'] = 'select';
				}
				if (empty($attr['options']) && empty($attr['attr']['empty'])) {
					if (!isset($attr['empty'])) {
						$model->fields[$key]['attr']['empty'] = $this->Alert->getMessage('general.select.noOptions');
					}
				}

				// for automatic adding of '-- Select --' if there are no '' value fields in dropdown
				$addSelect = true;
				if ($attr['type'] == 'chosenSelect') {
                    $addSelect = false;
                }

				if (isset($attr['select'])) {
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
			if (!isset($attr['type'])) {
				pr('Please set a data type for ' . $key);
			}

			$sortableTypes = ['string', 'date', 'time', 'datetime'];
			if (in_array($attr['type'], $sortableTypes) && !isset($attr['sort']) && $model->hasField($key)) {
				$model->fields[$key]['sort'] = true;
			} else if ($attr['type'] == 'select' && !isset($attr['options'])) {
				if ($model->isForeignKey($key)) {
					$associatedObject = $model->getAssociatedModel($key);

					$query = $associatedObject->find();

					// need to include associated object
					$event = new Event('ControllerAction.Model.onPopulateSelectOptions', $this, [$query]);
					$event = $associatedObject->getEventManager()->dispatch($event);
					if ($event->isStopped()) { return $event->getResult(); }
					if (!empty($event->getResult())) {
						$query = $event->getResult();
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

			if (isset($attr['onChangeReload'])) {

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
			//POCOR-8488 starts
 			//return $a["order"] - $b["order"];
			$aOrder = isset($a['order']) ? (int)$a['order'] : PHP_INT_MAX;
			$bOrder = isset($b['order']) ? (int)$b['order'] : PHP_INT_MAX;
			return $aOrder - $bOrder;
			//POCOR-8488 ends
		}
	}

	private function _validateOptions($options) {
		if (!isset($options['alias'])) {
			pr('There is no alias set for ' . $this->request->getAttribute('action'));
			die;
		}
		if (!isset($options['className'])) {
			pr('There is no className set for ' . $this->request->getAttribute('action'));
			die;
		}

		$className = $options['className'];
		$alias = $options['alias'];
		$model = $this->controller->fetchTable($className);
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

		$paramsPass = $this->controller->getRequest()->getParam('pass');
		$action = 'index';

		if (count($paramsPass) > 0) {
			if (!is_numeric($paramsPass[0])) { // this is an action
				$action = array_shift($paramsPass);
			}
		}

		$model->action = $action;

		$entity = null;
		$event = $controller->dispatchEvent('ControllerAction.Controller.onInitialize', [$model, $extra], $this);
		if ($event->isStopped()) { return $event->getResult(); }

		$event = $model->dispatchEvent('ControllerAction.Model.beforeAction', [$extra], $this);

		if ($event->isStopped()) { return $event->getResult(); }
		// dispatch event for specific action
		$event = $model->dispatchEvent("ControllerAction.Model.$action", [$extra], $this);
		if ($event->isStopped()) { return $event->getResult(); }
		if ($event->getResult() instanceof Entity) {
			$entity = $event->getResult();
		} else if ($event->getResult() instanceof Response) {
			return $event->getResult();
		} else if (is_null($event->getResult())) {
			throw new MissingActionException([
                'controller' => $controller->getName() . "Controller",
                'action' => $action,
                'prefix' => '',
                'plugin' => $this->controller->getRequest()->getParam('plugin')
            ]);
		}

		$extra['entity'] = $entity;
		$event = $model->dispatchEvent('ControllerAction.Model.afterAction', [$extra], $this);
		if ($event->isStopped()) { return $event->getResult(); }
		$elements = $extra['elements'];
		uasort($elements, [$this, '_sortByOrder']);
		$this->_renderFields($model);
		uasort($model->fields, [$this, '_sortByOrder']);

		$extra['config']['action'] = $model->action;
		$extra['config']['table'] = $model;
		$extra['config']['fields'] = $model->fields;
		$this->deprecatedFunctions(['model' => $model->getAlias()]);
		$controller->set('ControllerAction', $extra['config']);
		$controller->set('elements', $elements);
		$this->_render($model);

	}

	private function deprecatedFunctions($params) {
		$this->controller->set('model', $params['model']);
	}
}
