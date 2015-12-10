<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\ResultSet;

class ReorderBehavior extends Behavior {
	protected $_defaultConfig = [
		'orderField' => 'order',
		'filter' => null
	];

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.index.afterPaginate' => ['callable' => 'indexAfterPaginate', 'priority' => 100],
			'ControllerAction.Model.reorder.updateOrderValue' => ['callable' => 'reorderUpdateOrderValue', 'priority' => 110],
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function indexAfterPaginate(Event $event, ResultSet $data) {
		$results = $data->toArray();
		$firstOrder = 0;
		if (!empty($results)) {
			$firstOrder = $results[0]['order'] - 1;
		}
		$count = $firstOrder;
		$this->_table->Session->write($this->_table->registryAlias().'.orderCount', $count);
	}

	public function reorderUpdateOrderValue(Event $event, $orderValue) {
		$count = $this->_table->Session->read($this->_table->registryAlias().'.orderCount');
		return ($orderValue+$count);
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->isNew()) {
			$orderField = $this->config('orderField');
			$filter = $this->config('filter');

			$order = 0;

			if (is_null($filter)) {
				$order = $this->_table->find()->count();
			} else {
				$filterValue = $entity->$filter;
				$table = $this->_table;
				$order = $table
					->find()
					->where([$table->aliasField($filter) => $filterValue])
					->count();
			}
			$entity->$orderField = $order + 1;
		}
	}
}
