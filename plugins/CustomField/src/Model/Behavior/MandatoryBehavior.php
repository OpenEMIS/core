<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;

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
}
