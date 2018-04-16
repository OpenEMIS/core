<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class AdvancedPositionSearchBehavior extends Behavior {

	public function onBuildQuery(Event $event, Query $query, $advancedSearchHasMany) {
		if (isset($advancedSearchHasMany['position'])) {
			$search = $advancedSearchHasMany['position'];
		} else {
			$search = '';
		}

		$alias = $this->_table->alias();

		if (!empty($search)) {
			$searchString = '%' . $search . '%';
			$query->join([
					[
						'type' => 'INNER',
						'table' => 'institution_staff',
						'alias' => 'InstitutionStaff',
						'conditions' => [
							'InstitutionStaff.staff_id = '. $alias . '.id'
						]
					]
				])->join([
					[
						'type' => 'INNER',
						'table' => 'institution_positions',
						'alias' => 'InstitutionPositions',
						'conditions' => [
							'InstitutionPositions.id = InstitutionStaff.institution_position_id'
						]
					]
				])->join([
					[
						'type' => 'LEFT',
						'table' => 'staff_position_titles',
						'alias' => 'Positions',
						'conditions' => [
							'Positions.id = InstitutionPositions.staff_position_title_id'
						]
					]
				]);
			$query->andWhere(['Positions.name LIKE' =>  $searchString]);
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
		$turnOn = false;
		$userType = $this->_table->request->query('user_type');
		if (!is_null($userType)) {
			$tableClass = get_class($this->_table);
			switch($userType) {
				case $tableClass::ALL:
				case $tableClass::STAFF:
					$turnOn = true;
					break;
			}
		}
		if ($turnOn) {
			$searchables['position'] = [
				'label' => __('Position Title'),
				'value' => isset($advanceSearchModelData['hasMany']['position']) ? $advanceSearchModelData['hasMany']['position'] : '',
			];
			// $this->_table->ControllerAction->field('positions', ['order' => 53]);
		}
	}

	public function onGetPositions(Event $event, Entity $entity) {
		$userId = $entity->id;
		$Positions = TableRegistry::get('Staff.Positions');
		$staffPositions = $Positions->find()
			->contain(['InstitutionPositions'])
			->where([
				$Positions->aliasField('staff_id') => $userId
			])
			->toArray();
		if (!empty($staffPositions)) {
			foreach ($staffPositions as $key => $value) {
				$value = $value->institution_position->name.'<br/>';
			}
		} else {
			$value = '';
		}
		return $value;
	}

}
