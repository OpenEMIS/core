<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;

class FieldTypeBehavior extends Behavior {
	private $CustomFieldTypes;

	public function initialize(array $config) {
        $this->CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');
    }

    public function getFieldTypeList() {
        $list = $this->CustomFieldTypes
        	->find('list', ['keyField' => 'code', 'valueField' => 'name'])
        	->find('visible')
        	->toArray();
        return $list;
    }
}
