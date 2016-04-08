<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class AdvancedIdentitySearchBehavior extends Behavior {
	protected $_defaultConfig = [
		'associatedKey' => '',
	];

	public function initialize(array $config) {
		$associatedKey = $this->config('associatedKey');
		if (empty($associatedKey)) {
			$this->config('associatedKey', $this->_table->aliasField('id'));
		}
	}
	
	public function onBuildQuery(Event $event, Query $query, $advancedSearchHasMany) {
		if (isset($advancedSearchHasMany['identity_number'])) {
			$search = $advancedSearchHasMany['identity_number'];
		} else {
			$search = '';
		}

		if (!empty($search)) {
			$IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
			$default_identity_type = $IdentityTypes->getDefaultValue();
			$searchString = '%' . $search . '%';
			$query->join([
					[
						'type' => 'LEFT',
						'table' => 'user_identities',
						'alias' => 'Identities',
						'conditions' => [
							'Identities.security_user_id = '. $this->config('associatedKey'),
							'Identities.identity_type_id = '. $default_identity_type
						]
					]
				]);
			$query->andWhere(['Identities.number LIKE' =>  $searchString]);
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
		$searchables['identity_number'] = [
			'label' => __('Identity Number'),
			'value' => (isset($advanceSearchModelData['hasMany']) && isset($advanceSearchModelData['hasMany']['identity_number'])) ? $advanceSearchModelData['hasMany']['identity_number'] : '',
		];
	}

}
