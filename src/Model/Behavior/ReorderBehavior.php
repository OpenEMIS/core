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
