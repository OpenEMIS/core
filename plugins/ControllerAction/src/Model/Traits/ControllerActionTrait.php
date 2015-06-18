<?php
namespace ControllerAction\Model\Traits;

trait ControllerActionTrait {
	protected $_controllerActionEvents = [
		'ControllerAction.Controller.onInitialize'			=> 'onInitialize',
		'ControllerAction.Controller.beforePaginate'		=> 'beforePaginate',
		'ControllerAction.Model.onPopulateSelectOptions'	=> 'onPopulateSelectOptions', // called when select options auto populated
		'ControllerAction.Model.onGetLabel'					=> 'onGetLabel', // called to get label from model
		'ControllerAction.Model.beforeAction'				=> 'beforeAction', // called before start of any actions
		'ControllerAction.Model.afterAction'				=> 'afterAction', // called after any actions
		'ControllerAction.Model.onFormatDate'				=> 'onFormatDate', // called before displaying date fields
		'ControllerAction.Model.onFormatTime'				=> 'onFormatTime', // called before displaying time fields
		'ControllerAction.Model.onFormatDateTime'			=> 'onFormatDateTime', // called before displaying datetime fields
		'ControllerAction.Model.index.onInitializeButtons' 	=> 'indexOnInitializeButtons',
		'ControllerAction.Model.index.beforeAction'			=> 'indexBeforeAction',
		'ControllerAction.Model.index.beforePaginate'		=> 'indexBeforePaginate',
		'ControllerAction.Model.index.afterPaginate'		=> 'indexAfterPaginate',
		'ControllerAction.Model.index.afterAction'			=> 'indexAfterAction',
		'ControllerAction.Model.view.beforeAction'			=> 'viewBeforeAction',
		'ControllerAction.Model.view.beforeQuery'			=> 'viewBeforeQuery',
		'ControllerAction.Model.view.afterAction'			=> 'viewAfterAction',
		'ControllerAction.Model.add.beforeAction'			=> 'addBeforeAction', // called before any add logic is executed
		'ControllerAction.Model.add.onInitialize'			=> 'addOnInitialize', // called on http get
		'ControllerAction.Model.add.beforePatch'			=> 'addBeforePatch', // called on http post before patching entity
		'ControllerAction.Model.add.onReload'				=> 'addOnReload', // called on http post
		'ControllerAction.Model.add.afterAction'			=> 'addAfterAction', // called after adding entity
		'ControllerAction.Model.edit.beforeAction'			=> 'editBeforeAction',
		'ControllerAction.Model.edit.beforeQuery'			=> 'editBeforeQuery',
		'ControllerAction.Model.edit.onInitialize'			=> 'editOnInitialize',
		'ControllerAction.Model.edit.beforePatch'			=> 'editBeforePatch',
		'ControllerAction.Model.edit.onReload'				=> 'editOnReload',
		'ControllerAction.Model.edit.afterAction'			=> 'editAfterAction',
		'ControllerAction.Model.addEdit.beforeAction'		=> 'addEditBeforeAction',
		'ControllerAction.Model.addEdit.beforePatch'		=> 'addEditBeforePatch',
		'ControllerAction.Model.addEdit.onReload'			=> 'addEditOnReload',
		'ControllerAction.Model.addEdit.afterAction'		=> 'addEditAfterAction'
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
