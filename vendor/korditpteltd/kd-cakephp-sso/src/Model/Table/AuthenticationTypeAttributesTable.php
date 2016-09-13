<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use ArrayObject;

class AuthenticationTypeAttributesTable extends Table {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Timestamp', [
			'events' => [
        		'Model.beforeSave' => [
            		'created' => 'new',
           			'modified' => 'existing'
       			]
    		]
		]);
	}

    public function implementedEvents() {
    	$events = parent::implementedEvents();
        $events =  [
            'Model.beforeSave' => ['callable' => 'beforeSave', 'priority' => 5]
        ];
        return $events;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
        $userId = null;
        if (isset($_SESSION['Auth']) && isset($_SESSION['Auth']['User'])) {
            $userId = $_SESSION['Auth']['User']['id'];
        }
        if (is_null($userId)) {
        	$userId = 0;
        }
        if (!$entity->isNew()) {
            $entity->modified_user_id = $userId;
        } else {
        	$entity->created_user_id = $userId;
        }
    }

	public function getTypeAttributeValues($typeName = null) {
		$list = $this->find('list', [
                'groupField' => 'authentication_type',
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->order([$this->aliasField('attribute_field')])
            ->toArray();

		if (!is_null($typeName)) {
			if (isset($list[$typeName])) {
				return $list[$typeName];
			} else {
				return [];
			}
		} else {
			return $list;
		}
	}
}
