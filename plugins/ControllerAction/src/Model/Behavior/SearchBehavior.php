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
		$events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 5];
		$events['ControllerAction.Model.onGetFormButtons'] = ['callable' => 'onGetFormButtons', 'priority' => 5];
		return $events;
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->_table->action == 'index') {
			$buttons->exchangeArray([]);
		}
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$model = $this->_table;
		$alias = $model->registryAlias();
		$controller = $model->controller;
		$request = $model->request;
		$session = $request->session();
		$pageOptions = $extra['config']['pageOptions'];

		$limit = $session->check($alias.'.search.limit') ? $session->read($alias.'.search.limit') : key($pageOptions);
		$search = $session->check($alias.'.search.key') ? $session->read($alias.'.search.key') : '';

		if ($request->is(['post', 'put'])) {
			if (isset($request->data['Search'])) {
				if (array_key_exists('searchField', $request->data['Search'])) {
					$search = trim($request->data['Search']['searchField']);
				}

				if (array_key_exists('limit', $request->data['Search'])) {
					$limit = $request->data['Search']['limit'];
					$session->write($alias.'.search.limit', $limit);
				}
			}
		}

		$session->write($alias.'.search.key', $search);
		$request->data['Search']['searchField'] = $search;
		$request->data['Search']['limit'] = $limit;

		$extra['config']['search'] = $search;

		if ($extra['pagination']) {
			$extra['options']['limit'] = $pageOptions[$limit];
		}

		if ($extra['auto_contain']) {
			$contain = $model->getContains();
			if (!empty($contain)) {
				$query->contain($contain);
			}
		}

		$schema = $model->schema();
		$columns = $schema->columns();
		if ($extra['auto_search']) {
			$OR = [];
			if (!empty($search)) {
				$schema = $model->schema();
				$columns = $schema->columns();
				foreach ($columns as $col) {
					$attr = $schema->column($col);
					if ($col == 'password') continue;
					if (in_array($attr['type'], ['string', 'text'])) {
						$OR[$model->aliasField($col).' LIKE'] = '%' . $search . '%';
					}
				}
			}

			if (!empty($OR)) {
				$query->where(['OR' => $OR]);
			}
		}

		if ($extra['auto_order']) {
			if (in_array($this->config('orderField'), $columns)) {
				$query->order([$model->aliasField($this->config('orderField')) => 'asc']);
			}
		}
	}
}
