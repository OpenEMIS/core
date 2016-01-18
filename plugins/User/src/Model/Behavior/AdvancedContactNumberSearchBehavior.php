<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class AdvancedContactNumberSearchBehavior extends Behavior {

	public function onBuildQuery(Event $event, Query $query, $advancedSearchHasMany) {
		if (isset($advancedSearchHasMany['contact_number'])) {
			$search = $advancedSearchHasMany['contact_number'];
		} else {
			$search = '';
		}

		$alias = $this->_table->alias();

		if (!empty($search)) {
			$searchString = '%' . $search . '%';
			$query->join([
					[
						'type' => 'LEFT',
						'table' => 'user_contacts',
						'alias' => 'Contacts',
						'conditions' => [
							'Contacts.security_user_id = '. $alias . '.id'
						]
					]
				]);
			$query->orWhere(['Contacts.value LIKE' =>  $searchString]);
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
		$searchables['contact_number'] = [
			'label' => __('Contact Number'),
			'value' => isset($advanceSearchModelData['hasMany']) ? $advanceSearchModelData['hasMany']['contact_number'] : '',
		];
		$this->_table->ControllerAction->field('contact_numbers', ['order' => 52]);
	}

	public function onGetContactNumbers(Event $event, Entity $entity) {
		$userId = $entity->id;
		$Contacts = TableRegistry::get('User.Contacts');
		$studentContacts = $Contacts->find()
			->contain(['ContactTypes'])
			->where([
				$Contacts->aliasField('security_user_id') => $userId
			])
			->toArray();
		
		if (!empty($studentContacts)) {
			foreach ($studentContacts as $key => $value) {
				$value = $value->value.'<br/>';
			}
		} else {
			$value = '';
		}
		return $value;
	}

}
