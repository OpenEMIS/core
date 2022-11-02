<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Log\Log;

class ReorderBehavior extends Behavior {
	protected $_defaultConfig = [
		'orderField' => 'order',
		'filter' => null,
		'filterValues' => null
	];

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.reorder'] = 'reorder';
		return $events;
	}

	public function reorder(Event $mainEvent, ArrayObject $extra) {
		$model = $this->_table;
		$controller = $model->controller;
		$request = $model->request;

		$controller->autoRender = false;

		if ($request->is('ajax')) {
			$primaryKey = $model->primaryKey();
			$orderField = $this->config('orderField');

			$encodedIds = json_decode($request->data("ids"));

			$ids = [];
			$idKeys = [];

			foreach ($encodedIds as $id) {
				$ids[] = $model->paramsDecode($id);
				$idKeys[] = $model->getIdKeys($model, $model->paramsDecode($id));
			}

			if (!empty($ids)) {
				$init = 1;
				$originalOrder = $model
					->find()
					->select($primaryKey)
					->select($orderField)
					->where(['OR' => $idKeys])
					->order([$model->aliasField($orderField)])
					->hydrate(false)
					->toArray();

				$originalOrder = array_reverse($originalOrder);
				foreach ($ids as $id) {
					$orderValue = array_pop($originalOrder);
					/** POCOR-6677 starts - storing order as per reorder numbering to overcome duplication of order no*/
					if ($model->alias() == 'SecurityRoles') {
						$model->updateAll([$orderField => $init], [$id]);
						$init++; 
					} else {
						$model->updateAll([$orderField => $orderValue[$orderField]], [$id]);
					}
					/** POCOR-6677 ends*/ 
				}

				$event = $model->dispatchEvent('ControllerAction.Model.afterReorder', [$ids], $model);
				if ($event->isStopped()) { return $event->result; }
			}
		}
		$mainEvent->stopPropagation();
		return true;
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		/** POCOR-6677 starts- added AND condition to not do anything when model is SecurityRoles*/
		$model = $this->_table;
		if ($entity->isNew() && $model->alias() != 'SecurityRoles') {
			$orderField = $this->config('orderField');
			$filter = $this->config('filter');
			$filterValues = $this->config('filterValues');
			$order = 0;

			if (is_null($filter)) {
				$order = $this->_table->find()->count();
			} else {
				if (!is_null($filterValues)) {
					$filterValue = null;
					if (is_array($filterValues)) {
						$filterValue = $filterValues;
					}
				} else {
					$filterValue = $entity->{$filter};
				}
				$table = $this->_table;

				if (!is_null($filterValue)) {
					$condition = [$table->aliasField($filter).' IN ' => $filterValue];
				} else {
					// this logic will handle tree behavior, which parent_id is null.
					$condition = [$table->aliasField($filter . ' IS NULL')];
				}

				$order = $table
					->find()
					->where($condition)
					->count();
			}
			$entity->{$orderField} = $order + 1;
		}
	}

	private function updateOrder($entity, $orderField, $filter = null, $filterValues = null) {
		$table = $this->_table;
		if (is_null($filter)) {
			$reorderItems = $table->find('list')
			->order([$table->aliasField($orderField)])
			->toArray();
		} else {
			if (!is_null($filterValues)) {
				$filterValue = null;
				if (is_array($filterValues)) {
					$filterValue = $filterValues;
				}
			} else {
				$filterValue = $entity->{$filter};
			}

			if (!is_null($filterValue)) {
				$condition = [$table->aliasField($filter).' IN ' => $filterValue];
			} else {
				// this logic will handle tree behavior, which parent_id is null.
				$condition = [$table->aliasField($filter . ' IS NULL')];
			}
			$reorderItems = $table
				->find('list')
				->where($condition)
				->order([$table->aliasField($orderField)])
				->toArray();
		}
		$counter = 1;
		foreach ($reorderItems as $key => $item) {
			$table->updateAll([$orderField => $counter++], [$table->primaryKey() => $key]);
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		/** POCOR-6677 starts- added AND condition to not do anything when model is SecurityRoles*/
		$model = $this->_table;
		if ($model->alias() != 'SecurityRoles') {
			$orderField = $this->config('orderField');
			$filter = $this->config('filter');
			$filterValues = $this->config('filterValues');
			$this->updateOrder($entity, $orderField, $filter, $filterValues);
		}
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$orderField = $this->config('orderField');
		$filter = $this->config('filter');
		$filterValues = $this->config('filterValues');
		$this->updateOrder($entity, $orderField, $filter, $filterValues);
	}
}
