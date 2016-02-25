<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;

class MandatoryBehavior extends Behavior {
	private $CustomFieldTypes;

	public function initialize(array $config) {
		$this->CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');
    }

    public function getMandatoryList() {
        $list = [0 => __('No'), 1 => __('Yes')];
        return $list;
    }

    public function getMandatoryVisibility($selectedFieldType) {
        $isMandatory = $this->CustomFieldTypes->find('all')->where([$this->CustomFieldTypes->aliasField('code') => $selectedFieldType])->first()->is_mandatory;
        return ($isMandatory == 1 ? true : false);
    }

    public function onGetIsMandatory(Event $event, Entity $entity) {
        $isMandatory = $this->CustomFieldTypes->find('all')->where([$this->CustomFieldTypes->aliasField('code') => $entity->field_type])->first()->is_mandatory;
        $is_mandatory = ($isMandatory == 0) ? '<i class="fa fa-minus"></i>' : ($entity->is_mandatory == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>');
        return $is_mandatory;
    }
}
