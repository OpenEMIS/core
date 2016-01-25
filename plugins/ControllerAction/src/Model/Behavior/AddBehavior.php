<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Log\Log;

class AddBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.add'] = 'add';
		return $events;
	}

	public function add(Event $mainEvent, ArrayObject $extra) {
		$model = $this->_table;
		$request = $this->_table->request;
		$extra['config']['form'] = true;

		$event = $model->dispatchEvent('ControllerAction.Model.addEdit.beforeAction', [$extra], $this);
		if ($event->isStopped()) { return $event->result; }
		if ($event->result instanceof Table) {
			$model = $event->result;
		}

		$event = $model->dispatchEvent('ControllerAction.Model.add.beforeAction', [$extra], $this);
		if ($event->isStopped()) { return $event->result; }
		if ($event->result instanceof Table) {
			$model = $event->result;
		}

		$entity = $model->newEntity();

		if ($request->is(['get'])) {
			$event = $model->dispatchEvent('ControllerAction.Model.add.onInitialize', [$entity, $extra], $this);
			if ($event->isStopped()) { return $event->result; }
		} else if ($request->is(['post', 'put'])) {
			$submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
			$patchOptions = new ArrayObject([]);
			$requestData = new ArrayObject($request->data);

			$params = [$entity, $requestData, $patchOptions, $extra];

			if ($submit == 'save') {
				$event = $model->dispatchEvent('ControllerAction.Model.addEdit.beforePatch', $params, $this);
				if ($event->isStopped()) { return $event->result; }
				
				$event = $model->dispatchEvent('ControllerAction.Model.add.beforePatch', $params, $this);
				if ($event->isStopped()) { return $event->result; }

				$patchOptionsArray = $patchOptions->getArrayCopy();
				$request->data = $requestData->getArrayCopy();
				$entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);

				$event = $model->dispatchEvent('ControllerAction.Model.add.afterPatch', $params, $this);
				if ($event->isStopped()) { return $event->result; }

				$request->data = $requestData->getArrayCopy();

				$process = function ($model, $entity) {
					return $model->save($entity);
				};

				$event = $model->dispatchEvent('ControllerAction.Model.add.beforeSave', [$entity, $requestData, $extra], $this);
				if ($event->isStopped()) { return $event->result; }
				if (is_callable($event->result)) {
					$process = $event->result;
				}
				
				$result = $process($model, $entity);

				if (!$result) {
					Log::write('debug', $entity->errors());
				}

				$event = $model->dispatchEvent('ControllerAction.Model.add.afterSave', [$entity, $requestData, $extra], $this);
				if ($event->isStopped()) { return $event->result; }

				if ($result) {
					$mainEvent->stopPropagation();
					return $model->controller->redirect($model->url('index', 'QUERY'));
				}
			} else {
				$patchOptions['validate'] = false;
				$methodKey = 'on' . ucfirst($submit);

				// $eventKey = 'ControllerAction.Model.addEdit.' . $methodKey;
				// $this->debug(__METHOD__, ': Event -> ' . $eventKey);
				// $method = 'addEdit' . ucfirst($methodKey);
				// $event = $this->dispatchEvent($this->model, $eventKey, $method, $params);
				// if ($event->isStopped()) { return $event->result; }
				
				// $eventKey = 'ControllerAction.Model.add.' . $methodKey;
				// $this->debug(__METHOD__, ': Event -> ' . $eventKey);
				// $method = 'add' . ucfirst($methodKey);
				// $event = $this->dispatchEvent($this->model, $eventKey, $method, $params);
				// if ($event->isStopped()) { return $event->result; }
				
				$patchOptionsArray = $patchOptions->getArrayCopy();
				$request->data = $requestData->getArrayCopy();
				$entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);
			}
		}

		$event = $model->dispatchEvent('ControllerAction.Model.addEdit.afterAction', [$entity, $extra], $this);
		if ($event->isStopped()) { return $event->result; }
		
		$event = $model->dispatchEvent('ControllerAction.Model.add.afterAction', [$entity, $extra], $this);
		if ($event->isStopped()) { return $event->result; }
		
		$model->controller->set('data', $entity);
		return $entity;
	}
}
