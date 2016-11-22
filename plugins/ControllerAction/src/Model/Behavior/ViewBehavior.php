<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class ViewBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.view'] = 'view';
		return $events;
	}

	public function view(Event $mainEvent, ArrayObject $extra) {
		$model = $this->_table;

		$event = $model->dispatchEvent('ControllerAction.Model.view.beforeAction', [$extra], $this);
		if ($event->isStopped()) {
			$mainEvent->stopPropagation();
			return $event->result;
		}
		if ($event->result instanceof Table) {
			$model = $event->result;
		}

		$primaryKey = $model->getPrimaryKey();
		$idKey = $model->aliasField($primaryKey);
		$sessionKey = $model->registryAlias() . '.' . $primaryKey;
		$contain = [];

		foreach ($model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
				$contain[] = $assoc->name();
			}
		}

		$id = $model->paramsPass(0);
		if (empty($id)) {
			if ($model->Session->check($sessionKey)) {
				$id = $model->Session->read($sessionKey);
			} else if (!empty($model->ControllerAction->getQueryString('id'))) {
				$id = $model->ControllerAction->getQueryString('id');
			}
		}

		$entity = false;
		if ($model->exists([$idKey => $id])) {
			$query = $model->find()->where([$idKey => $id])->contain($contain);

			$event = $model->dispatchEvent('ControllerAction.Controller.beforeQuery', [$model, $query, $extra], $this);
			$event = $model->dispatchEvent('ControllerAction.Model.viewEdit.beforeQuery', [$query, $extra], $this);
			$event = $model->dispatchEvent('ControllerAction.Model.view.beforeQuery', [$query, $extra], $this);

			$entity = $query->first();
		}

		$event = $model->dispatchEvent('ControllerAction.Model.view.afterAction', [$entity, $extra], $this);
		if ($event->isStopped()) {
			$mainEvent->stopPropagation();
			return $event->result;
		}

		if (!empty($entity)) {
			$model->Session->write($sessionKey, $id);
			$model->controller->set('data', $entity);
		} else {
			$mainEvent->stopPropagation();
			return $model->controller->redirect($model->url('index', 'QUERY'));
		}
		return $entity;
	}
}
