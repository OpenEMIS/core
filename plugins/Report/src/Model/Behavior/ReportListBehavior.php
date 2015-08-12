<?php
namespace Report\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;

class ReportListBehavior extends Behavior {
	public $ReportProgress;

	public function initialize(array $config) {
		$this->ReportProgress = TableRegistry::get('Report.ReportProgress');
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.add.beforeSave'] = 'addBeforeSave';
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		$events['ControllerAction.Model.afterAction'] = 'afterAction';
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		return $events;
	}

	public function afterAction(Event $event, $config) {
		if ($this->_table->action == 'index') {
			return $this->_table->controller->render('Report.index');
		}
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$settings['pagination'] = false;

		$ReportProgress = TableRegistry::get('Report.ReportProgress');
		$fields = $this->_table->ControllerAction->getFields($ReportProgress);

		$fields['current_records']['visible'] = false;
		$fields['total_records']['visible'] = false;
		$fields['error_message']['visible'] = false;
		$fields['file_path']['visible'] = false;
		$fields['module']['visible'] = false;
		$fields['params']['visible'] = false;
		$fields['pid']['visible'] = false;
		$fields['created']['visible'] = true;
		$fields['modified']['visible'] = true;

		$this->_table->fields = $fields;

		$this->_table->ControllerAction->setFieldOrder(['name', 'created', 'modified', 'expiry_date', 'status']);
		
		$query = $ReportProgress->find()
		->where([$ReportProgress->aliasField('module') => $this->_table->alias()])
		->order([$ReportProgress->aliasField('expiry_date') => 'DESC']);

		return $query;
	}

	// public function addBeforeAction(Event $event) {
		
	// }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		// if ($action == 'index') {
		// 	$toolbarButtons['add']['url'] = ['plugin' => 'Report', 'controller' => 'Reports', 'action' => $this->_table->alias(), 'export'];
		// 	$toolbarButtons['add']['type'] = 'button';
		// 	$toolbarButtons['add']['label'] = '<i class="fa kd-add"></i>';
		// 	$toolbarButtons['add']['attr'] = $attr;
		// 	$toolbarButtons['add']['attr']['title'] = __('Add Report');
		// } else {
		// 	$toolbarButtons['back'] = $buttons['back'];
		// 	$toolbarButtons['back']['type'] = 'button';
		// 	$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
		// 	$toolbarButtons['back']['attr'] = $attr;
		// 	$toolbarButtons['back']['attr']['title'] = __('Back');
		// }
	}

	public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = ['xlsx' => 'Excel'];
		return $attr;
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$process = function($model, $entity) use ($data) {
			$this->_generate($data);
			return true;
		};
		return $process;
	}

	// public function export() {
	// 	$entity = $this->_table->newEntity();
	// 	$request = $this->_table->request;
	// 	$table = $this->_table;

	// 	if ($request->is(['post', 'put'])) {
	// 		$submit = isset($request->data['submit']) ? $request->data['submit'] : 'generate';
	// 		$requestData = new ArrayObject($request->data);

	// 		if ($submit == 'generate') {
	// 			$this->_generate($requestData);
	// 			$action = ['plugin' => 'Report', 'controller' => 'Reports', 'action' => $table->alias(), 'index'];
	// 			return $table->controller->redirect($action);
	// 		} else {
	// 			// Start Event
	// 			$methodKey = 'on' . ucfirst($submit);
	// 			$eventKey = 'Model.Report.' . $methodKey;
	// 			$event = new Event($eventKey, $this, [$entity, $requestData]);
	// 			$event = $table->eventManager()->dispatch($event);
	// 			// End Event
	// 		}
	// 	}

	// 	$table->controller->set('data', $entity);
	// 	$table->controller->render('Reports/export');
	// }

	public function onExcelStartSheet(Event $event, ArrayObject $settings, $totalCount) {
		$process = $settings['process'];
		$this->ReportProgress->updateAll(
			['total_records' => $totalCount],
			['id' => $process->id]
		);
	}

	public function onExcelEndSheet(Event $event, ArrayObject $settings, $totalProcessed) {
		$process = $settings['process'];
		$this->ReportProgress->updateAll(
			['current_records' => $totalProcessed],
			['id' => $process->id]
		);
	}

	protected function _generate($data) {
		$alias = $this->_table->alias();
		$featureList = $this->_table->fields['feature']['options'];
		$feature = $data[$alias]['feature'];
		$table = TableRegistry::get($feature);

		// Event: 
		// $eventKey = 'Model.Report.onGetName';
		// $event = new Event($eventKey, $this, [$data]);
		// $event = $table->eventManager()->dispatch($event);
		// $name = $event->result;
		// End Event

		$name = $featureList[$feature];
		$params = $data[$alias];

		$ReportProgress = TableRegistry::get('Report.ReportProgress');
		$obj = ['name' => $name, 'module' => $alias, 'params' => $params];

		$id = $ReportProgress->addReport($obj);
		if ($id !== false) {
			$ReportProgress->generate($id);
		}
	}
}
