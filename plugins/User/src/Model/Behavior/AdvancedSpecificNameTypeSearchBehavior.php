<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\Association\BelongsTo;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Datasource\Exception\MissingModelException;

class AdvancedSpecificNameTypeSearchBehavior extends Behavior {
	protected $_defaultConfig = [
		'modelToSearch' => '',
	];
	private $_keys = ['first_name', 'middle_name', 'third_name', 'last_name'];

	public function initialize(array $config) {
		$model = $this->config('modelToSearch');
		if (empty($model)) {
			$this->config('modelToSearch', $this->_table);
		} else {
			if (! $model instanceof Table && ! $model instanceof BelongsTo) {
				throw new MissingModelException('AdvancedSpecificNameTypeSearchBehavior requires a registered model for "modelToSearch" parameter. <br/>"'.$model.'" defined as "modelToSearch" is not a model object.');
			}
		}
		$this->_table->addBehavior('Area.Area');
	}
	
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'AdvanceSearch.onSetupFormField' => 'onSetupFormField',
			'AdvanceSearch.onBuildQuery' => 'onBuildQuery',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onBuildQuery(Event $event, Query $query, $advancedSearchHasMany) {
		$searches = [];
		foreach ($this->_keys as $key) {
			if (isset($advancedSearchHasMany[$key])) {
				$searches[$key] = $advancedSearchHasMany[$key];
			}
		}

		$model = $this->config('modelToSearch');

		if (!empty($searches)) {
			$conditions = [];
			foreach ($searches as $searchKey => $searchValue) {
				if (!empty($searchValue)) {
					$conditions[] = [$model->aliasField($searchKey).' LIKE' => $searchValue . '%'];
				}
			}
			$query->andWhere( $conditions );
		}
		return $query;
	}

	public function onSetupFormField(Event $event, ArrayObject $searchables, $advanceSearchModelData) {
		foreach ($this->_keys as $key) {
			$label = Inflector::humanize($key);
			$searchables[$key] = [
				'label' => __($label),
				'value' => (isset($advanceSearchModelData['hasMany']) && isset($advanceSearchModelData['hasMany'][$key])) ? $advanceSearchModelData['hasMany'][$key] : '',
			];
		}
	}

}
