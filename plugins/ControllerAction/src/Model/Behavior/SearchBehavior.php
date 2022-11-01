<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class SearchBehavior extends Behavior {
	protected $_defaultConfig = [
		'orderField' => 'order'
	];

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.index.beforeAction'] = ['callable' => 'indexBeforeAction', 'priority' => 5];
		$events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 11];
		$events['ControllerAction.Model.getSearchableFields'] = ['callable' => 'getSearchableFields', 'priority' => 5];
		return $events;
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$model = $this->_table;
		$alias = $model->registryAlias();
		$controller = $model->controller;
		$request = $model->request;
		$session = $request->session();
		$pageOptions = $extra['config']['pageOptions'];

		$search = $session->check($alias.'.search.key') ? $session->read($alias.'.search.key') : '';

		if ($request->is(['post', 'put'])) {
			if (isset($request->data['Search'])) {
				if (array_key_exists('searchField', $request->data['Search'])) {
					$search = trim($request->data['Search']['searchField']);
				}
			}
		}

		$session->write($alias.'.search.key', $search);
		$request->data['Search']['searchField'] = $search;

		$extra['config']['search'] = $search;
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$model = $this->_table;
		$search = $extra['config']['search'];

		$schema = $model->schema();
		$columns = $schema->columns();
		$excludeFields = ['id', 'password'];
		if ($extra['auto_search']) {
			$OR = [];
			if (!empty($search)) {
				foreach ($columns as $col) {
					$attr = $schema->column($col);
					if (in_array($col, $excludeFields)) continue;
					if (in_array($attr['type'], ['string', 'text'])) {
						$OR[$model->aliasField($col).' LIKE'] = '%' . $search . '%';
					}
				}
			}

			if (array_key_exists('OR', $extra)) {
				$OR = array_merge($OR, $extra['OR']);
			}

			if (!empty($OR)) {
				$query->where(['OR' => $OR]);
			}
		}

		if ($extra['auto_order']) {
			if (in_array($this->config('orderField'), $columns)) {
				$extra['options']['sort'] = 'order';
                $extra['options']['direction'] = 'asc';
			}
		}
	}

	//called by ControllerActionHelper
	public function getSearchableFields(Event $event, ArrayObject $searchableFields) {
		$model = $this->_table;
		$schema = $model->schema();
		$columns = $schema->columns();
		$ControllerActionHelper = $event->subject();
		$fields = $model->fields;

		foreach ($columns as $col) {
			$attr = $schema->column($col);
			if ($col == 'password') continue;
			if (in_array($attr['type'], ['string', 'text'])) {
				$visible = $ControllerActionHelper->isFieldVisible($fields[$col], 'index');
				if ($visible) {
					$searchableFields[] = $col;
				}
			}
		}
	}
}
