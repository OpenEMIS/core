<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;

class FieldTypeBehavior extends Behavior {
	private $CustomFieldTypes;

	public function initialize(array $config) {
        $this->CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');
    }

    public function getFieldTypeList($format=[]) {
        $query = $this->CustomFieldTypes
            ->find('list', ['keyField' => 'code', 'valueField' => 'name'])
            ->find('visible');

        if (!empty($format)) {
            $query->where([$this->CustomFieldTypes->aliasField('format IN ') => $format]);
        }

        return $query->toArray();
    }

    public function onGetFieldType(Event $event, Entity $entity) {
        $fieldType = $entity->field_type;
        $customFieldType = $this->CustomFieldTypes
            ->find()
            ->where([
                $this->CustomFieldTypes->aliasField('code') => $fieldType
            ])
            ->first();

        // return $customFieldType->name;
    }
}
