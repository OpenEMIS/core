<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class AdvancedContactNumberSearchBehavior extends Behavior {
	protected $_defaultConfig = [
		'associatedKey' => '',
	];

	public function initialize(array $config) {
		$associatedKey = $this->config('associatedKey');
		if (empty($associatedKey)) {
			$this->config('associatedKey', $this->_table->aliasField('id'));
		}
	}
	
	public function onBuildQuery(Event $event, Query $query, $advancedSearchHasMany) 
	{
		$search = $advancedSearchHasMany['contact_number'];
		
		if (strlen($search) > 0) {
			$searchString = '%' . $search . '%';
			$query->join([
					[
						'type' => 'LEFT',
						'table' => 'user_contacts',
						'alias' => 'Contacts',
						'conditions' => [
							'Contacts.security_user_id = '. $this->config('associatedKey')
						]
					]
				]);
			$query->andWhere(['Contacts.value LIKE' =>  $searchString]);
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
			'value' => (isset($advanceSearchModelData['hasMany']) && isset($advanceSearchModelData['hasMany']['contact_number'])) ? $advanceSearchModelData['hasMany']['contact_number'] : '',
		];
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
