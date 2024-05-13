<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

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
		//echo "<pre>"; print_r($model['registryAlias']);die;
		for ($i = 0; $i <= strlen($search); $i++) {

	        // Construct the modified search string by inserting the special character "ʻ" at the current position
	        $modifiedSearchString = substr($search, 0, $i) . 'ʻ' . substr($search, $i);
	        // Perform a query using the modified search string
	        $table = TableRegistry::get('Institution.Institutions');
	        $result = $table->find()
				    	->andWhere([
				        'OR' => [
				            'Institutions.name LIKE' => "%$modifiedSearchString%",
				            'Institutions.code LIKE' => "%$modifiedSearchString%"
				        ]
				    ])->toArray();
    
		    if (!empty($result)) {
		           $newSearch = $modifiedSearchString;
		            break;
		    }
	}
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
						$OR[$model->aliasField('name').' LIKE'] = '%' . $newSearch . '%';
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
