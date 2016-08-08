<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;

class UniqueBehavior extends Behavior {
	private $CustomFieldTypes;

	public function initialize(array $config) {
		$this->CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');
    }

    public function getUniqueList() {
        $list = [0 => __('No'), 1 => __('Yes')];
        return $list;
    }

	public function getUniqueVisibility($selectedFieldType) {
        $isUnique = $this->CustomFieldTypes->find('all')->where([$this->CustomFieldTypes->aliasField('code') => $selectedFieldType])->first()->is_unique;
        return ($isUnique == 1 ? true : false);
    }

    public function onGetIsUnique(Event $event, Entity $entity) {
        $isUnique = $this->CustomFieldTypes->find('all')->where([$this->CustomFieldTypes->aliasField('code') => $entity->field_type])->first()->is_unique;
        $is_unique = ($isUnique == 0) ? '<i class="fa fa-minus"></i>' : ($entity->is_unique == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>');
        return $is_unique;
    }
}
