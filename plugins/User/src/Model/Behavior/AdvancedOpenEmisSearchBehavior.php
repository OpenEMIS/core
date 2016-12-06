<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class AdvancedOpenEmisSearchBehavior extends Behavior {
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
        $openEmisNo = $advancedSearchHasMany['openemis_no'];
        
        if (strlen($openEmisNo) > 0) {
            $query->where([
                $this->_table->aliasField('openemis_no LIKE ') => $openEmisNo . '%'
            ]);
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
        $searchables['openemis_no'] = [
            'label' => __('OpenEMIS ID'),
            'value' => (isset($advanceSearchModelData['hasMany']) && isset($advanceSearchModelData['hasMany']['openemis_no'])) ? $advanceSearchModelData['hasMany']['openemis_no'] : '',
        ];   
    }
}
