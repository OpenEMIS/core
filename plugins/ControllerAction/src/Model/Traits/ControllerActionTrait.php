<?php
namespace ControllerAction\Model\Traits;

trait ControllerActionTrait {
	protected $_controllerActionEvents = [
		'ControllerAction.Controller.onInitialize'			=> 'onInitialize',
		'ControllerAction.Controller.beforePaginate'		=> 'beforePaginate',
		'ControllerAction.Model.onPopulateSelectOptions'	=> 'onPopulateSelectOptions',
		'ControllerAction.Model.beforeAction'				=> 'beforeAction',
		'ControllerAction.Model.afterAction'				=> 'afterAction',
		'ControllerAction.Model.index.onInitializeButtons' 	=> 'indexOnInitializeButtons',
		'ControllerAction.Model.index.beforeAction'			=> 'indexBeforeAction',
		'ControllerAction.Model.index.beforePaginate'		=> 'indexBeforePaginate',
		'ControllerAction.Model.index.afterPaginate'		=> 'indexAfterPaginate',
		'ControllerAction.Model.index.afterAction'			=> 'indexAfterAction',
		'ControllerAction.Model.view.beforeAction'			=> 'viewBeforeAction',
		'ControllerAction.Model.view.beforeQuery'			=> 'viewBeforeQuery',
		'ControllerAction.Model.view.afterAction'			=> 'viewAfterAction',
		'ControllerAction.Model.add.beforeAction'			=> 'addBeforeAction',
		'ControllerAction.Model.add.onInitialize'			=> 'addOnInitialize',
		'ControllerAction.Model.add.beforePatch'			=> 'addBeforePatch',
		'ControllerAction.Model.add.afterAction'			=> 'addAfterAction',
		'ControllerAction.Model.edit.beforeAction'			=> 'editBeforeAction',
		'ControllerAction.Model.edit.beforeQuery'			=> 'editBeforeQuery',
		'ControllerAction.Model.edit.beforePatch'			=> 'editBeforePatch',
		'ControllerAction.Model.edit.afterAction'			=> 'editAfterAction'
	];

	public function getControllerActionEvents() {
		return $this->_controllerActionEvents;
	}

	public function implementedEvents() {
        $events = parent::implementedEvents();
        
        $controllerActionEvents = $this->getControllerActionEvents();
        
        foreach ($controllerActionEvents as $event => $method) {
            if (!method_exists($this, $method)) {
                continue;
            }
            $events[$event] = $method;
        }
        return $events;
    }

	public function ComponentAction() { // Redirect logic to functions in Component or Model
        return $this->ControllerAction->processAction();
    }
}
