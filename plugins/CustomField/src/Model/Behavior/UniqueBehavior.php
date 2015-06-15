<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;

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
        //$visible = $isUnique == 1 ? true : false;
        return ($isUnique == 1 ? true : false);
    }
}
