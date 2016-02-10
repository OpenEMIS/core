<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Datasource\Exception\RecordNotFoundException;

class RemoveBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.remove'] = 'remove';
		$events['ControllerAction.Model.transfer'] = 'transfer';
		$events['ControllerAction.Model.transfer.afterAction'] = ['callable' => 'transferAfterAction', 'priority' => 5];
		return $events;
	}

	public function transferAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$model = $this->_table;
		$request = $model->request;
		if ($model->actions('remove') == 'transfer' && $request->is('delete') && $extra['result'] == true) {
			$convertFrom = $entity->id;
			$convertTo = $entity->convert_to;

			foreach ($model->associations() as $assoc) {
				if (!$assoc->dependent()) {
					if ($assoc->type() == 'oneToMany') {
						$this->updateHasManyAssociations($assoc, $convertFrom, $convertTo);
					} else if ($assoc->type() == 'manyToMany') {
						$this->updateBelongsToManyAssociations($assoc, $convertFrom, $convertTo);
					}
				}
			}
		}
	}

	public function remove(Event $mainEvent, ArrayObject $extra) {
		$model = $this->_table;
		$request = $model->request;
		$extra['options'] = [];

		$event = $model->dispatchEvent('ControllerAction.Model.delete.beforeAction', [$extra], $this);
		if ($event->isStopped()) { return $event->result; }

		$primaryKey = $model->primaryKey();
		$result = true;
		$entity = null;

		if ($request->is('delete') && !empty($request->data[$primaryKey])) {
			$id = $request->data[$primaryKey];
			try {
				$entity = $model->get($id);
			} catch (RecordNotFoundException $exception) { // to handle concurrent deletes
				$mainEvent->stopPropagation();
				return $model->controller->redirect($model->url('index', 'QUERY'));
			}
			$result = $this->doDelete($entity, $extra);
		}
		$extra['result'] = $result;

		$event = $model->dispatchEvent('ControllerAction.Model.delete.afterAction', [$entity, $extra], $this);
		if ($event->isStopped()) { return $event->result; }

		$mainEvent->stopPropagation();
		return $model->controller->redirect($model->url('index', 'QUERY'));
	}

	public function transfer(Event $mainEvent, ArrayObject $extra) {
		$model = $this->_table;
		$controller = $model->controller;
		$request = $model->request;
		$extra['config']['form'] = ['type' => 'DELETE'];
		$extra['options'] = [
			'keyField' => 'id',
			'valueField' => 'name'
		];

		$event = $model->dispatchEvent('ControllerAction.Model.transfer.beforeAction', [$extra], $this);
		if ($event->isStopped()) { return $event->result; }
		
		$primaryKey = $model->primaryKey();
		$idKey = $model->aliasField($primaryKey);

		$result = true;
		$entity = $model->newEntity();

		if ($request->is('get')) {
			$id = $model->paramsPass(0);
			if ($model->exists([$idKey => $id])) {
				$entity = $model->get($id);

				$query = $model->find();
				$event = $model->dispatchEvent('ControllerAction.Model.transfer.onInitialize', [$entity, $query, $extra], $this);
				if ($event->isStopped()) { return $event->result; }

				$convertOptions = $query->find('list', $extra['options'])
								->where([$idKey . ' <> ' => $id])
								->toArray();

				if (empty($convertOptions)) {
					$convertOptions[''] = __('No Available Options');
				}

				$associations = $this->getAssociatedRecords($model, $entity);
				$cells = [];
				foreach ($associations as $row) {
					$cells[] = [$row['model'], $row['count']];
				}
				$model->fields = [];
				$model->field('id', ['type' => 'hidden']);
				$model->field('convert_from', ['type' => 'readonly', 'attr' => ['value' => $entity->name]]);
				$model->field('convert_to', ['type' => 'select', 'options' => $convertOptions, 'attr' => ['required' => 'required']]);
				$model->field('apply_to', [
					'type' => 'table',
					'headers' => [__('Feature'), __('No of Records')],
					'cells' => $cells
				]);

				$controller->set('data', $entity);
			}

			$event = $model->dispatchEvent('ControllerAction.Model.transfer.afterAction', [$entity, $extra], $this);
			if ($event->isStopped()) { return $event->result; }

			if (empty($entity) || empty($entity->id)) {
				$mainEvent->stopPropagation();
				return $model->controller->redirect($model->url('index', 'QUERY'));
			}
			return $entity;
		} else if ($request->is('delete')) {
			$id = $request->data($model->aliasField($primaryKey));
			if (!empty($id)) {
				try {
					$entity = $model->get($id);
				} catch (RecordNotFoundException $exception) { // to handle concurrent deletes
					$mainEvent->stopPropagation();
					return $model->controller->redirect($model->url('index', 'QUERY'));
				}
				
				$convertTo = $request->data($model->aliasField('convert_to'));
				$entity->convert_to = $convertTo;
				$doDelete = true;

				if (empty($convertTo)) {
					if ($this->hasAssociatedRecords($model, $entity)) {
						$doDelete = false;
					}
				}
				
				$result = false;
				if ($doDelete) {
					$result = $this->doDelete($entity, $extra);
				}
				$extra['result'] = $result;
				
				$event = $model->dispatchEvent('ControllerAction.Model.transfer.afterAction', [$entity, $extra], $this);
				if ($event->isStopped()) { return $event->result; }

				$mainEvent->stopPropagation();
				return $model->controller->redirect($model->url('index', 'QUERY'));
			}
		}
	}

	private function doDelete($entity, ArrayObject $extra) {
		$model = $this->_table;
		$process = function ($model, $entity, $options) {
			return $model->delete($entity, $options);
		};

		$event = $model->dispatchEvent('ControllerAction.Model.onBeforeDelete', [$entity, $extra], $this);
		if ($event->isStopped()) { return $event->result; }
		if (is_callable($event->result)) {
			$process = $event->result;
		}

		$options = $extra['options'];
		$result = $process($model, $entity, $options);
		
		return $result;
	}

	private function getAssociatedRecords($model, $entity) {
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

	private function hasAssociatedRecords($model, $entity) {
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

	private function updateHasManyAssociations($association, $from, $to) {
		$association->updateAll(
			[$association->foreignKey() => $to],
			[$association->foreignKey() => $from]
		);
	}

	private function updateBelongsToManyAssociations($association, $from, $to) {
		$modelAssociationTable = $association->junction();

		$foreignKey = $association->foreignKey();
		$targetForeignKey = $association->targetForeignKey();

		// List of the target foreign keys for subqueries
		$targetForeignKeys = $modelAssociationTable->find()
			->select([$modelAssociationTable->aliasField($targetForeignKey)])
			->where([$modelAssociationTable->aliasField($foreignKey) => $to]);

		// List of id in the junction table to be deleted
		$idNotToUpdate = $modelAssociationTable->find('list',[
				'keyField' => 'id',
				'valueField' => 'id'
			])
			->where([
				$modelAssociationTable->aliasField($foreignKey) => $from,
				$modelAssociationTable->aliasField($targetForeignKey).' IN' => $targetForeignKeys
			])
			->toArray();

		$condition = [];

		if (empty($idNotToUpdate)) {
			$condition = [$foreignKey => $from];
		} else {
			$condition = [$foreignKey => $from, 'id NOT IN' => $idNotToUpdate];
		}
		
		// Update all transfer records
		$modelAssociationTable->updateAll(
			[$foreignKey => $to],
			$condition
		);
	}
}
