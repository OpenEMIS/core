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

	public function onBuildQuery(Event $event, Query $query, $advancedSearchHasMany)
	{
        $identityType = $advancedSearchHasMany['identity_type'];
		$identityNumber = $advancedSearchHasMany['identity_number'];

        if (strlen($identityNumber) > 0) {
            $query->join([
                        'UserIdentities' => [
                            'table' => 'user_identities',
                            'conditions' => [
                                'UserIdentities.security_user_id = '.$this->config('associatedKey')
                            ]
                        ]
                    ])
                    ->where([
                        'UserIdentities.number LIKE ' => $identityNumber . '%'
                    ]);

            if (!empty($identityType)) {
                $query->andWhere([
                            'UserIdentities.identity_type_id' => $identityType
                        ]);
            }

            $query->group('UserIdentities.security_user_id');
        }
        return $query;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'AdvanceSearch.onSetupFormField' => 'onSetupFormField',
            'AdvanceSearch.onBuildQuery' => 'onBuildQuery',
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onSetupFormField(Event $event, ArrayObject $searchables, $advanceSearchModelData)
    {
        $searchables['identity_type'] = [
            'label' => __('Identity Type'),
            'type' => 'select',
            'options' => $this->getIdentityTypeOptions(),
            'selected' => (isset($advanceSearchModelData['hasMany']) && isset($advanceSearchModelData['hasMany']['identity_type'])) ? $advanceSearchModelData['hasMany']['identity_type'] : '',
        ];

        $searchables['identity_number'] = [
            'label' => __('Identity Number'),
            'value' => (isset($advanceSearchModelData['hasMany']) && isset($advanceSearchModelData['hasMany']['identity_number'])) ? $advanceSearchModelData['hasMany']['identity_number'] : '',
        ];
    }

    public function getIdentityTypeOptions()
    {
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');

        return  $IdentityTypes
                ->find('list')
                ->find('visible')
                //POCOR-4375 Error 404 When Accessing Directory
                ->order([$IdentityTypes->aliasField('order')])
                ->toArray();
    }
}
