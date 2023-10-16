<?php
namespace ControllerAction\Model\Traits;

trait ControllerActionTrait {
	protected $_controllerActionEvents = [
		'ControllerAction.Controller.onInitialize'			=> 'onInitialize',
		// public function onInitialize(Event $event, Table $model, ArrayObject $extra) {}

		'ControllerAction.Controller.beforePaginate'		=> 'beforePaginate',
		// public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {}

		'ControllerAction.Model.onPopulateSelectOptions'	=> 'onPopulateSelectOptions', // called when select options auto populated
		// public function onPopulateSelectOptions(Event $event, Query $query) {}

		'ControllerAction.Model.onGetFieldLabel'			=> 'onGetFieldLabel', // called to get label from model
		// public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {}

		'ControllerAction.Model.onUpdateIncludes'			=> 'onUpdateIncludes', // called to include any css/js files
		// public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {}

		'ControllerAction.Model.onGetFormButtons'			=> 'onGetFormButtons', // called to add/remove form buttons
		// public function onGetFormButtons(Event $event, ArrayObject $buttons) {}

		'ControllerAction.Model.onUpdateDefaultActions'		=> 'onUpdateDefaultActions', // called to update default actions
		// public function onUpdateDefaultActions(Event $event) {}

		'ControllerAction.Model.beforeAction'				=> 'beforeAction', // called before start of any actions
		// public function beforeAction(Event $event) {}

		'ControllerAction.Model.afterAction'				=> 'afterAction', // called after any actions
		// public function afterAction(Event $event, $config) {}

		'ControllerAction.Model.onFormatDate'				=> 'onFormatDate', // called before displaying date fields
		// public function onFormatDate(Event $event, Time $dateObject) {}

		'ControllerAction.Model.onFormatTime'				=> 'onFormatTime', // called before displaying time fields
		// public function onFormatTime(Event $event, Time $dateObject) {}

		'ControllerAction.Model.onFormatDateTime'			=> 'onFormatDateTime', // called before displaying datetime fields
		// public function onFormatDateTime(Event $event, Time $dateObject) {}

		'ControllerAction.Model.onInitializeButtons'		=> 'onInitializeButtons',
		// public function onInitializeButtons(Event $event, ArrayObject $buttons, $action, $isFromModel) {}

		'ControllerAction.Model.index.beforeAction'			=> 'indexBeforeAction',
		// public function indexBeforeAction(Event $event, ArrayObject $settings) {}
        // if u need query look for it in $settings['query']

		'ControllerAction.Model.index.beforePaginate'		=> 'indexBeforePaginate',
		// public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {}

		'ControllerAction.Model.index.afterPaginate'		=> 'indexAfterPaginate',
		// public function indexAfterPaginate(Event $event, $data) {}

		'ControllerAction.Model.index.afterAction'			=> 'indexAfterAction',
		// v4 - public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra) {}
        // v3 - public function indexAfterAction(Event $event, $data) {}

		'ControllerAction.Model.view.beforeAction'			=> 'viewBeforeAction',
		// public function viewBeforeAction(Event $event) {}

		'ControllerAction.Model.view.beforeQuery'			=> 'viewBeforeQuery',
		// public function viewBeforeQuery(Event $event, Query $query) {}

		//'ControllerAction.Model.view.onReload'			=> 'viewOnReload',
		// public function viewOnReload(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {}

		'ControllerAction.Model.view.afterAction'			=> 'viewAfterAction',
		// public function viewAfterAction(Event $event, Entity $entity) {}

		'ControllerAction.Model.add.beforeAction'			=> 'addBeforeAction', // called before any add logic is executed
		// public function addBeforeAction(Event $event) {}

		'ControllerAction.Model.add.onInitialize'			=> 'addOnInitialize', // called on http get
		// public function addOnInitialize(Event $event, Entity $entity) {}

		'ControllerAction.Model.add.beforePatch'			=> 'addBeforePatch', // called on http post before patching entity
		// public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {}

		'ControllerAction.Model.add.afterPatch'				=> 'addAfterPatch', // after patching entity - afterValidate
		// public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {}

		'ControllerAction.Model.add.beforeSave'				=> 'addBeforeSave', // you can overwrite this function to implement your own saving logic
		// public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) { return function() {}; }

		'ControllerAction.Model.add.afterSave'				=> 'addAfterSave', // changes redirect url after a successful save
		// public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {}

		//'ControllerAction.Model.add.onReload'				=> 'addOnReload', // called on http post
		// public function addOnReload(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {}

		'ControllerAction.Model.add.afterAction'			=> 'addAfterAction', // called after adding entity
		// public function addAfterAction(Event $event, Entity $entity) {}

		'ControllerAction.Model.edit.beforeAction'			=> 'editBeforeAction',
		// public function editBeforeAction(Event $event) {}

		'ControllerAction.Model.edit.beforeQuery'			=> 'editBeforeQuery',
		// public function editBeforeQuery(Event $event, Query $query) {}

		'ControllerAction.Model.edit.afterQuery'			=> 'editAfterQuery',
		// public function editAfterQuery(Event $event, Entity $entity) {}

		'ControllerAction.Model.edit.onInitialize'			=> 'editOnInitialize',
		// public function editOnInitialize(Event $event, Entity $entity) {}

		'ControllerAction.Model.edit.beforePatch'			=> 'editBeforePatch',
		// public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {}

		'ControllerAction.Model.edit.afterPatch'			=> 'editAfterPatch',
		// public function editAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {}

		'ControllerAction.Model.edit.beforeSave'			=> 'editBeforeSave', // you can overwrite this function to implement your own saving logic
		// public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data) { return function() {}; }

		'ControllerAction.Model.edit.afterSave'				=> 'editAfterSave', // extra processing after saving
		// public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {}

		//'ControllerAction.Model.edit.onReload'			=> 'editOnReload',
		// public function editOnReload(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {}

		'ControllerAction.Model.edit.afterAction'			=> 'editAfterAction',
		// public function editAfterAction(Event $event, Entity $entity) {}

		'ControllerAction.Model.viewEdit.beforeQuery'		=> 'viewEditBeforeQuery',
		// public function viewEditBeforeQuery(Event $event, Query $query) {}

		'ControllerAction.Model.addEdit.beforeAction'		=> 'addEditBeforeAction',
		// public function addEditBeforeAction(Event $event) {}

		'ControllerAction.Model.addEdit.beforePatch'		=> 'addEditBeforePatch',
		// public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {}

		'ControllerAction.Model.addEdit.afterAction'		=> 'addEditAfterAction',
		// public function addEditAfterAction(Event $event, Entity $entity) {}

		'ControllerAction.Model.delete.beforeAction'		=> 'deleteBeforeAction',
		// public function deleteBeforeAction(Event $event, ArrayObject $settings) {}

		'ControllerAction.Model.delete.onInitialize'		=> 'deleteOnInitialize',
		// public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra) {}

		'ControllerAction.Model.delete.afterAction'			=> 'deleteAfterAction',
		// public function deleteAfterAction(Event $event, Entity $entity, ArrayObject $extra) {}

		'ControllerAction.Model.onGetConvertOptions'=> 'onGetConvertOptions',
		// public function onGetConvertOptions(Event $event, Entity $entity, Query $query) {}

		'ControllerAction.Model.onBeforeDelete'               => 'onBeforeDelete',
        // public function onBeforeDelete(Event $event, ArrayObject $options, $id, ArrayObject $extra) {}

		'ControllerAction.Model.onDeleteTransfer'			=> 'onDeleteTransfer',
		// public function onDeleteTransfer(Event $event, ArrayObject $options, $id) {}

		// CAv4
		'ControllerAction.Controller.beforeQuery'		=> 'beforeQuery',
		// public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra) {}

		'ControllerAction.Model.index.beforeQuery'		=> 'indexBeforeQuery',
		// public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {}

		'ControllerAction.Model.transfer.beforeAction'		=> 'transferBeforeAction',
		// public function transferBeforeAction(Event $event, ArrayObject $extra) {}

		'ControllerAction.Model.transfer.onInitialize'		=> 'transferOnInitialize',
		// public function transferOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra) {}

		'ControllerAction.Model.transfer.afterAction'		=> 'transferAfterAction',
		// public function transferAfterAction(Event $event, Entity $entity, ArrayObject $extra) {}
		// End CAv4
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
