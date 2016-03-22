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

	public function reorder(Event $event, ArrayObject $extra) {
		$model = $this->_table;
		$controller = $model->controller;
		$request = $model->request;

		$controller->autoRender = false;

		if ($request->is('ajax')) {
			$primaryKey = $model->primaryKey();
			$orderField = $this->config('orderField');
			
			$ids = json_decode($request->data("ids"));

			$originalOrder = $model->find('list')
				->where([$model->aliasField($primaryKey).' IN ' => $ids])
				->select(['id' => $model->aliasField($primaryKey), 'name' => $model->aliasField($orderField)])
				->order([$model->aliasField($orderField)])
				->toArray();

			$originalOrder = array_reverse($originalOrder);

			foreach ($ids as $order => $id) {
				$orderValue = array_pop($originalOrder);
				$model->updateAll([$orderField => $orderValue], [$primaryKey => $id]);
			}
		}
		$event->stopPropagation();
		return true;
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->isNew()) {
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
					$filterValue = $entity->$filter;
				}
				$table = $this->_table;
				$order = $table
					->find()
					->where([$table->aliasField($filter).' IN ' => $filterValue])
					->count();
			}
			$entity->$orderField = $order + 1;
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
				$filterValue = $entity->$filter;
			}
			$reorderItems = $table
				->find('list')
				->where([$table->aliasField($filter).' IN ' => $filterValue])
				->order([$table->aliasField($orderField)])
				->toArray();
		}
		$counter = 1;
		foreach ($reorderItems as $key => $item) {
			$table->updateAll([$orderField => $counter++], [$table->primaryKey() => $key]);
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$orderField = $this->config('orderField');
		$filter = $this->config('filter');
		$filterValues = $this->config('filterValues');
		$this->updateOrder($entity, $orderField, $filter, $filterValues);
	}


	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$orderField = $this->config('orderField');
		$filter = $this->config('filter');
		$filterValues = $this->config('filterValues');
		$this->updateOrder($entity, $orderField, $filter, $filterValues);
	}
}
