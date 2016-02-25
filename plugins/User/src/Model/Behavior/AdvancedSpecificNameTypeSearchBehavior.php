<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;

class AdvancedSpecificNameTypeSearchBehavior extends Behavior {

	public function onBuildQuery(Event $event, Query $query, $advancedSearchHasMany) {
		$searches = [];
		if (isset($advancedSearchHasMany['first_name'])) {
			$searches['first_name'] = $advancedSearchHasMany['first_name'];
		}
		if (isset($advancedSearchHasMany['middle_name'])) {
			$searches['middle_name'] = $advancedSearchHasMany['middle_name'];
		}
		if (isset($advancedSearchHasMany['third_name'])) {
			$searches['third_name'] = $advancedSearchHasMany['third_name'];
		}
		if (isset($advancedSearchHasMany['last_name'])) {
			$searches['last_name'] = $advancedSearchHasMany['last_name'];
		}

		$model = $this->_table;

		if (!empty($searches)) {
			$conditions = [];
			foreach ($searches as $searchKey => $searchValue) {
				if (!empty($searchValue)) {
					$conditions[] = [$model->aliasField($searchKey).' LIKE' =>  '%' . $searchValue . '%'];
				}
			}
			$query->andWhere( $conditions );
		}
		return $query;
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

	public function onSetupFormField(Event $event, ArrayObject $searchables, $advanceSearchModelData) {
		$searchables['first_name'] = [
			'label' => __('First Name'),
			'value' => isset($advanceSearchModelData['hasMany']) ? $advanceSearchModelData['hasMany']['first_name'] : '',
		];
		$searchables['middle_name'] = [
			'label' => __('Middle Name'),
			'value' => isset($advanceSearchModelData['hasMany']) ? $advanceSearchModelData['hasMany']['middle_name'] : '',
		];
		$searchables['third_name'] = [
			'label' => __('Third Name'),
			'value' => isset($advanceSearchModelData['hasMany']) ? $advanceSearchModelData['hasMany']['third_name'] : '',
		];
		$searchables['last_name'] = [
			'label' => __('Last Name'),
			'value' => isset($advanceSearchModelData['hasMany']) ? $advanceSearchModelData['hasMany']['last_name'] : '',
		];
	}

}
